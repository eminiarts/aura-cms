<?php

namespace Aura\Base\Livewire;

use Aura\Base\Traits\FieldsOnComponent;
use Aura\Base\Traits\InputFields;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use LivewireUI\Modal\ModalComponent;

class CreateResource extends ModalComponent
{
    use FieldsOnComponent;
    use InputFields;

    public $form = [
        'fields' => [
            'name' => '',
        ],
    ];

    public static function getFields()
    {
        return [
            [
                'name' => 'Name (Singular, e.g. Post)',
                'slug' => 'name',
                'instructions' => 'The name of the post type, shown in the admin panel.',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'required|alpha:ascii',
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
        return view('aura::livewire.create-resource');
    }

    public function rules()
    {
        return Arr::dot([
            'form.fields' => $this->validationRules(),
        ]);
    }

    public function save()
    {
        abort_unless(auth()->user()->resource->isSuperAdmin(), 403);

        $name = $this->form['fields']['name'];
        $slug = str($name)->slug();

        $this->validate();

        Artisan::call('aura:resource', [
            'name' => $this->form['fields']['name'],
        ]);

        Artisan::call('cache:clear');

        $this->notify('Erfolgreich Erstellt.');

        $this->dispatch('closeModal');



        // redirect to route
        return redirect()->route('aura.resource.editor', ['slug' => $slug]);

        // Route::get('/resources/{slug}/editor', ResourceEditor::class)->name('resource.editor');
    }
}
