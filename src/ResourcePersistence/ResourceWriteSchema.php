<?php

namespace Aura\Base\ResourcePersistence;

use Aura\Base\ConditionalLogic;
use Aura\Base\Contracts\FieldValueContext;
use Aura\Base\Fields\Slug;
use Aura\Base\Resource;
use Aura\Base\Rules\CaseInsensitiveUniqueEmail;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Unique;
use Illuminate\Validation\ValidationException;

final class ResourceWriteSchema
{
    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function validate(Resource $resource, array $input, FieldValueContext $context): array
    {
        $fields = collect($this->writableFields($resource, $input, $context));
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

            if (! is_string($slug)) {
                continue;
            }

            if ($this->isServerDerivedSlug($resource, $field)) {
                [$derivable, $derived] = $this->derivedSlugValue($field, $trusted, $input);

                if (! $derivable) {
                    continue;
                }

                if (Arr::has($input, $slug) && Arr::get($input, $slug) !== $derived) {
                    throw ValidationException::withMessages([
                        $slug => "The [{$slug}] field must match its server-derived value.",
                    ]);
                }

                Arr::set($trusted, $slug, $derived);

                continue;
            }

            if (! Arr::has($input, $slug)) {
                continue;
            }

            Arr::set($trusted, $slug, Arr::get($input, $slug));
        }

        $rules = collect($resource->validationRules())
            ->filter(fn (mixed $rule, mixed $slug): bool => is_string($slug) && $declaredSlugs->contains($slug))
            ->all();

        if ($context === FieldValueContext::Edit && $resource->exists) {
            $rules = collect($rules)
                ->mapWithKeys(fn (mixed $rule, string $attribute): array => [
                    $attribute => $this->ignoreCurrentResourceInRule($resource, $rule, $attribute),
                ])
                ->all();
        }

        return Validator::make($trusted, $rules)->validate();
    }

    /**
     * @param  array<string, mixed>  $field
     * @param  array<string, mixed>  $trusted
     * @param  array<string, mixed>  $input
     * @return array{0: bool, 1: mixed}
     */
    private function derivedSlugValue(array $field, array $trusted, array $input): array
    {
        $basedOn = $field['based_on'] ?? null;
        $fieldInstance = $field['field'] ?? null;

        if (! is_string($basedOn) || ! $fieldInstance instanceof Slug) {
            return [false, null];
        }

        $source = Arr::has($trusted, $basedOn)
            ? Arr::get($trusted, $basedOn)
            : Arr::get($input, $basedOn);

        if ($source === null) {
            return [false, null];
        }

        return [true, $fieldInstance->deriveValue($source)];
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

    private function ignoreCurrentResourceInRule(Resource $resource, mixed $rule, string $attribute): mixed
    {
        if (is_string($rule)) {
            return preg_replace_callback('/(^|\|)(unique:[^|]+)(?=\||$)/', function (array $matches) use ($attribute, $resource): string {
                $parameters = str_getcsv(substr($matches[2], strlen('unique:')), escape: '\\');

                if (isset($parameters[2]) && $parameters[2] !== '' && strtoupper((string) $parameters[2]) !== 'NULL') {
                    return $matches[0];
                }

                $parameters[1] = $parameters[1] ?? str($attribute)->afterLast('.')->toString();
                $parameters[2] = (string) $resource->getKey();
                $parameters[3] = $parameters[3] ?? $resource->getKeyName();

                return $matches[1].'unique:'.implode(',', $parameters);
            }, $rule) ?? $rule;
        }

        if (is_array($rule)) {
            return array_map(
                fn (mixed $nested): mixed => $this->ignoreCurrentResourceInRule($resource, $nested, $attribute),
                $rule,
            );
        }

        if ($rule instanceof Unique || $rule instanceof CaseInsensitiveUniqueEmail) {
            return (clone $rule)->ignore($resource->getKey(), $resource->getKeyName());
        }

        return $rule;
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
     * @return array<int, non-empty-array<string, mixed>>
     */
    private function writableFields(Resource $resource, array $input, FieldValueContext $context): array
    {
        $fields = $context === FieldValueContext::Create
            ? $resource->createFields()
            : $resource->editFields();

        $candidates = collect($this->flatten($fields))
            ->filter(fn (array $field): bool => ($field['field_type'] ?? null) === 'input')
            ->filter(function (array $field) use ($resource): bool {
                $slug = $field['slug'] ?? null;
                $fieldInstance = $field['field'] ?? null;

                if (! is_string($slug) || ! is_object($fieldInstance)) {
                    return false;
                }

                if (! $fieldInstance instanceof Slug && $fieldInstance->isDisabled($resource, $field)) {
                    return false;
                }

                return true;
            })
            ->values();
        $trusted = [];
        $visible = [];
        $maximumPasses = ($candidates->count() * 2) + 1;

        for ($pass = 0; $pass < $maximumPasses; $pass++) {
            $changed = false;

            foreach ($candidates as $field) {
                $slug = $field['slug'] ?? null;

                if (! is_string($slug)) {
                    continue;
                }

                if (! isset($visible[$slug])) {
                    if (! ConditionalLogic::shouldDisplayFieldForWrite($resource, $field, $trusted)) {
                        continue;
                    }

                    $visible[$slug] = true;
                    $changed = true;
                }

                if (Arr::has($trusted, $slug)) {
                    continue;
                }

                if ($this->isServerDerivedSlug($resource, $field)) {
                    [$derivable, $derived] = $this->derivedSlugValue($field, $trusted, $input);

                    if ($derivable) {
                        Arr::set($trusted, $slug, $derived);
                        $changed = true;
                    }

                    continue;
                }

                if (Arr::has($input, $slug)) {
                    Arr::set($trusted, $slug, Arr::get($input, $slug));
                    $changed = true;
                }
            }

            if (! $changed) {
                break;
            }
        }

        return $candidates
            ->filter(fn (array $field): bool => is_string($field['slug'] ?? null) && isset($visible[$field['slug']]))
            ->values()
            ->all();
    }
}
