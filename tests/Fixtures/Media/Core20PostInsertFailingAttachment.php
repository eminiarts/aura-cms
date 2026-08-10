<?php

namespace Aura\Base\Tests\Fixtures\Media;

use Aura\Base\Resources\Attachment;
use RuntimeException;

class Core20PostInsertFailingAttachment extends Attachment
{
    protected static function booted(): void
    {
        static::created(function (): void {
            throw new RuntimeException('simulated post-insert listener failure');
        });
    }
}
