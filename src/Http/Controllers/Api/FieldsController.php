<?php

namespace Aura\Base\Http\Controllers\Api;

use Aura\Base\Fields\Field;
use Aura\Base\Http\Controllers\Controller;
use Aura\Base\Resource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class FieldsController extends Controller
{
    public function values(Request $request)
    {
        // if $request->model or $request->slug are missing, throw an error
        if (! $request->model || ! $request->slug) {
            return response()->json([
                'error' => 'Missing model or slug',
            ], 400);
        }

        // The field class is client-controlled. Only allow instantiating a
        // genuine Aura field type — this prevents resolving an arbitrary class
        // through the container and invoking its api() method.
        if (! $request->field || ! is_string($request->field) || ! is_subclass_of($request->field, Field::class)) {
            return response()->json([
                'error' => 'Invalid field',
            ], 400);
        }

        // The target model is also client-controlled. Restrict it to Aura
        // resources and require the current user to be allowed to view them —
        // otherwise any authenticated user could enumerate titles/meta of
        // resource types they have no access to.
        if (! is_string($request->model) || ! is_subclass_of($request->model, Resource::class)) {
            return response()->json([
                'error' => 'Invalid model',
            ], 400);
        }

        $model = app($request->model);

        if (Gate::denies('viewAny', $model)) {
            return response()->json([
                'error' => 'Unauthorized',
            ], 403);
        }

        $field = app($request->field);

        if (! method_exists($field, 'api')) {
            return response()->json([
                'error' => 'Invalid field',
            ], 400);
        }

        return $field->api($request);
    }
}
