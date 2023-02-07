<?php

namespace Eminiarts\Aura\Aura;

use Eminiarts\Aura\Traits\AuraModelConfig;
use Eminiarts\Aura\Traits\AuraTaxonomies;
use Eminiarts\Aura\Traits\InputFields;
use Eminiarts\Aura\Traits\InteractsWithTable;
use Illuminate\Database\Eloquent\Model;

class Resource extends Model
{
    use AuraModelConfig;
    use AuraTaxonomies;
    use InputFields;
    use InteractsWithTable;
}
