<?php

namespace Aura\Base\Livewire;

use Aura\Base\Traits\InputFields;
use Livewire\Component;

class Notifications extends Component
{
    use InputFields;

    public $model;

    public $open = false;

    public $form = [
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
                'type' => 'Aura\\Base\\Fields\\Tab',
                'name' => 'Unread',
                'slug' => 'tab-unread',
                'global' => true,
            ],
            [
                'type' => 'Aura\\Base\\Fields\\View',
                'name' => 'Unread',
                'slug' => 'view-unread',
                'view' => 'aura::livewire.notifications-unread',
            ],
            [
                'type' => 'Aura\\Base\\Fields\\Tab',
                'name' => 'Read',
                'slug' => 'tab-read',
                'global' => true,
            ],
            [
                'type' => 'Aura\\Base\\Fields\\View',
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
            return [$field['slug'] => $this->form['fields'][$field['slug']] ?? null];
        });
    }

    public function getNotificationsProperty()
    {
        if (! auth()->check()) {
            return [];
        }

        return auth()->user()->readNotifications;
    }

    public function getUnreadNotificationsProperty()
    {
        if (! auth()->check()) {
            return [];
        }

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
