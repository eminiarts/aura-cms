<?php

namespace Aura\Base\Http\Controllers\Api;

use Aura\Base\Http\Controllers\Controller;
use Illuminate\Http\Request;

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

        // Get the model
        //$model = app($request->model);

        // Get the field
        $field = app($request->field)->api($request);

        return $field;
    }
}
