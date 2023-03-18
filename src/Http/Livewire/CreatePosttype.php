<?php

namespace Eminiarts\Aura\Http\Livewire;

use Eminiarts\Aura\Traits\InputFields;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use LivewireUI\Modal\ModalComponent;

class CreatePosttype extends ModalComponent
{
    use InputFields;

    public $post;

    public static function getFields()
    {
        return [
            [
                'name' => 'Name (Singular, e.g. Post)',
                'instructions' => 'The name of the post type, shown in the admin panel.',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => 'required',
                'slug' => 'name',
            ],
        ];
    }

    public function getFieldsProperty()
    {
        $fields = collect($this->mappedFields());

        return $this->fieldsForView($fields);
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
        $this->validate();

        Artisan::call('aura:posttype', [
            'name' => $this->post['fields']['name'],
        ]);

        Artisan::call('cache:clear');

        $this->notify('Erfolgreich Erstellt.');

        $this->emit('closeModal');
    }
}
