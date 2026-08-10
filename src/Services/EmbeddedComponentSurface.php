<?php

namespace Aura\Base\Services;

enum EmbeddedComponentSurface: string
{
    case Edit = 'edit';
    case Index = 'index';
    case View = 'view';
}
