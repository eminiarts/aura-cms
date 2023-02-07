<?php

namespace Eminiarts\Aura\Http\Livewire;

use Eminiarts\Aura;
use Eminiarts\Aura\Traits\SaveFields;
use Illuminate\Support\Str;
use Livewire\Component;

class Posttype extends Component
{
    use SaveFields;

    public $fields = [];

    public $fieldsArray = [];

    public $globalTabs = [];

    public $hasGlobalTabs = false;

    public $model;

    public $postTypeFields = [];

    public $slug;

    protected $listeners = ['refreshComponent' => '$refresh', 'savedField' => 'updateFields', 'saveField' => 'saveField', 'deleteField' => 'deleteField'];

    protected $newFields = [];

    public function addConditionalLogicRule($key, $group)
    {
        $this->fields[$key]['conditional_logic'][$group][] = ['param' => '', 'operator' => '=', 'value' => ''];
    }

    public function addConditionalLogicRuleGroup($key)
    {
        // code...
        $this->fields[$key]['conditional_logic'][] = [
            ['param' => '', 'operator' => '=', 'value' => ''],
        ];
    }

    public function addField($id, $slug, $type, $children)
    {
        // dd($id, $slug, $type, $children);
        $children = (int) $children;
        $str = Str::random(4);
        if ($type == 'App\\Aura\\Fields\\Tab') {
            $field = [
                'name' => 'Tab '.$str,
                'type' => 'App\\Aura\\Fields\\Text',
                'validation' => '',
                'conditional_logic' => [],
                'slug' => 'tab'.'_'.$str,
            ];
        } elseif ($type == 'App\\Aura\\Fields\\Panel') {
            $field = [
                'name' => 'Panel '.$str,
                'type' => 'App\\Aura\\Fields\\Panel',
                'validation' => '',
                'conditional_logic' => [],
                'slug' => 'panel'.'_'.$str,
            ];
        } else {
            $field = [
                'name' => 'Text '.$str,
                'type' => 'App\\Aura\\Fields\\Text',
                'validation' => '',
                'conditional_logic' => [],
                'slug' => 'text'.'_'.$str,
            ];
        }

        // dd($field, $type);

        $fields = collect($this->fieldsArray);

        // get index of the field
        $index = $fields->search(function ($item) use ($slug) {
            return $item['slug'] == $slug;
        });

        // dd($index, $children, $index + $children, $slug, $fields);

        // duplicate field in at index of the field + 1
        $fields->splice($index + $children + 1, 0, [$field]);

        $fields = $fields->toArray();

        $this->fieldsArray = $fields;

        $this->saveFields($this->fieldsArray);

        $this->newFields = $this->model->mapToGroupedFields($this->fieldsArray);

        // $this->emit('refreshComponent');

        $this->emit('openSlideOver', 'edit-field', ['fieldSlug' => $field['slug'], 'slug' => $this->slug, 'field' => $field]);
    }

    public function addNewTab()
    {
        $fields = collect($this->fieldsArray);
        // dd($fields);

        // check if collection has an item with type = "App\Aura\Fields\Tab" and global = true
        $hasGlobalTabs = $fields->where('type', 'App\Aura\Fields\Tab')->where('global', true)->count();
        $globalTab = [
            'type' => 'App\Aura\Fields\Tab',
            'name' => 'Tab',
            'label' => 'Tab',
            'slug' => 'tab-'.Str::random(4),
            'global' => true,
        ];
        // if no global tabs, add one to the beginning of the collection
        if ($hasGlobalTabs == 0) {
            $fields->prepend($globalTab);
        }
        // else add it to the end of the collection
        else {
            $fields->push($globalTab);
        }

        $fields = $fields->toArray();

        $this->fieldsArray = $fields;

        $this->saveFields($this->fieldsArray);

        $this->newFields = $this->model->mapToGroupedFields($this->fieldsArray);

        $this->emit('refreshComponent');

        $this->emit('openSlideOver', 'edit-field', ['fieldSlug' => $globalTab['slug'], 'slug' => $this->slug, 'field' => $globalTab]);
    }

    public function addTemplateFields($slug)
    {
        $template = Aura::findTemplateBySlug($slug);
        $newFields = $template->getFields();

        $this->saveFields($newFields);

        $this->newFields = $this->model->mapToGroupedFields($newFields);

        return redirect(request()->header('Referer'));
    }

    public function deleteField($data)
    {
        $fields = collect($this->fieldsArray);

        $field = $fields->where('slug', $data['slug'])->first();

        $fields = $fields->reject(function ($item) use ($data) {
            return $item['slug'] == $data['slug'];
        });

        $this->fieldsArray = $fields->toArray();

        $this->saveFields($this->fieldsArray);

        $this->newFields = $this->model->mapToGroupedFields($this->fieldsArray);
    }

    public function duplicateField($id, $slug)
    {
        $fields = collect($this->fieldsArray);

        $field = $fields->where('slug', $slug)->first();

        $field['slug'] = $field['slug'].'_'.Str::random(4);
        $field['name'] = $field['name'].' Copy';

        // get index of the field
        $index = $fields->search(function ($item) use ($slug) {
            return $item['slug'] == $slug;
        });

        // duplicate field in at index of the field + 1
        $fields->splice($index + 1, 0, [$field]);

        $fields = $fields->toArray();

        $this->fieldsArray = $fields;

        $this->saveFields($this->fieldsArray);

        $this->newFields = $this->model->mapToGroupedFields($this->fieldsArray);

        $this->emit('openSlideOver', 'edit-field', ['fieldSlug' => $field['slug'], 'slug' => $this->slug, 'field' => $field]);
    }

    public function getMappedFieldsProperty()
    {
        if ($this->newFields) {
            $this->updateGlobalTabs();

            return $this->newFields;
        }
        // dd($this->model->getFieldsForEdit());
        return $this->model->getFieldsForEdit();
    }

    public function mount($slug)
    {
        $this->slug = $slug;

        $this->model = Aura::findResourceBySlug($slug);

        $this->fieldsArray = $this->model->getFields();

        if (count($this->mappedFields) > 0 && $this->mappedFields[0]['type'] == "App\Aura\Fields\Tab" && $this->mappedFields[0]['global']) {
            $this->hasGlobalTabs = true;

            // Global Tabs
            collect($this->mappedFields)->each(function ($field) {
                if ($field['type'] == "App\Aura\Fields\Tab" && $field['global']) {
                    $this->globalTabs[] = [
                        'slug' => $field['slug'],
                        'name' => $field['name'],
                    ];
                }
            });
        }

        $this->postTypeFields = [
            'type' => $this->model->getType(),
            'slug' => $this->model->getSlug(),
            'icon' => $this->model->getIcon(),
        ];
    }

    public function openSidebar($fieldSlug, $slug)
    {
        // get field with fieldSlug from this fieldsarray
        $field = collect($this->fieldsArray)->where('slug', $fieldSlug)->first();

        $this->emit('openSlideOver', 'edit-field', ['fieldSlug' => $fieldSlug, 'slug' => $slug, 'field' => $field]);
    }

    public function removeConditionalLogicRule($key, $groupKey, $ruleKey)
    {
        unset($this->fields[$key]['conditional_logic'][$groupKey][$ruleKey]);

        if (count($this->fields[$key]['conditional_logic'][$groupKey]) == 0) {
            unset($this->fields[$key]['conditional_logic'][$groupKey]);
        }
    }

    public function removeField($key)
    {
        unset($this->fields[$key]);
    }

    public function render()
    {
        $title = 'Welcome';

        return view('livewire.posttype', compact('title'));
    }

    public function reorder($ids)
    {
        // $this->fields = $this->model->getFields();

        $this->validate();
        // dd('hier');

        // dd($ids);
        $ids = collect($ids)->map(function ($id) {
            return (int) Str::after($id, 'field_') - 1;
        });
        // dd($ids);

        // dd($this->model->getFields());
        $fields = array_values($this->fieldsArray);
        // dd($ids, $fields);

        $fields = $ids->map(function ($id) use ($fields) {
            return $fields[$id];
        })->toArray();

        $this->fieldsArray = $fields;

        $this->saveFields($this->fieldsArray);

        $this->newFields = $this->model->mapToGroupedFields($this->fieldsArray);
    }

    public function rules()
    {
        return [
            'postTypeFields.type' => 'required|regex:/^[a-zA-Z]+$/',
            'postTypeFields.slug' => 'required',
            'postTypeFields.icon' => 'required',
            'fields.*.name' => '',
            'fields.*.slug' => [
                'required',
                function ($attribute, $value, $fail) {
                    if (collect($this->fields)->pluck('slug')->duplicates()->values()->contains($value)) {
                        $fail('The '.$attribute.' can not be used twice.');
                    }

                    if ($value == 'id') {
                        $fail('The '.$attribute.' can not be "id".');
                    }

                    if ($value == 'title') {
                        $fail('The '.$attribute.' can not be "title".');
                    }
                },
            ],
            'fields.*.type' => '',
            'fields.*.key' => '',
            'fields.*.validation' => '',
            'fields.*.wrapper' => '',
            'fields.*.conditional_logic' => '',
            'fields.*.has_conditional_logic' => '',
        ];
    }

    public function save()
    {
        $this->validate();
        if (count($this->fields) == 0) {
            $this->fields = $this->fieldsArray;
        }
        // $this->model->save();

        $this->saveProps($this->postTypeFields);
    }

    public function saveField($data)
    {
        // code...
        // dd($data);
        // edit the field in $this->fieldsArray with the slug $data['slug'] and replace it with $data['value']
        $fields = collect($this->fieldsArray);

        $field = $fields->where('slug', $data['slug'])->first();

        $field = $data['value'];

        // $fields = $fields->map(function ($item) use ($field) {
        //     if ($item['slug'] == $field['slug']) {
        //         return $field;
        //     }
        //     return $item;
        // });

        // get index of the field with the slug $data['slug']
        $index = $fields->search(function ($item) use ($data) {
            return $item['slug'] == $data['slug'];
        });

        $this->fieldsArray[$index] = $data['value'];

        $this->saveFields($this->fieldsArray);

        $this->newFields = $this->model->mapToGroupedFields($this->fieldsArray);
    }

    public function sendField($slug)
    {
        // get field with fieldSlug from this fieldsarray
        return $field = collect($this->fieldsArray)->where('slug', $slug)->first();
    }

    public function updateFields($fields)
    {
        $this->newFields = $this->model->mapToGroupedFields($fields);
    }

    // when newFields updated
    public function updateGlobalTabs()
    {
        if ($this->hasGlobalTabs) {
            $this->globalTabs = [];

            // Global Tabs
            collect($this->newFields)->each(function ($field) {
                if ($field['type'] == "App\Aura\Fields\Tab" && $field['global']) {
                    $this->globalTabs[] = [
                        'slug' => $field['slug'],
                        'name' => $field['name'],
                    ];
                }
            });
        }
    }

    public function updatingFields($value)
    {
        // Make Sure Name is always a Slug
        foreach ($this->fields as $key => $field) {
            if (! optional($field)['slug']) {
                $this->fields[$key]['slug'] = Str::slug($field['name']);
            } else {
                $this->fields[$key]['slug'] = Str::slug($field['slug']);
            }
        }
    }
}
