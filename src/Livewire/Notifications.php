<?php

namespace Eminiarts\Aura\Livewire;

use Eminiarts\Aura\Traits\InputFields;
use Livewire\Component;

class Notifications extends Component
{
    use InputFields;

    public $model;

    public $open = false;

    public $post = [
        'fields' => [],
    ];

    public function activate($params)
    {
        $this->open = true;
    }

    public static function getFields()
    {
        return [
            [
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'name' => 'Unread',
                'slug' => 'tab-unread',
                'global' => true,
            ],
            [
                'type' => 'Eminiarts\\Aura\\Fields\\View',
                'name' => 'Unread',
                'slug' => 'view-unread',
                'view' => 'aura::livewire.notifications-unread',
            ],
            [
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'name' => 'Read',
                'slug' => 'tab-read',
                'global' => true,
            ],
            [
                'type' => 'Eminiarts\\Aura\\Fields\\View',
                'name' => 'read',
                'slug' => 'view-unread',
                'view' => 'aura::livewire.notifications-read',
            ],
        ];
    }

    public function getFieldsForViewProperty()
    {
        $fields = collect($this->mappedFields());

        return $this->fieldsForView($fields);
    }

    public function getFieldsProperty()
    {
        return $this->inputFields()->mapWithKeys(function ($field) {
            return [$field['slug'] => $this->post['fields'][$field['slug']] ?? null];
        });
    }

    public function getNotificationsProperty()
    {
        return auth()->user()->readNotifications;
    }

    public function getUnreadNotificationsProperty()
    {
        return auth()->user()->unreadNotifications;
    }

    public function markAllAsRead()
    {
        auth()->user()->unreadNotifications()->update(['read_at' => now()]);
    }

    public function render()
    {
        return view('aura::livewire.notifications');
    }
}
