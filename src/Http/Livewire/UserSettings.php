<?php

namespace Eminiarts\Aura\Http\Livewire;

use Eminiarts\Aura\Models\Option;
use Eminiarts\Aura\Traits\InputFields;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Livewire\Component;

class UserSettings extends Component
{
    use InputFields;

    public $model;

    public $post = [
        'fields' => [],
    ];

    // Listen for selectedAttachment
    protected $listeners = ['updateField' => 'updateField'];

    public static function getFields()
    {
        return [
            [
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'name' => 'General',
                'label' => 'General',
                'slug' => 'tab-general',
                'global' => true,
            ],
            [
                'type' => 'Eminiarts\\Aura\\Fields\\Panel',
                'name' => 'Panel',
                'label' => 'Panel',
                'slug' => 'panel-DZzV',
            ],
            [
                'label' => 'Title',
                'name' => 'Title',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
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

        $this->post['fields'] = json_decode($this->model->value, true);
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
            'post.fields' => $this->validationRules(),
        ]);
    }

    public function save()
    {
        // Save Fields as user-settings in Option table
        $option = 'user-settings';
        $value = json_encode($this->post['fields']);
        $o = Option::updateOrCreate(['name' => $option], ['value' => $value]);

        // $this->validate();

        return $this->notify('Successfully updated.');

        // dd('hier')

        // $this->post->save();

        // Artisan::call('make:posttype', [
        //     'name' => $this->post['fields']['name'],
        // ]);

        // return $this->notify('Created successfully.');
    }

    // Select Attachment
    public function updateField($data)
    {
        $this->post['fields'][$data['slug']] = $data['value'];

        // dd($this->post['fields'][$data['slug']], $data['value']);
        // dd($this->post);
        $this->save();
    }
}
