<?php

namespace Aura\Base\ResourcePersistence;

use Aura\Base\ConditionalLogic;
use Aura\Base\Contracts\FieldValueContext;
use Aura\Base\Fields\Slug;
use Aura\Base\Resource;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class ResourceWriteSchema
{
    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function validate(Resource $resource, array $input, FieldValueContext $context): array
    {
        $fields = $this->writableFields($resource, $input, $context);
        $declaredSlugs = $fields
            ->pluck('slug')
            ->filter(fn (mixed $slug): bool => is_string($slug) && $slug !== '')
            ->values();

        $unknown = collect(array_keys($input))
            ->filter(fn (mixed $key): bool => is_string($key))
            ->reject(fn (string $key): bool => $declaredSlugs->contains($key)
                || $declaredSlugs->contains(fn (string $slug): bool => str_starts_with($slug, $key.'.')))
            ->values();

        if ($unknown->isNotEmpty()) {
            throw ValidationException::withMessages($unknown
                ->mapWithKeys(fn (string $key): array => [$key => "The [{$key}] field is not writable."])
                ->all());
        }

        $trusted = [];

        foreach ($fields as $field) {
            $slug = $field['slug'] ?? null;

            if (! is_string($slug) || ! Arr::has($input, $slug)) {
                continue;
            }

            if ($this->isServerDerivedSlug($resource, $field)) {
                throw ValidationException::withMessages([
                    $slug => "The [{$slug}] field is derived by the server and cannot be submitted.",
                ]);
            }

            Arr::set($trusted, $slug, Arr::get($input, $slug));
        }

        $rules = collect($resource->validationRules())
            ->filter(fn (mixed $rule, mixed $slug): bool => is_string($slug) && $declaredSlugs->contains($slug))
            ->all();

        return Validator::make($trusted, $rules)->validate();
    }

    /**
     * @param  array<int, array<string, mixed>>  $fields
     * @return array<int, array<string, mixed>>
     */
    private function flatten(array $fields): array
    {
        $flattened = [];

        foreach ($fields as $field) {
            $flattened[] = $field;

            if (isset($field['fields']) && is_array($field['fields'])) {
                array_push($flattened, ...$this->flatten($field['fields']));
            }
        }

        return $flattened;
    }

    /** @param array<string, mixed> $field */
    private function isServerDerivedSlug(Resource $resource, array $field): bool
    {
        $fieldInstance = $field['field'] ?? null;

        return $fieldInstance instanceof Slug
            && $fieldInstance->isDisabled($resource, $field)
            && ! ($field['custom'] ?? false);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return Collection<int, array<string, mixed>>
     */
    private function writableFields(Resource $resource, array $input, FieldValueContext $context): Collection
    {
        $fields = $context === FieldValueContext::Create
            ? $resource->createFields()
            : $resource->editFields();

        $trusted = [];

        return collect($this->flatten($fields))
            ->filter(fn (array $field): bool => ($field['field_type'] ?? null) === 'input')
            ->filter(function (array $field) use ($resource, $input, &$trusted): bool {
                $slug = $field['slug'] ?? null;
                $fieldInstance = $field['field'] ?? null;

                if (! is_string($slug) || ! is_object($fieldInstance)) {
                    return false;
                }

                if (! $fieldInstance instanceof Slug && $fieldInstance->isDisabled($resource, $field)) {
                    return false;
                }

                if (! ConditionalLogic::shouldDisplayFieldForWrite($resource, $field, $trusted)) {
                    return false;
                }

                if (Arr::has($input, $slug) && ! $this->isServerDerivedSlug($resource, $field)) {
                    Arr::set($trusted, $slug, Arr::get($input, $slug));
                }

                return true;
            })
            ->values();
    }
}
