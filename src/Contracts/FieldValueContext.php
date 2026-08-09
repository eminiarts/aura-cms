<?php

namespace Aura\Base\Contracts;

enum FieldValueContext: string
{
    case Create = 'create';
    case Edit = 'edit';
    case Export = 'export';
    case Index = 'index';
    case Model = 'model';
    case View = 'view';
}
