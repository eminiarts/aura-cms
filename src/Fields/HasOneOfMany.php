<?php

namespace Eminiarts\Aura\Fields;

class HasOneOfMany extends Field
{
    public $component = 'aura::fields.has-one-of-many';

    public bool $group = false;

    public string $type = 'relation';

    public function queryFor($model, $query)
    {
    }
}
