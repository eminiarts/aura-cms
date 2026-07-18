<?php

namespace Aura\Base\Fields;

/**
 * The User → Teams tab field. It reuses the parent-aware BelongsToMany query
 * scoping (#49) for any embedded-table context, but on the user View page it
 * renders the dedicated Membership editor (the aura::user-teams Livewire
 * component) instead of the read-only has-many table, so an authorized admin can
 * attach/detach teams and change the per-team role. Only the view() output
 * differs from the generic BelongsToMany field; every other resource pair keeps
 * the generic behavior.
 */
class UserTeams extends BelongsToMany
{
    public $view = 'aura::fields.user-teams';
}
