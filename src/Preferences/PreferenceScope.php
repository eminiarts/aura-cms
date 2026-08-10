<?php

namespace Aura\Base\Preferences;

enum PreferenceScope: string
{
    case Everyone = 'everyone';
    case Team = 'team';
    case User = 'user';
}
