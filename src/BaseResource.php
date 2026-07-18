<?php

namespace Aura\Base;

use Aura\Base\Contracts\DefinesFields;
use Aura\Base\Traits\AuraModelConfig;
use Aura\Base\Traits\InputFields;
use Aura\Base\Traits\InteractsWithTable;
use Illuminate\Database\Eloquent\Model;

class BaseResource extends Model implements DefinesFields
{
    use AuraModelConfig;
    use InputFields;
    use InteractsWithTable;
}
