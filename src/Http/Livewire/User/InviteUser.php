<?php

namespace Eminiarts\Aura\Http\Livewire\User;

use Eminiarts\Aura\Traits\InputFields;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use LivewireUI\Modal\ModalComponent;

class InviteUser extends ModalComponent
{
    use InputFields;

    public $post;

    public static function getFields()
    {
        return [
            [
                'name' => 'Email',
                'type' => 'Eminiarts\\Aura\\Fields\\Email',
                'validation' => 'required',
                'slug' => 'email',
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
        return view('aura::livewire.user.invite-user');
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

        dd('save');


        $this->notify('Erfolgreich Erstellt.');

        $this->emit('closeModal');
    }
}
