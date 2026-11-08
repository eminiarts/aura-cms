<?php

namespace Aura\Base\Fields;

use Illuminate\Support\Str;

class Text extends Field
{
    public $edit = 'aura::fields.text';

    public $optionGroup = 'Input Fields';

    public $view = 'aura::fields.view-value';

    /**
     * A value that is exactly one http(s) URL renders as a link. A bare site
     * ("https://www.atlascomponents.de/") shows only its host; a URL with a
     * path, query or fragment shows everything after the scheme, shortened,
     * with the full URL in the tooltip. Anything else takes the escaped
     * scalar path.
     */
    public function display($field, $value, $model)
    {
        if (! optional($field)['display_view'] && is_string($value) && ($label = $this->urlLabel(trim($value))) !== null) {
            $url = e(trim($value));

            return "<a class='aura-link' href='{$url}' title='{$url}' target='_blank' rel='noopener noreferrer'>".e($label).'</a>';
        }

        return parent::display($field, $value, $model);
    }

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'name' => 'Text',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'text-tab',
                // 'global' => true,
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
            [
                'name' => 'Autocomplete',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'autocomplete',
            ],
            [
                'name' => 'Prefix',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'prefix',
                'style' => [
                    'width' => '50',
                ],
            ],
            [
                'name' => 'Suffix',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'suffix',
                'style' => [
                    'width' => '50',
                ],
            ],
            [
                'name' => 'Max Length',
                'type' => 'Aura\\Base\\Fields\\Number',
                'validation' => '',
                'slug' => 'max_length',
                'style' => [
                    'width' => '100',
                ],

            ],
        ]);
    }

    protected function urlLabel(string $value): ?string
    {
        if (! preg_match('~^https?://~i', $value) || filter_var($value, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        $parts = parse_url($value);
        $host = $parts['host'] ?? null;

        if (! is_string($host) || $host === '') {
            return null;
        }

        $isBareSite = in_array($parts['path'] ?? '', ['', '/'], true)
            && ! isset($parts['query'])
            && ! isset($parts['fragment'])
            && ! isset($parts['port']);

        if ($isBareSite) {
            return preg_replace('/^www\./i', '', $host);
        }

        return Str::limit(preg_replace('~^https?://~i', '', $value), 48);
    }
}
