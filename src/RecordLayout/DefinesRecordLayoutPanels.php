<?php

namespace Aura\Base\RecordLayout;

interface DefinesRecordLayoutPanels
{
    /** @return list<RecordLayoutPanel> */
    public static function recordLayoutPanels(): array;
}
