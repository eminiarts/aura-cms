<?php

namespace Aura\Base;

use Aura\Base\Contracts\DefinesFields;
use Aura\Base\Contracts\ProvidesEmbeddedAuthorizationAttributes;
use Aura\Base\Traits\AuraModelConfig;
use Aura\Base\Traits\InputFields;
use Aura\Base\Traits\InteractsWithTable;
use Aura\Base\Traits\ProvidesEmbeddedAuthorizationAttributes as ProvidesEmbeddedAuthorizationAttributesTrait;
use Illuminate\Database\Eloquent\Model;

class BaseResource extends Model implements DefinesFields, ProvidesEmbeddedAuthorizationAttributes
{
    use AuraModelConfig;
    use InputFields;
    use InteractsWithTable;
    use ProvidesEmbeddedAuthorizationAttributesTrait;
}
