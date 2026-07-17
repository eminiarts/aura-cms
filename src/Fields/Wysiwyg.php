<?php

namespace Aura\Base\Fields;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

class Wysiwyg extends Field
{
    public $edit = 'aura::fields.wysiwyg';

    public $optionGroup = 'JS Fields';

    public $tableColumnType = 'text';

    public $view = 'aura::fields.view-value';

    public function display($field, $value, $model)
    {
        if (! is_string($value)) {
            return $value;
        }

        $sanitizer = new HtmlSanitizer(
            (new HtmlSanitizerConfig)->allowSafeElements()
        );

        return $sanitizer->sanitize($value);
    }
}
