<?php

namespace Eminiarts\Aura\Fields;

use Eminiarts\Aura\Resources\Attachment;

class Image extends Field
{
    public $component = 'aura::fields.image';

    public $view = 'aura::fields.view-value';

    public function display($field, $value, $model)
    {
        if (! $value) {
            return;
        }

        $values = is_array($value) ? $value : [$value];

        $firstImageValue = array_shift($values);
        $attachment = Attachment::find($firstImageValue);

        if ($attachment) {
            $url = $attachment->path('media');
            $imageHtml = "<img src='{$url}' class='object-cover w-32 h-32 rounded-lg shadow-lg'>";
        } else {
            return $value;
        }

        $additionalImagesCount = count($values);
        $circleHtml = '';
        if ($additionalImagesCount > 0) {
            $circleHtml = "<div class='flex items-center justify-center w-10 h-10 font-bold text-center text-gray-800 bg-gray-200 rounded-full'>+{$additionalImagesCount}</div>";
        }

        return "<div class='flex items-center space-x-2'>{$imageHtml}{$circleHtml}</div>";
    }

    public function get($field, $value)
    {
        if (is_string($value)) {
            return json_decode($value, true);
        }

        return $value;
    }

    public function set($value)
    {
        return json_encode($value);
    }

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'label' => 'Image',
                'name' => 'Image',
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'slug' => 'image-tab',
                'style' => [],
            ],

            [
                'name' => 'Use Media Manager',
                'type' => 'Eminiarts\\Aura\\Fields\\Boolean',
                'slug' => 'use_media_manager',
            ],

            // min and max numbers for allowed number of files
            [
                'name' => 'Min Files',
                'type' => 'Eminiarts\\Aura\\Fields\\Number',
                'validation' => '',
                'slug' => 'min_files',
                'instructions' => 'Minimum number of files allowed',
            ],
            [
                'name' => 'Max Files',
                'type' => 'Eminiarts\\Aura\\Fields\\Number',
                'validation' => '',
                'slug' => 'max_files',
                'instructions' => 'Maximum number of files allowed',
            ],

            // allowed file types
            [
                'name' => 'Allowed File Types',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => '',
                'slug' => 'allowed_file_types',
                'instructions' => 'Comma separated list of allowed file types. Example: jpg, png, gif',
            ],

        ]);
    }
}
