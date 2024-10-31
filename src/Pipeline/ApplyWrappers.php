<?php

namespace Aura\Base\Pipeline;

use Closure;
use Illuminate\Support\Str;

class ApplyWrappers implements Pipe
{
    public function handle($fields, Closure $next)
    {
        $newFields = [];
        $addedWrappers = [];

        foreach ($fields as $field) {
            $wrapperInfo = $this->getFieldWrapper($field);

            if ($wrapperInfo && !in_array($wrapperInfo['type'], $addedWrappers)) {
                // Add the wrapper field once
                $wrapperField = [
                    'label' => $wrapperInfo['type'],
                    'name'  => $wrapperInfo['type'],
                    'type'  => $wrapperInfo['type'],
                    'slug'  => Str::slug($wrapperInfo['type']),
                    'field' => app($wrapperInfo['type']),
                ];

                if ($wrapperInfo['class']) {
                    $wrapperField['field'] = new $wrapperInfo['class']();
                }

                $newFields[] = $wrapperField;
                $addedWrappers[] = $wrapperInfo['type'];
            }

            // Add the field as is
            $newFields[] = $field;
        }

        // Return a collection if the input was a collection
        if ($fields instanceof \Illuminate\Support\Collection) {
            $newFields = collect($newFields);
        }

        // For debugging
        ray($newFields);

        // Pass the new fields to the next pipe
        return $next($newFields);
    }

    private function getFieldWrapper($field)
    {
        if (isset($field['wrapper']) && $field['wrapper']) {
            $wrapperType = $field['wrapper'];
            $wrapperFieldClass = $field['wrapperFieldClass'] ?? null;
            $wrapperSlug = $field['wrapperSlug'] ?? $this->getWrapperSlug($wrapperType);
            return [
                'type'  => $wrapperType,
                'class' => $wrapperFieldClass,
                'slug'  => $wrapperSlug,
            ];
        } elseif (isset($field['field'])) {
            $fieldInstance = $field['field'];
            if (isset($fieldInstance->wrapper) && $fieldInstance->wrapper) {
                $wrapperType = $fieldInstance->wrapper;
                $wrapperFieldClass = $fieldInstance->wrapperFieldClass ?? null;
                $wrapperSlug = $fieldInstance->wrapperSlug ?? $this->getWrapperSlug($wrapperType);
                return [
                    'type'  => $wrapperType,
                    'class' => $wrapperFieldClass,
                    'slug'  => $wrapperSlug,
                ];
            }
        } elseif (isset($field['type']) && class_exists($field['type'])) {
            $fieldInstance = new $field['type']();
            if (isset($fieldInstance->wrapper) && $fieldInstance->wrapper) {
                $wrapperType = $fieldInstance->wrapper;
                $wrapperFieldClass = $fieldInstance->wrapperFieldClass ?? null;
                $wrapperSlug = $fieldInstance->wrapperSlug ?? $this->getWrapperSlug($wrapperType);
                return [
                    'type'  => $wrapperType,
                    'class' => $wrapperFieldClass,
                    'slug'  => $wrapperSlug,
                ];
            }
        }

        return null;
    }

    private function getWrapperSlug($wrapperType)
    {
        // Generate slug for wrapper
        return strtolower(str_replace('\\', '-', $wrapperType));
    }
}
