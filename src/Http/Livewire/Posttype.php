<?php

namespace Eminiarts\Aura\Http\Livewire;

use Eminiarts\Aura;
use Illuminate\Support\Str;
use Livewire\Component;

class Posttype extends Component
{
    public $model;

    public $fields;

    public function rules()
    {
        return [
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

    public function addConditionalLogicRuleGroup($key)
    {
        // code...
        $this->fields[$key]['conditional_logic'][] = [
            ['param' => '', 'operator' => '=', 'value' => ''],
        ];
    }

    public function removeConditionalLogicRule($key, $groupKey, $ruleKey)
    {
        unset($this->fields[$key]['conditional_logic'][$groupKey][$ruleKey]);

        if (count($this->fields[$key]['conditional_logic'][$groupKey]) == 0) {
            unset($this->fields[$key]['conditional_logic'][$groupKey]);
        }
    }

    public function addConditionalLogicRule($key, $group)
    {
        $this->fields[$key]['conditional_logic'][$group][] = ['param' => '', 'operator' => '=', 'value' => ''];
    }

    public function reorder($orderedIds)
    {
        $this->fields = collect($orderedIds)->map(function ($id) {
            return collect($this->fields)->where('slug', $id)->first();
        })->toArray();
    }

    public function render()
    {
        return view('livewire.posttype');
    }

    public function mount($slug)
    {
        $this->model = Aura::findResourceBySlug($slug);
        $this->fields = $this->model->getFields();
    }

    public function addField()
    {
        $this->fields['z_'.Str::random(4)] = [
            'name' => 'Text',
            'slug' => 'text',
            'id' => rand(100, 200),
            // 'key' => Str::random(8),
            'type' => "App\Aura\Fields\Text",
            'validation' => '',
            'conditional_logic' => '',
            'has_conditional_logic' => false,
            'wrapper' => '',
            'on_index' => true,
            'on_forms' => true,
            'in_view' => true,
        ];
    }

    public function removeField($key)
    {
        unset($this->fields[$key]);
    }

    public function save()
    {
        $this->validate();

        $a = new \ReflectionClass($this->model::class);

        $file = file_get_contents($a->getFileName());

        $replacement = varexport($this->setKeysToFields($this->fields), true);

        preg_match('/function\s+getFields\s*\((?:[^()]+)*?\s*\)\s*(?<functionBody>{(?:[^{}]+|(?-1))*+})/ms', $file, $matches);

        $body = $matches['functionBody'];

        preg_match('/return (\[.*\]);/ms', $body, $matches2);

        $replaced = Str::replace(
            $matches2[1],
            $this->formatIndentation($matches2[1], $replacement),
            $file
        );

        //dd($matches[1], $replacement, $this->formatIndentation($matches[1], $replacement));

        file_put_contents($a->getFileName(), $replaced);

        $this->notify('Saved successfully.');
    }

    public function setKeysToFields($fields)
    {
        $group = null;

        return collect($fields)->mapWithKeys(function ($item, $key) use (&$group) {
            if (app($item['type'])->group) {
                $group = $item['slug'];

                return [$item['slug'] => $item];
            }

            return [$group.'.'.$item['slug'] => $item];
        })->toArray();
    }

    public function formatIndentation($str, $str2)
    {
        // Get first Line
        $line = preg_split('#\r?\n#', $str, 0)[1];

        // Get Spaces in first Line
        $count = substr_count($line, ' ');

        // Get second Line
        $line2 = preg_split('#\r?\n#', $str2, 0)[1];

        // Get Spaces in second Line
        $count2 = substr_count($line2, ' ');

        // Get the difference of Spaces
        $newSpaces = str_pad('', $count - $count2, ' ');

        // Str to Array
        $new = preg_split('#\r?\n#', $str2, 0);

        // Add Spaces to each line except the 1st
        foreach ($new as $key => $line) {
            if ($key == 0) {
                continue;
            }

            $new[$key] = $newSpaces.$line;
        }

        // Implode Array to Text
        return implode("\n", $new);
    }
}
