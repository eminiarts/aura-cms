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
       $imagesHtml = '';

       $offset = 0;
       foreach ($values as $imageValue) {
           $attachment = Attachment::find($imageValue);

           if ($attachment) {
               $url = $attachment->path();
               $imagesHtml .= "<img src='{$url}' class='w-32 h-32 object-cover rounded-lg shadow-lg absolute' style='left: {$offset}px'>";
               $offset += 10; // Change this value to adjust the offset between images
           }
       }

       return "<div class='relative h-32' style='height: 128px; width: calc(100% + {$offset}px)'>{$imagesHtml}</div>";
   }

}
