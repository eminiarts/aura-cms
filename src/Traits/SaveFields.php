<?php

namespace Aura\Base\Traits;

use Aura\Base\Events\SaveFields as SaveFieldsEvent;
use Aura\Base\Facades\Aura;
use Illuminate\Support\Str;

trait SaveFields
{
    public function formatIndentation($str, $str2)
    {
        // if (preg_split('#\r?\n#', $str, 0) !== null) {
        //     return $str2;
        // }

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

    public function saveFields($fields)
    {
        $fieldsWithIds = $fields;

        // Unset Mapping of Fields
        foreach ($fields as &$field) {
            unset($field['field']);
            unset($field['field_type']);
            unset($field['_id']);
            unset($field['_parent_id']);
        }

        $a = new \ReflectionClass($this->model::class);

        $file = file_get_contents($a->getFileName());

        $replacement = Aura::varexport($this->setKeysToFields($fields), true);

        // dd($replacement);

        preg_match('/function\s+getFields\s*\((?:[^()]+)*?\s*\)\s*(?<functionBody>{(?:[^{}]+|(?-1))*+})/ms', $file, $matches);

        $body = $matches['functionBody'];

        preg_match('/return (\[.*\]);/ms', $body, $matches2);

        $replaced = Str::replace(
            $matches2[1],
            $this->formatIndentation($matches2[1], $replacement),
            $file
        );

        // dd($matches[1], $replacement, $this->formatIndentation($matches[1], $replacement));

        file_put_contents($a->getFileName(), $replaced);

        // ray($this->mappedFields);

        // Trigger the event to change the database schema
        event(new SaveFieldsEvent($fieldsWithIds, $this->mappedFields, $this->model));

        $this->dispatch('refreshComponent');

        $this->notify('Saved successfully.');
    }

    public function saveProps($props)
    {
        $a = new \ReflectionClass($this->model::class);

        $file = file_get_contents($a->getFileName());

        $replacement = $props;

        $patterns = [
            'type' => "/type = ['\"]([^'\"]*)['\"]/",
            'group' => "/group = ['\"]([^'\"]*)['\"]/",
            'dropdown' => "/dropdown = ['\"]([^'\"]*)['\"]/",
            'sort' => '/sort = (.*?);/',
            'slug' => "/slug = ['\"]([^'\"]*)['\"]/",
            'icon' => "/public function getIcon\(\)[\n\r\s+]*\{[\n\r\s+]*return ['\"](.*?)['\"];/",
        ];

        $replacements = [
            'type' => "type = '".htmlspecialchars($replacement['type'])."'",
            'group' => "group = '".htmlspecialchars($replacement['group'])."'",
            'dropdown' => "dropdown = '".htmlspecialchars($replacement['dropdown'])."'",
            'sort' => 'sort = '.htmlspecialchars($replacement['sort']).';',
            'slug' => "slug = '".htmlspecialchars($replacement['slug'])."'",
            'icon' => "public function getIcon()\n    {\n        return '".($replacement['icon'])."';",
        ];

        $replaced = $file;

        $matches = [];
        foreach ($patterns as $key => $pattern) {
            preg_match($pattern, $file, $matches[$key]);
        }

        foreach ($patterns as $key => $pattern) {

            if ($key == 'icon') {
                // dump($replacements[$key]);
                $replaced = preg_replace($pattern, strip_tags($replacements[$key], '<a><altGlyph><altGlyphDef><altGlyphItem><animate><animateColor><animateMotion><animateTransform><circle><clipPath><color-profile><cursor><defs><desc><ellipse><feBlend><feColorMatrix><feComponentTransfer><feComposite><feConvolveMatrix><feDiffuseLighting><feDisplacementMap><feDistantLight><feFlood><feFuncA><feFuncB><feFuncG><feFuncR><feGaussianBlur><feImage><feMerge><feMergeNode><feMorphology><feOffset><fePointLight><feSpecularLighting><feSpotLight><feTile><feTurbulence><filter><font><font-face><font-face-format><font-face-name><font-face-src><font-face-uri><foreignObject><g><glyph><glyphRef><hkern><image><line><linearGradient><marker><mask><metadata><missing-glyph><mpath><path><pattern><polygon><polyline><radialGradient><rect><set><stop><style nonce="{{ csp_nonce() }}"><svg><switch><symbol><text><textPath><title><tref><tspan><use><view><vkern>'), $replaced);

                continue;
            }

            if (in_array($key, ['group', 'dropdown', 'sort'])) {

                if (isset($replacement[$key])) {
                    if (isset($matches[$key][1]) || (isset($matches[$key][0]) && $matches[$key][0] == "''")) {
                        // Replace existing line
                        $replaced = Str::replace(
                            $matches[$key][1],
                            htmlspecialchars($replacement[$key]),
                            $replaced
                        );
                    } else {

                        // Don't add empty lines
                        if (empty(htmlspecialchars($replacement[$key]))) {
                            continue;
                        }

                        // Add missing line
                        // if sort then add ?int instead of ?string
                        if ($key == 'sort') {
                            $lineToAdd = "protected static ?int \${$key} = ".htmlspecialchars($replacement[$key]).";\n";
                        } else {
                            $lineToAdd = "protected static ?string \${$key} = '".htmlspecialchars($replacement[$key])."';\n";
                        }
                        $replaced = preg_replace('/(public\s+static\s+\?string\s+\$slug\s+=\s+[^;\n]+;)/', "$1\n{$lineToAdd}", $replaced);
                    }
                }

                continue;
            }

            if (preg_match($pattern, $file) && isset($replacements[$key])) {
                $replaced = preg_replace($pattern, $replacements[$key], $replaced);
            }
        }

        file_put_contents($a->getFileName(), $replaced);

        // Run "pint" on the migration file
        exec('./vendor/bin/pint '.$a->getFileName());

        // $this->notify('Saved Props successfully.');
    }

    public function setKeysToFields($fields)
    {
        $group = null;

        return $fields;

        return collect($fields)->mapWithKeys(function ($item, $key) use (&$group) {
            if (app($item['type'])->group) {
                $group = $item['slug'];

                return [$item['slug'] => $item];
            }

            return [$group.'.'.$item['slug'] => $item];
        })->toArray();
    }
}
