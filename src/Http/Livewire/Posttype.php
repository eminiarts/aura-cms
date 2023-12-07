<?php

namespace Eminiarts\Aura\Http\Livewire;

use Eminiarts\Aura\Facades\Aura;
use Eminiarts\Aura\Traits\HasActions;
use Eminiarts\Aura\Traits\SaveFields;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Livewire\Component;

class Posttype extends Component
{
    use AuthorizesRequests;
    use HasActions;
    use SaveFields;

    public $fields = [];

    public $fieldsArray = [];

    public $globalTabs = [];

    public $hasGlobalTabs = false;

    public $model;

    public $postTypeFields = [];

    public $reservedWords = ['id', 'type'];

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
        $children = (int) $children;
        $str = Str::random(4);
        if ($type == 'Eminiarts\\Aura\\Fields\\Tab') {
            $field = [
                'name' => 'Tab '.$str,
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'validation' => '',
                'conditional_logic' => [],
                'slug' => 'tab'.'_'.$str,
            ];
        } elseif ($type == 'Eminiarts\\Aura\\Fields\\Panel') {
            $field = [
                'name' => 'Panel '.$str,
                'type' => 'Eminiarts\\Aura\\Fields\\Panel',
                'validation' => '',
                'conditional_logic' => [],
                'slug' => 'panel'.'_'.$str,
            ];
        } else {
            $field = [
                'name' => 'Text '.$str,
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => '',
                'conditional_logic' => [],
                'slug' => 'text'.'_'.$str,
            ];
        }

        $fields = collect($this->fieldsArray);

        // get index of the field
        $index = $fields->search(function ($item) use ($slug) {
            return $item['slug'] == $slug;
        });

        // duplicate field in at index of the field + 1
        $fields->splice($index + $children + 1, 0, [$field]);

        $fields = $fields->toArray();

        $this->fieldsArray = $fields;

        $this->saveFields($this->fieldsArray);

        $this->newFields = $this->model->mapToGroupedFields($this->fieldsArray);

        // $this->emit('refreshComponent');

        $this->emit('openSlideOver', 'edit-field', ['fieldSlug' => $field['slug'], 'slug' => $this->slug, 'field' => $field]);

        $this->emit('finishedSavingFields');
    }

    public function addNewTab()
    {
        $fields = collect($this->fieldsArray);

        // check if collection has an item with type = "Eminiarts\Aura\Fields\Tab" and global = true
        $hasGlobalTabs = $fields->where('type', 'Eminiarts\Aura\Fields\Tab')->where('global', true)->count();
        $globalTab = [
            'type' => 'Eminiarts\Aura\Fields\Tab',
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

        $this->hasGlobalTabs = true;
        $this->updateGlobalTabs();

        $fields = $fields->toArray();

        $this->fieldsArray = $fields;

        $this->saveFields($this->fieldsArray);

        $this->newFields = $this->model->mapToGroupedFields($this->fieldsArray);

        // $this->emit('refreshComponent');

        $this->emit('openSlideOver', 'edit-field', ['fieldSlug' => $globalTab['slug'], 'slug' => $this->slug, 'field' => $globalTab]);

        $this->emit('finishedSavingFields');
    }

    public function addTemplateFields($slug)
    {
        $template = Aura::findTemplateBySlug($slug);

        $newFields = $template->getFields();

        // go through each field and add a random string to the slug
        foreach ($newFields as $key => $field) {
            $newFields[$key]['slug'] = $field['slug'].'_'.Str::random(4);
        }

        // check if newfields has a global tab
        $hasGlobalTab = collect($newFields)->where('type', 'Eminiarts\Aura\Fields\Tab')->where('global', true)->count();

        if ($hasGlobalTab > 0) {
            $this->hasGlobalTabs = true;
            $this->updateGlobalTabs();
        }

        $fields = collect($this->fieldsArray);

        // get index of the field
        $index = 0;

        // duplicate field in at index of the field + 1
        $fields->splice($index + 1, 0, $newFields);

        $fields = $fields->toArray();

        $this->fieldsArray = $fields;

        $this->saveFields($this->fieldsArray);

        $this->newFields = $this->model->mapToGroupedFields($this->fieldsArray);

        $this->emit('finishedSavingFields');
    }

    public function checkAuthorization()
    {
        if (config('aura.features.posttype_editor') == false) {
            abort(404);
        }

        if ($this->model->isVendorResource()) {
            abort(403, 'Only App resources can be edited.');
        }
    }

    public function countChildren($item)
    {
        $count = 0;

        if (isset($item['fields'])) {
            foreach ($item['fields'] as $child) {
                $count++;
                $count += $this->countChildren($child);
            }
        }

        return $count;
    }

    public function delete()
    {
        $a = new \ReflectionClass($this->model::class);

        // Delete file
        unlink($a->getFileName());

        $this->notify('Successfully deleted: '.$this->model->name);

        return redirect()->route('aura.dashboard');
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

        $this->emit('finishedSavingFields');
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

        $this->emit('finishedSavingFields');
    }

    public function generateMigration()
    {
        Artisan::call('aura:create-resource-migration', [
            'resource' => $this->model::class,
        ]);

        $this->notify('Successfully generated migration for: '.$this->model->name);
    }

    public function getActionsProperty()
    {
        return [
            'delete' => [
                'label' => 'Delete',
                'icon-view' => 'aura::components.actions.trash',
                'class' => 'hover:text-red-700 text-red-500 font-bold',
                'confirm' => true,
                'confirm-title' => 'Delete Posttype?',
                'confirm-content' => 'Are you sure you want to delete this Posttype?',
                'confirm-button' => 'Delete',
                'confirm-button-class' => 'ml-3 bg-red-600 hover:bg-red-700',
            ],
            'generateMigration' => [
                'label' => 'Generate Migration',
                'class' => 'hover:text-primary-700 text-primary-500 font-bold',
                'confirm' => true,
                'icon' => '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M14 20C14 21.1046 13.1046 22 12 22C10.8954 22 10 21.1046 10 20M14 20C14 18.8954 13.1046 18 12 18M14 20H21M10 20C10 18.8954 10.8954 18 12 18M10 20H3M12 18V14M21 5C21 6.65685 16.9706 8 12 8C7.02944 8 3 6.65685 3 5M21 5C21 3.34315 16.9706 2 12 2C7.02944 2 3 3.34315 3 5M21 5V11C21 12.66 17 14 12 14M3 5V11C3 12.66 7 14 12 14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
                'confirm-title' => 'Generate Resource Migration',
                'confirm-content' => 'Are you sure you want to generate a Migration? Make sure to have a look at the migration file before running it. You have to use the CustomTable trait in your resource class. Link to the documentation: <a href="https://eminiarts.com/docs/aura/resources#custom-tables">Custom Tables</a>',
                'confirm-button' => 'Generate',
            ],
        ];

    }

    public function getMappedFieldsProperty()
    {
        if ($this->newFields) {
            $this->updateGlobalTabs();

            return $this->newFields;
        }

        return $this->model->getFieldsForEdit();
    }

    public function insertTemplateFields($id, $slug, $type)
    {
        $template = Aura::findTemplateBySlug($type);
        $newFields = $template->getFields();

        // go through each field and add a random string to the slug
        foreach ($newFields as $key => $field) {
            $newFields[$key]['slug'] = $field['slug'].'_'.Str::random(4);
        }

        $fields = collect($this->fieldsArray);

        // get index of the field
        $index = $fields->search(function ($item) use ($slug) {
            return $item['slug'] == $slug;
        });

        // duplicate field in at index of the field + 1
        $fields->splice($index + 1, 0, $newFields);

        $fields = $fields->toArray();

        $this->fieldsArray = $fields;

        $this->saveFields($this->fieldsArray);

        $this->newFields = $this->model->mapToGroupedFields($this->fieldsArray);

        $this->emit('finishedSavingFields');
    }

    public function mount($slug)
    {
        $this->slug = $slug;

        $this->model = Aura::findResourceBySlug($slug);

        $this->checkAuthorization();

        // Check if fields have closures
        if ($this->model->fieldsHaveClosures($this->model->getFields())) {
            abort(403, 'Your fields have closures. You can not use the Posttype Builder with Closures.');
        }

        $this->fieldsArray = $this->model->getFields();

        if (count($this->mappedFields) > 0 && $this->mappedFields[0]['type'] == "Eminiarts\Aura\Fields\Tab" && array_key_exists('global', $this->mappedFields[0]) && $this->mappedFields[0]['global']) {
            $this->hasGlobalTabs = true;

            // Global Tabs
            collect($this->mappedFields)->each(function ($field) {
                if ($field['type'] == "Eminiarts\Aura\Fields\Tab" && $field['global']) {
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
            'group' => $this->model->getGroup(),
            'dropdown' => $this->model->getDropdown(),
            'sort' => $this->model->getSort(),
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

    public function render()
    {
        $title = 'Welcome';

        return view('aura::livewire.posttype', compact('title'))->layout('aura::components.layout.app');
    }

    public function reorder($ids)
    {
        $this->validate();

        $ids = collect($ids)->map(function ($id) {
            return (int) Str::after($id, 'field_') - 1;
        });

        // ray($ids);

        $fields = array_values($this->fieldsArray);

        $fields = $ids->map(function ($id) use ($fields) {
            return $fields[$id];
        })->toArray();

        $this->fieldsArray = $fields;

        // dd($fields);

        $this->saveFields($this->fieldsArray);

        $this->newFields = $this->model->mapToGroupedFields($this->fieldsArray);

        $this->emit('finishedSavingFields');
    }

    public function rules()
    {
        return [
            'postTypeFields.type' => 'required|regex:/^[a-zA-Z]+$/',
            'postTypeFields.slug' => 'required',
            'postTypeFields.icon' => 'required',
            'postTypeFields.group' => '',
            'postTypeFields.dropdown' => '',
            'postTypeFields.sort' => '',
            'fields.*.name' => '',
            'fields.*.slug' => [
                'required',
                function ($attribute, $value, $fail) {
                    if (collect($this->fields)->pluck('slug')->duplicates()->values()->contains($value)) {
                        $fail('The '.$attribute.' can not be used twice.');
                    }

                    // check if slug is a reserved word with "in_array"
                    if (in_array($value, $this->reservedWords)) {
                        $fail('The '.$attribute.' can not be a reserved word.');
                    }
                },
            ],
            'fields.*.type' => '',
            'fields.*.key' => '',
            'fields.*.validation' => '',
            'fields.*.wrapper' => '',
            'fields.*.conditional_logic' => '',
        ];
    }

    public function save()
    {
        $this->validate();
        if (count($this->fields) == 0) {
            $this->fields = $this->fieldsArray;
        }
        // $this->model->save();

        $fields = collect($this->fieldsArray);

        $fields = $fields->toArray();

        $this->fieldsArray = $fields;

        $this->saveFields($this->fieldsArray);

        $this->newFields = $this->model->mapToGroupedFields($this->fieldsArray);

        $this->saveProps($this->postTypeFields);
    }

    public function saveField($data)
    {
        $fields = collect($this->fieldsArray);

        // get index of the field with the slug $data['slug']
        $index = $fields->search(function ($item) use ($data) {
            return $item['slug'] == $data['slug'];
        });

        if ($index === false) {
            return;
        }

        $this->fieldsArray[$index] = $data['value'];

        $this->saveFields($this->fieldsArray);

        $this->newFields = $this->model->mapToGroupedFields($this->fieldsArray);

        ray($this->fieldsArray);

        // emit new fields
        $this->emit('newFields', $this->fieldsArray);
        $this->emit('finishedSavingFields');
    }

    public function sendField($slug)
    {
        // get field with fieldSlug from this fieldsarray
        return $field = collect($this->fieldsArray)->where('slug', $slug)->first();
    }

    public function singleAction($action)
    {
        $this->{$action}();

        $this->notify('Successfully ran: '.$action);
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
                if ($field['type'] == "Eminiarts\Aura\Fields\Tab" && $field['global']) {
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
            if (!optional($field)['slug']) {
                $this->fields[$key]['slug'] = Str::slug($field['name']);
            } else {
                $this->fields[$key]['slug'] = Str::slug($field['slug']);
            }
        }
    }
}
