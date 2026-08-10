<?php

namespace Aura\Base\Tests\Fixtures\Media;

use Aura\Base\Resources\Attachment;
use RuntimeException;

class Core20FailingAttachment extends Attachment
{
    public static function create(array $attributes = [])
    {
        throw new RuntimeException('simulated persistence failure');
    }
}
