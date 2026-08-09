<?php

namespace Aura\Base\Livewire\Table;

use Aura\Base\Resource;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use ReflectionMethod;

final class TableMutationDispatcher
{
    public function __construct(private readonly Gate $gate) {}

    public function abilityFor(string $action, mixed $definition = null): string
    {
        if (is_array($definition) && array_key_exists('ability', $definition)) {
            if (! is_string($definition['ability']) || $definition['ability'] === '') {
                abort(422, 'The declared table action ability is invalid.');
            }

            return $definition['ability'];
        }

        $normalizedAction = Str::lower($action);

        if (str_contains($normalizedAction, 'forcedelete')) {
            return 'forceDelete';
        }

        if (str_contains($normalizedAction, 'restore')) {
            return 'restore';
        }

        if (str_contains($normalizedAction, 'delete') || str_contains($normalizedAction, 'trash')) {
            return 'delete';
        }

        return 'update';
    }

    public function authorize(Model $record, string $ability): void
    {
        $this->gate->authorize($ability, $record);
    }

    /**
     * @param  array<string, mixed>  $declaredActions
     */
    public function dispatchAction(Model $record, string $action, array $declaredActions): mixed
    {
        if (! array_key_exists($action, $declaredActions)) {
            abort(403, 'This table action is not allowed.');
        }

        $actionDefinition = $declaredActions[$action];
        $this->authorize($record, $this->abilityFor($action, $actionDefinition));

        $condition = is_array($actionDefinition) ? ($actionDefinition['conditional_logic'] ?? null) : null;

        if ($condition !== null && (! is_callable($condition) || ! $condition())) {
            abort(403, 'This table action is not available for the record.');
        }

        if (! method_exists($record, $action)) {
            abort(422, 'The declared table action cannot be executed.');
        }

        $method = new ReflectionMethod($record, $action);

        if (! $method->isPublic() || $method->isStatic() || $method->getNumberOfRequiredParameters() > 0) {
            abort(422, 'The declared table action cannot be executed.');
        }

        return $record->{$action}();
    }

    public function findRecord(Model $resource, int|string $id): Model
    {
        $record = $resource->newQuery()->find($id);

        if (! $record instanceof Model) {
            abort(404);
        }

        return $record;
    }

    public function updateField(Resource $record, string $fieldSlug, mixed $value): void
    {
        $this->authorize($record, 'update');

        $field = $record->fieldBySlug($fieldSlug);
        $fieldClass = $record->fieldClassBySlug($fieldSlug);

        if (
            ! is_array($field)
            || ($field['slug'] ?? null) !== $fieldSlug
            || (! $record->isTableField($fieldSlug) && ! $record->isMetaField($fieldSlug))
            || ! is_object($fieldClass)
            || ! method_exists($fieldClass, 'options')
        ) {
            throw ValidationException::withMessages([
                'kanbanField' => 'The configured Kanban group field is invalid.',
            ]);
        }

        $allowedValues = collect($fieldClass->options($record, $field))->map(function (mixed $option, mixed $key) {
            if (is_array($option) && array_key_exists('key', $option)) {
                return $option['key'];
            }

            return is_int($key) ? $option : $key;
        })->filter(fn (mixed $allowedValue) => is_string($allowedValue) || is_int($allowedValue));

        $matchedValue = $allowedValues->first(
            fn (mixed $allowedValue) => is_string($value) || is_int($value)
                ? (string) $allowedValue === (string) $value
                : false,
        );

        if ($matchedValue === null) {
            throw ValidationException::withMessages([
                'kanbanStatus' => 'The selected Kanban status is invalid.',
            ]);
        }

        $record->setAttribute($fieldSlug, $matchedValue);
        $record->save();
    }
}
