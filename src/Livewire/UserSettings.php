<?php

namespace Aura\Base\Livewire;

use Aura\Base\Models\Option;
use Aura\Base\Traits\InputFields;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Livewire\Component;

class UserSettings extends Component
{
    use InputFields;

    public $form = [
        'fields' => [],
    ];

    public $model;

    // Listen for selectedAttachment
    protected $listeners = ['updateField' => 'updateField'];

    public static function getFields()
    {
        return [
            [
                'type' => 'Aura\\Base\\Fields\\Tab',
                'name' => 'General',
                'label' => 'General',
                'slug' => 'tab-general',
                'global' => true,
            ],
            [
                'type' => 'Aura\\Base\\Fields\\Panel',
                'name' => 'Panel',
                'label' => 'Panel',
                'slug' => 'panel-DZzV',
            ],
            [
                'label' => 'Title',
                'name' => 'Title',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'required',
                'slug' => 'title',
                'style' => [
                    'width' => '100',
                ],
            ],

        ];
    }

    public function getFieldsProperty()
    {
        $fields = collect($this->mappedFields());

        return $this->fieldsForView($fields);
    }

    public function mount()
    {
        $this->model = Option::firstOrCreate([
            'name' => 'user-settings',
        ], [
            'value' => [],
        ]);

        $this->form['fields'] = json_decode($this->model->value, true);
    }

    public function render()
    {
        return view('aura::livewire.user-settings');
    }

    public function reorderMedia($slug, $ids)
    {
        $ids = collect($ids)->map(function ($id) {
            return Str::after($id, '_file_');
        })->toArray();

        $this->updateField([
            'slug' => $slug,
            'value' => $ids,
        ]);
    }

    public function rules()
    {
        return Arr::dot([
            'form.fields' => $this->validationRules(),
        ]);
    }

    public function save()
    {
        // Save Fields as user-settings in Option table
        $option = 'user-settings';
        $value = json_encode($this->form['fields']);
        $o = Option::updateOrCreate(['name' => $option], ['value' => $value]);

        // $this->validate();

        return $this->notify(__('Successfully updated'));

    }

    // Select Attachment
    public function updateField($data)
    {
        $this->form['fields'][$data['slug']] = $data['value'];

        $this->save();
    }
}
