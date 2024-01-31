<?php

namespace Eminiarts\Aura\Livewire;

use Eminiarts\Aura\Traits\FieldsOnComponent;
use Eminiarts\Aura\Traits\InputFields;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use LivewireUI\Modal\ModalComponent;

class CreatePosttype extends ModalComponent
{
    use FieldsOnComponent;
    use InputFields;

    public $post = [
        'fields' => [
            'name' => '',
        ],
    ];

    public static function getFields()
    {
        return [
            [
                'name' => 'Name (Singular, e.g. Post)',
                'instructions' => 'The name of the post type, shown in the admin panel.',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => 'required|alpha:ascii',
                'slug' => 'name',
            ],
        ];
    }

    public function mount()
    {
        abort_if(app()->environment('production'), 403);

        abort_unless(auth()->user()->resource->isSuperAdmin(), 403);
    }

    public function render()
    {
        return view('aura::livewire.create-posttype');
    }

    public function rules()
    {
        return Arr::dot([
            'post.fields' => $this->validationRules(),
        ]);
    }

    public function save()
    {
        abort_unless(auth()->user()->resource->isSuperAdmin(), 403);

        $this->validate();

        Artisan::call('aura:posttype', [
            'name' => $this->post['fields']['name'],
        ]);

        Artisan::call('cache:clear');

        $this->notify('Erfolgreich Erstellt.');

        $this->dispatch('closeModal');
    }
}
