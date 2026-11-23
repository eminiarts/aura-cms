<?php

use Aura\Base\Livewire\Notifications;
use Livewire\Component;

class ReplacementNotificationsPanel extends Component
{
    public function render()
    {
        return '<div>replacement panel</div>';
    }
}

test('aura::notifications resolves to Aura\'s slide-over by default', function () {
    expect(app('livewire')->new('aura::notifications'))->toBeInstanceOf(Notifications::class);
});

test('a plugin can replace the notifications slide-over through aura.components.notifications', function () {
    config(['aura.components.notifications' => ReplacementNotificationsPanel::class]);

    // The layout renders <livewire:aura::notifications/>.
    expect(app('livewire')->new('aura::notifications'))->toBeInstanceOf(ReplacementNotificationsPanel::class);
});
