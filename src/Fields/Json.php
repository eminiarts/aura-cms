<?php

namespace Aura\Base\Fields;

use Illuminate\Support\Str;

class Json extends Field
{
    public $edit = 'aura::fields.json';

    public $view = 'aura::fields.json-view';

    /**
     * Convert validated JSON editor text back to its native PHP value.
     */
    public function dehydrate($value, $field = null)
    {
        if (! is_string($value)) {
            return $value;
        }

        if (trim($value) === '') {
            return;
        }

        // Fields without a `json` rule can reach this with invalid text; store it unchanged.
        $decoded = json_decode($value, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
    }

    public function display($field, $value, $model)
    {
        // The encoded payload is database-backed and view-value renders this
        // raw, so neutralise stored markup. ENT_NOQUOTES: the JSON quotes are
        // text content here, not an attribute value, and escaping them would
        // only make the rendered payload unreadable.
        $json = json_encode(
            $value,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
        );

        return htmlspecialchars($json === false ? '' : $json, ENT_NOQUOTES, 'UTF-8', false);
    }

    public function get($class, $value, $field = null)
    {
        if (optional($field)['decode'] === false || is_array($value) || $value === null) {
            return $value;
        }

        return json_decode($value, true);
    }

    /**
     * Wrap the tokens of display() output in syntax highlighting spans.
     *
     * Expects JSON that display() has already escaped: it contains no raw
     * markup, so only the wrapping spans are added here.
     */
    public static function highlight(string $json): string
    {
        return preg_replace_callback(
            '/("(?:[^"\\\\]|\\\\.)*")(\s*:)?|\b(true|false)\b|\b(null)\b|(-?\d+(?:\.\d+)?(?:[eE][+-]?\d+)?)/',
            function (array $match) {
                [$token, $class] = match (true) {
                    ($match[2] ?? '') !== '' => [$match[1], 'text-primary-700 dark:text-primary-400'],
                    $match[1] !== '' => [$match[1], 'text-green-700 dark:text-green-400'],
                    ($match[3] ?? '') !== '' => [$match[3], 'text-purple-600 dark:text-purple-400'],
                    ($match[4] ?? '') !== '' => [$match[4], 'text-gray-500 dark:text-gray-400'],
                    default => [$match[5], 'text-blue-600 dark:text-blue-400'],
                };

                return '<span class="'.$class.'">'.$token.'</span>'.($match[2] ?? '');
            },
            $json
        ) ?? $json;
    }

    public function hydrate($value, $field = null)
    {
        $json = json_encode(
            $value,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
        );

        return $json === false ? '' : $json;
    }

    /**
     * A JSON value as one mono line for lists: a string as it is, anything else as compact JSON, cut at
     * $limit characters, the full text (up to 500) in the tooltip. Escaped. Null for a field with its own
     * display (closure or display_view), which keeps it. A field with 'list_summary' => true shows summary()
     * in words instead, the JSON still in the tooltip.
     */
    public static function listPreview(array $field, $model, int $limit = 60): ?string
    {
        if (isset($field['display']) || isset($field['display_view'])) {
            return null;
        }

        $value = $model->{$field['slug']};

        if ($value === null || $value === '') {
            return null;
        }

        $text = is_string($value)
            ? $value
            : (string) json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

        if ($field['list_summary'] ?? false) {
            return '<span class="block max-w-md text-sm text-gray-600 truncate dark:text-gray-300" title="'.e(Str::limit($text, 500)).'">'
                .e(static::summary($value)).'</span>';
        }

        return '<span class="block max-w-md font-mono text-xs text-gray-600 truncate dark:text-gray-400" title="'.e(Str::limit($text, 500)).'">'
            .e(Str::limit($text, $limit)).'</span>';
    }

    // public function getFields()
    // {
    //     return array_merge(parent::getFields(), [
    //         [
    //             'label' => 'JSON',
    //             'name' => 'JSON',
    //             'type' => 'Aura\\Base\\Fields\\Tab',
    //             'slug' => 'json',
    //             'style' => [],
    //         ],
    //         [
    //             'label' => 'Language',
    //             'name' => 'Language',
    //             'type' => 'Aura\\Base\\Fields\\Select',
    //             'validation' => 'required',
    //             'slug' => 'language',
    //             'options' => [
    //                 'html' => 'HTML',
    //                 'css' => 'CSS',
    //                 'javascript' => 'JavaScript',
    //                 'php' => 'PHP',
    //                 'json' => 'JSON',
    //                 'yaml' => 'YAML',
    //                 'markdown' => 'Markdown',
    //             ],
    //         ],
    //     ]);
    // }

    public function set($post, $field, $value)
    {
        if (is_array($value)) {
            return json_encode($value);
        }

        return $value;
    }

    /**
     * A stored value in words, for lists of settings: [] or {} is "Empty", true/false "On"/"Off", an object
     * "3 settings", a list "3 items", a string or number as it is. A string holding JSON is read as that JSON.
     */
    public static function summary($value): string
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
        }

        return match (true) {
            $value === null, $value === '', $value === [] => __('Empty'),
            $value === true => __('On'),
            $value === false => __('Off'),
            is_array($value) && array_is_list($value) => trans_choice(':count item|:count items', count($value), ['count' => count($value)]),
            is_array($value) => trans_choice(':count setting|:count settings', count($value), ['count' => count($value)]),
            default => (string) $value,
        };
    }
}
