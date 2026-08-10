<?php

namespace Aura\Base\Routing;

use Aura\Base\Livewire\Resource\View;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionNamedType;

final class ResourceViewRoute
{
    /**
     * @param  class-string<Model>  $resource
     * @param  class-string  $component
     * @return array<int, string>
     */
    public static function middleware(string $resource, string $component): array
    {
        $parameter = self::parameter($resource, $component);

        return $parameter === 'id' ? [] : ["can:view,{$parameter}"];
    }

    /**
     * @param  class-string<Model>  $resource
     * @param  class-string  $component
     */
    public static function parameter(string $resource, string $component): string
    {
        if (is_a($component, View::class, true) || ! method_exists($component, 'mount')) {
            return 'id';
        }

        $mount = (new ReflectionClass($component))->getMethod('mount');

        foreach ($mount->getParameters() as $parameter) {
            $type = $parameter->getType();

            if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
                continue;
            }

            $model = $type->getName();

            if (! is_a($model, Model::class, true) || ! is_a($model, UrlRoutable::class, true)) {
                continue;
            }

            if (! is_a($resource, $model, true) && ! is_a($model, $resource, true)) {
                throw new InvalidArgumentException("The [{$component}] view component binds [{$model}], which does not match [{$resource}].");
            }

            return $parameter->getName();
        }

        return 'id';
    }
}
