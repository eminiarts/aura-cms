<?php

namespace Aura\Base\RecordLayout;

enum RecordLayoutRegion: string
{
    case ActivityTimeline = 'activity-timeline';
    case HeaderActions = 'header-actions';
    case LeftSummary = 'left-summary';
    case MainContent = 'main-content';
    case RightSidebar = 'right-sidebar';
}
