<?php

namespace VendorName\Skeleton;

use Eminiarts\Aura\Fields\Field;

class Skeleton extends Field
{
    public $component = ':vendor_slug-skeleton::fields.skeleton';

    public $view = ':vendor_slug-skeleton::fields.skeleton-view';

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            // Custom Fields for this field
            // See Documentation for more info
        ]);
    }
}
