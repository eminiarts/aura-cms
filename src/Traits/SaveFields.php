<?php

namespace Aura\Base\Traits;

use Aura\Base\Events\SaveFields as SaveFieldsEvent;
use Aura\Base\Facades\Aura;
use Aura\Base\Schema\SchemaMigrationLock;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use RuntimeException;
use Throwable;

trait SaveFields
{
    #[Locked]
    public ?string $resourceFieldsVersion = null;

    public function initializeResourceFieldsVersion(): void
    {
        $filePath = $this->resourceFieldsFilePath();
        $contents = file_get_contents($filePath);

        if ($contents === false) {
            throw new RuntimeException("Unable to read resource file [{$filePath}].");
        }

        $this->resourceFieldsVersion = hash('sha256', $contents);
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

        $filePath = $this->resourceFieldsFilePath();
        SchemaMigrationLock::runForTable($this->model->getTable(), function () use ($fields, $fieldsWithIds, $filePath): void {
            $this->saveFieldsWhileLocked($fields, $fieldsWithIds, $filePath);
        });

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

    private function resourceFieldsFilePath(): string
    {
        $reflection = new \ReflectionClass($this->model::class);
        $filePath = $reflection->getFileName();

        if (! is_string($filePath) || ! is_file($filePath)) {
            throw new RuntimeException('Unable to locate the resource file for field configuration.');
        }

        return $filePath;
    }

    /**
     * @param  array<int, array<string, mixed>>  $fields
     * @param  array<int, array<string, mixed>>  $fieldsWithIds
     */
    private function saveFieldsWhileLocked(array $fields, array $fieldsWithIds, string $filePath): void
    {
        $originalFile = file_get_contents($filePath);

        if ($originalFile === false) {
            throw new RuntimeException("Unable to read resource file [{$filePath}].");
        }

        $currentVersion = hash('sha256', $originalFile);

        if ($this->resourceFieldsVersion !== null && ! hash_equals($this->resourceFieldsVersion, $currentVersion)) {
            throw new RuntimeException('Resource fields changed since this editor was opened. Refresh the editor before saving again.');
        }

        $replacement = Aura::varexport($this->setKeysToFields($fields), true);
        preg_match('/function\s+getFields\s*\((?:[^()]*?)\s*\)\s*(?::\s*[?\\\\\w|&]+)?\s*(?<functionBody>{(?:[^{}]+|(?-1))*+})/ms', $originalFile, $matches, PREG_OFFSET_CAPTURE);

        if (! isset($matches['functionBody'])) {
            $this->notify('Function getFields() not found.');

            throw new RuntimeException('Function getFields() not found.');
        }

        $functionBody = $matches['functionBody'][0];
        $functionBodyOffset = $matches['functionBody'][1];
        preg_match('/return\s+(\[.*\]);/ms', $functionBody, $returnMatches);

        if (! isset($returnMatches[1])) {
            $this->notify('Return statement not found in getFields().');

            throw new RuntimeException('Return statement not found in getFields().');
        }

        $newFunctionBody = Str::replace($returnMatches[1], $replacement, $functionBody);
        $newFile = substr_replace($originalFile, $newFunctionBody, $functionBodyOffset, strlen($functionBody));

        $this->writeResourceFieldsFile($filePath, $newFile);

        try {
            event(new SaveFieldsEvent($fieldsWithIds, $this->mappedFields, $this->model));
        } catch (Throwable $exception) {
            try {
                $this->writeResourceFieldsFile($filePath, $originalFile);
            } catch (Throwable $restoreException) {
                throw new RuntimeException(
                    "Unable to restore resource file [{$filePath}] after a failed migration: {$restoreException->getMessage()}",
                    previous: $exception,
                );
            }

            throw $exception;
        }

        $this->resourceFieldsVersion = hash('sha256', $newFile);
    }

    private function writeResourceFieldsFile(string $filePath, string $contents): void
    {
        $temporaryPath = tempnam(dirname($filePath), '.aura-fields-');

        if ($temporaryPath === false) {
            throw new RuntimeException("Unable to create a temporary resource file beside [{$filePath}].");
        }

        try {
            if (file_put_contents($temporaryPath, $contents, LOCK_EX) === false) {
                throw new RuntimeException("Unable to write temporary resource file [{$temporaryPath}].");
            }

            $permissions = fileperms($filePath);

            if ($permissions !== false) {
                chmod($temporaryPath, $permissions & 0777);
            }

            if (! rename($temporaryPath, $filePath)) {
                throw new RuntimeException("Unable to replace resource file [{$filePath}].");
            }
        } finally {
            if (is_file($temporaryPath)) {
                unlink($temporaryPath);
            }
        }
    }
}
