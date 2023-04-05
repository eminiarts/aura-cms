<?php

namespace Eminiarts\Aura\Fields;

use Eminiarts\Aura\Resources\Attachment;

class Image extends Field
{
    public $component = 'aura::fields.image';

    public $view = 'aura::fields.view-value';

    public function get($field, $value)
    {
        return json_decode($value, true);
    }

    public function set($value)
    {
        return json_encode($value);
    }

   public function display($field, $value, $model)
   {
       if (!$value) {
           return;
       }

       $values = is_array($value) ? $value : [$value];

       $firstImageValue = array_shift($values);
       $attachment = Attachment::find($firstImageValue);

       if ($attachment) {
           $url = $attachment->path();
           $imageHtml = "<img src='{$url}' class='w-32 h-32 object-cover rounded-lg shadow-lg'>";
       }

       $additionalImagesCount = count($values);
       $circleHtml = '';
       if ($additionalImagesCount > 0) {
           $circleHtml = "<div class='h-10 w-10 bg-gray-200 text-center flex items-center justify-center rounded-full text-gray-800 font-bold'>+{$additionalImagesCount}</div>";
       }

       return "<div class='flex items-center space-x-2'>{$imageHtml}{$circleHtml}</div>";
   }

}
