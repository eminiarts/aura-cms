<?php

namespace App\Aura;

use App\Aura\Traits\AuraModelConfig;
use App\Aura\Traits\AuraTaxonomies;
use App\Aura\Traits\InputFields;
use App\Aura\Traits\InteractsWithTable;
use Illuminate\Database\Eloquent\Model;

class Resource extends Model
{
    use AuraModelConfig;
    use AuraTaxonomies;
    use InputFields;
    use InteractsWithTable;
}
