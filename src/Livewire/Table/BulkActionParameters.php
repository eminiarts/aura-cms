<?php

namespace Aura\Base\Livewire\Table;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class BulkActionParameters
{
    /** @var list<string> */
    private const TYPES = ['array', 'boolean', 'float', 'integer', 'string'];

    /**
     * @param  array<string, mixed>  $definition
     * @param  array<string, mixed>  $parameters
     * @return array<string, array{label: string, options?: array<mixed>, rules: array<int, mixed>, type: string}>
     */
    public function declarations(array $definition, array $parameters = []): array
    {
        $declarations = $definition['parameters'] ?? [];

        if (! is_array($declarations)) {
            abort(422, 'The declared bulk action parameters are invalid.');
        }

        $normalized = [];

        foreach ($declarations as $name => $declaration) {
            if (
                ! is_string($name)
                || preg_match('/\A[A-Za-z][A-Za-z0-9_]*\z/', $name) !== 1
                || ! is_array($declaration)
                || ! is_string($declaration['label'] ?? null)
                || $declaration['label'] === ''
                || ! is_string($declaration['type'] ?? null)
                || ! in_array($declaration['type'], self::TYPES, true)
                || ! is_array($declaration['rules'] ?? null)
            ) {
                abort(422, 'The declared bulk action parameters are invalid.');
            }

            if (array_key_exists('options', $declaration) && ! is_array($declaration['options'])) {
                abort(422, 'The declared bulk action parameters are invalid.');
            }

            $normalized[$name] = $declaration;
        }

        if (array_diff_key($parameters, $normalized) !== []) {
            throw ValidationException::withMessages([
                'parameters' => 'The bulk action parameters contain undeclared values.',
            ]);
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $definition
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    public function validate(array $definition, array $parameters): array
    {
        $declarations = $this->declarations($definition, $parameters);
        $rules = [];

        foreach ($declarations as $name => $declaration) {
            $rules[$name] = array_merge(
                $declaration['rules'],
                [$this->validationRuleFor($declaration['type'])],
            );
        }

        $validated = Validator::make($parameters, $rules)->validate();

        foreach ($validated as $name => $value) {
            $validated[$name] = $this->cast($declarations[$name]['type'], $value);
        }

        return $validated;
    }

    private function cast(string $type, mixed $value): mixed
    {
        return match ($type) {
            'boolean' => (bool) $value,
            'float' => (float) $value,
            'integer' => (int) $value,
            'string' => (string) $value,
            default => $value,
        };
    }

    private function validationRuleFor(string $type): string
    {
        return match ($type) {
            'float' => 'numeric',
            default => $type,
        };
    }
}
