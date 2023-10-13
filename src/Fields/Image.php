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
        return json_decode($value, true);
    }

    public function set($value)
    {
        return json_encode($value);
    }
}
