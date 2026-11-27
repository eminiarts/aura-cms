<?php

namespace Aura\Base\Fields;

class Phone extends Field
{
    public $edit = 'aura::fields.phone';

    public $optionGroup = 'Input Fields';

    public $view = 'aura::fields.view-value';

    /**
     * A number on a record page renders as a tel: link in the quiet value-link style (the href keeps
     * only digits and a leading +); lists and anything without a digit take the escaped scalar path.
     */
    public function display($field, $value, $model)
    {
        if (! optional($field)['display_view'] && ! $this->index && is_string($value) && preg_match('/\d/', $value)) {
            $number = trim($value);
            $dial = preg_replace('/(?!^\+)[^\d]/', '', $number);

            return "<a class='aura-link' href='tel:".e($dial)."'>".e($number).'</a>';
        }

        return parent::display($field, $value, $model);
    }

    public function getFields()
    {
        return array_merge(parent::getFields(), []);
    }
}
