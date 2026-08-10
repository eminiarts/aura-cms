<?php

namespace Aura\Base\Livewire;

use Closure;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Support\Str;

final class ModalActionRegistry
{
    /**
     * @var array<string, array{
     *     authorize: Closure|null,
     *     component: string,
     *     rules: array<string, mixed>
     * }>
     */
    private array $actions = [];

    public function __construct(private readonly ValidationFactory $validation) {}

    /**
     * @param  array<string, mixed>  $rules
     */
    public function register(
        string $action,
        string $component,
        array $rules = [],
        ?Closure $authorize = null,
    ): void {
        if (
            preg_match('/\A[A-Za-z0-9][A-Za-z0-9._:-]*\z/', $action) !== 1
            || preg_match('/\A[A-Za-z0-9][A-Za-z0-9._:-]*\z/', $component) !== 1
        ) {
            abort(422, 'The modal action declaration is invalid.');
        }

        $this->actions[$action] = [
            'authorize' => $authorize,
            'component' => $component,
            'rules' => $rules,
        ];
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @param  array<string, mixed>  $modalAttributes
     * @return array{arguments: array<string, mixed>, component: string, modalAttributes: array<string, mixed>}
     */
    public function resolve(string $action, array $arguments, array $modalAttributes): array
    {
        if (! array_key_exists($action, $this->actions)) {
            abort(422, 'The requested modal action is not declared.');
        }

        $definition = $this->actions[$action];
        $rules = [
            'modalAttributes.persistent' => ['sometimes', 'boolean'],
            'modalAttributes.modalClasses' => ['sometimes', 'string', 'max:255'],
            'modalAttributes.slideOver' => ['sometimes', 'boolean'],
            ...$definition['rules'],
        ];
        $declaredArgumentRules = array_keys($definition['rules']);
        $unknownArguments = array_filter(
            $this->parameterPaths($arguments, 'arguments'),
            fn (string $path): bool => ! collect($declaredArgumentRules)->contains(
                fn (string $rule): bool => Str::is($rule, $path),
            ),
        );
        $unknownModalAttributes = array_diff(
            array_keys($modalAttributes),
            ['persistent', 'modalClasses', 'slideOver'],
        );

        if ($unknownArguments !== [] || $unknownModalAttributes !== []) {
            abort(422, 'The modal action parameters are invalid.');
        }

        $validated = $this->validation->make([
            'arguments' => $arguments,
            'modalAttributes' => $modalAttributes,
        ], $rules)->validate();
        $validatedArguments = $validated['arguments'] ?? [];
        $validatedModalAttributes = $validated['modalAttributes'] ?? [];

        $definition['authorize']?->__invoke($validatedArguments);

        return [
            'arguments' => $validatedArguments,
            'component' => $definition['component'],
            'modalAttributes' => $validatedModalAttributes,
        ];
    }

    /**
     * @param  array<string, mixed>  $values
     * @return list<string>
     */
    private function parameterPaths(array $values, string $prefix): array
    {
        $paths = [];

        foreach ($values as $key => $value) {
            $path = $prefix.'.'.$key;

            if (is_array($value) && $value !== []) {
                array_push($paths, ...$this->parameterPaths($value, $path));
            } else {
                $paths[] = $path;
            }
        }

        return $paths;
    }
}
