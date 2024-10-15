<?php

namespace Aura\Base\Traits;

use Aura\Base\Facades\Aura;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Symfony\Component\Process\ExecutableFinder;
use Aura\Base\Events\SaveFields as SaveFieldsEvent;

trait SaveFields
{
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

        $filePath = $a->getFileName();

        if (file_exists($filePath)) {
            $file = file_get_contents($filePath);

            $replacement = Aura::varexport($this->setKeysToFields($fields), true);

            preg_match('/function\s+getFields\s*\((?:[^()]*?)\s*\)\s*(?<functionBody>{(?:[^{}]+|(?-1))*+})/ms', $file, $matches, PREG_OFFSET_CAPTURE);

            if (isset($matches['functionBody'])) {
                $functionBody = $matches['functionBody'][0];
                $functionBodyOffset = $matches['functionBody'][1];

                preg_match('/return\s+(\[.*\]);/ms', $functionBody, $matches2);

                if (isset($matches2[1])) {

                    $newFunctionBody = Str::replace(
                        $matches2[1],
                        $replacement,
                        $functionBody
                    );

                    $newFile = substr_replace(
                        $file,
                        $newFunctionBody,
                        $functionBodyOffset,
                        strlen($functionBody)
                    );

                    file_put_contents($filePath, $newFile);

                    $this->runPint($filePath);
                } else {
                    // Handle the case where the return statement is not found
                    // You may want to add the return statement if it's missing
                    // For now, we'll notify that the return statement was not found
                    $this->notify('Return statement not found in getFields().');
                }
            } else {
                // Handle the case where getFields() function is not found
                $this->notify('Function getFields() not found.');
            }
        }

        // Trigger the event to change the database schema
        event(new SaveFieldsEvent($fieldsWithIds, $this->mappedFields, $this->model));

        // $this->dispatch('refreshComponent');

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
        // exec('./vendor/bin/pint '.$a->getFileName());

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

    protected function runPint($migrationFile)
    {
        return;

        $command = [
            (new ExecutableFinder)->find('php', 'php', [
                '/usr/local/bin',
                '/opt/homebrew/bin',
            ]),

            'vendor/bin/pint', $migrationFile,
        ];

        $result = Process::path(base_path())->run($command);
    }
}
