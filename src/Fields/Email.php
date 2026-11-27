<?php

namespace Aura\Base\Fields;

class Email extends Field
{
    public $edit = 'aura::fields.email';

    public $optionGroup = 'Input Fields';

    public $view = 'aura::fields.view-value';

    /**
     * A valid address renders as a mailto link in the quiet value-link style;
     * anything else takes the escaped scalar path.
     */
    public function display($field, $value, $model)
    {
        if (! optional($field)['display_view'] && ! $this->index && is_string($value) && filter_var(trim($value), FILTER_VALIDATE_EMAIL)) {
            $email = e(trim($value));

            return "<a class='aura-link' href='mailto:{$email}'>{$email}</a>";
        }

        return parent::display($field, $value, $model);
    }

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'label' => 'Email',
                'name' => 'Email',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'email-tab',
                'style' => [],
            ],
            [
                'name' => 'Default Value',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'instructions' => 'Default value on create',
                'slug' => 'default',
            ],
            [
                'name' => 'Placeholder',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'placeholder',
            ],
        ]);
    }
}
