<?php

namespace Eminiarts\Aura\Http\Livewire;

use Livewire\Component;
use Illuminate\Support\Arr;
use Eminiarts\Aura\Traits\HasFields;
use Eminiarts\Aura\Traits\InputFields;
use LivewireUI\Modal\ModalComponent;
use Illuminate\Support\Facades\Artisan;

class CreatePosttype extends ModalComponent
{
    use InputFields;

    public $post;

    public function render()
    {
        return view('livewire.create-posttype');
    }

    public function rules()
    {
        return Arr::dot([
            'post.fields' => $this->validationRules(),
        ]);
    }

    public function getFieldsProperty()
    {
        $fields = collect($this->mappedFields());

        return $this->fieldsForView($fields);
    }

    public static function getFields()
    {
        return [
            'name' => [
                'id' => 3,
                'label' => 'Name',
                'name' => 'name',
                'type' => 'App\\Aura\\Fields\\Text',
                'validation' => 'required',
                'on_index' => true,
                'has_conditional_logic' => false,
                'slug' => 'name',

            ],
        ];
    }

    public function save()
    {
        $this->validate();

        Artisan::call('make:posttype', [
            'name' => $this->post['fields']['name']
        ]);

        return $this->notify('Erfolgreich Erstellt.');
    }
}
