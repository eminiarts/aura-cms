<?php

namespace Aura\Base;

use Aura\Base\Traits\AuraModelConfig;
use Aura\Base\Traits\InputFields;
use Aura\Base\Traits\InteractsWithTable;
use Illuminate\Database\Eloquent\Model;

class BaseResource extends Model
{
    use AuraModelConfig;
    use InputFields;
    use InteractsWithTable;
}
