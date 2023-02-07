<?php

namespace App\Aura\Traits;

use Illuminate\Support\Str;

trait SaveFields
{
    public function formatIndentation($str, $str2)
    {
        if (preg_split('#\r?\n#', $str, 0) !== null || count(preg_split('#\r?\n#', $str, 0)) == 0) {
            return $str2;
        }
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
        $a = new \ReflectionClass($this->model::class);

        $file = file_get_contents($a->getFileName());

        $replacement = varexport($this->setKeysToFields($fields), true);

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

        $this->notify('Saved successfully.');
    }

    public function saveProps($props)
    {
        $a = new \ReflectionClass($this->model::class);

        $file = file_get_contents($a->getFileName());

        $replacement = $props;

        $matches = [];

        preg_match("/type = '(.*?)'/", $file, $matches['type']);
        preg_match("/slug = '(.*?)'/", $file, $matches['slug']);

        preg_match("/public function getIcon\(\)[\n\r\s+]*\{[\n\r\s+]*return '(.*?)';/", $file, $matches['icon']);

        // dd($matches);

        $replaced = Str::replace(
            $matches['type'][1],
            htmlspecialchars($replacement['type']),
            $file
        );

        $replaced = Str::replace(
            $matches['slug'][1],
            htmlspecialchars($replacement['slug']),
            $replaced
        );

        $replaced = Str::replace(
            $matches['icon'][1],
            strip_tags(Str::replace('\'', '"', $replacement['icon']), '<a><altGlyph><altGlyphDef><altGlyphItem><animate><animateColor><animateMotion><animateTransform><circle><clipPath><color-profile><cursor><defs><desc><ellipse><feBlend><feColorMatrix><feComponentTransfer><feComposite><feConvolveMatrix><feDiffuseLighting><feDisplacementMap><feDistantLight><feFlood><feFuncA><feFuncB><feFuncG><feFuncR><feGaussianBlur><feImage><feMerge><feMergeNode><feMorphology><feOffset><fePointLight><feSpecularLighting><feSpotLight><feTile><feTurbulence><filter><font><font-face><font-face-format><font-face-name><font-face-src><font-face-uri><foreignObject><g><glyph><glyphRef><hkern><image><line><linearGradient><marker><mask><metadata><missing-glyph><mpath><path><pattern><polygon><polyline><radialGradient><rect><set><stop><style><svg><switch><symbol><text><textPath><title><tref><tspan><use><view><vkern>'),
            $replaced
        );

        // dd('saveProps', $file, $replaced);

        // dd($matches[1], $replacement, $this->formatIndentation($matches[1], $replacement));

        file_put_contents($a->getFileName(), $replaced);

        $this->notify('Saved Props successfully.');
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
