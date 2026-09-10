<?php

namespace Aura\Base\Traits;

use Aura\Base\Events\SaveFields as SaveFieldsEvent;
use Aura\Base\Facades\Aura;
use Illuminate\Support\Str;
use ReflectionClass;
use RuntimeException;
use Throwable;

trait SaveFields
{
    public function saveFields($fields)
    {
        $fieldsWithIds = $fields;

        foreach ($fields as &$field) {
            unset($field['field'], $field['field_type'], $field['_id'], $field['_parent_id']);
        }
        unset($field);

        $filePath = (new ReflectionClass($this->model::class))->getFileName();

        if ($filePath === false || ! is_file($filePath)) {
            throw new RuntimeException('The resource definition file could not be found.');
        }

        $file = file_get_contents($filePath);

        if ($file === false) {
            throw new RuntimeException('The resource definition file could not be read.');
        }

        preg_match('/function\s+getFields\s*\((?:[^()]*?)\s*\)\s*(?::\s*array\s*)?(?<functionBody>{(?:[^{}]+|(?-1))*+})/ms', $file, $matches, PREG_OFFSET_CAPTURE);

        if (! isset($matches['functionBody'])) {
            throw new RuntimeException('The Resource Editor could not locate getFields().');
        }

        [$functionBody, $functionBodyOffset] = $matches['functionBody'];
        preg_match('/return\s+(\[.*\]);/ms', $functionBody, $return);

        if (! isset($return[1])) {
            throw new RuntimeException('getFields() must return an array literal for Resource Editor changes.');
        }

        $replacement = Aura::varexport($this->setKeysToFields($fields), true);
        $newFunctionBody = Str::replace($return[1], $replacement, $functionBody);
        $newFile = substr_replace($file, $newFunctionBody, $functionBodyOffset, strlen($functionBody));
        token_get_all($newFile, TOKEN_PARSE);

        try {
            if (file_put_contents($filePath, $newFile) === false) {
                throw new RuntimeException('The resource definition file could not be saved.');
            }

            event(new SaveFieldsEvent($fieldsWithIds, $this->mappedFields, $this->model));
        } catch (Throwable $exception) {
            // A rejected schema change must not leave the class expecting new columns.
            file_put_contents($filePath, $file);

            throw $exception;
        }

        $this->notify('Saved successfully.');
    }

    public function saveProps($props)
    {
        $a = new ReflectionClass($this->model::class);

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

    }

    public function setKeysToFields($fields)
    {
        return $fields;
    }
}
