<?php

namespace Aura\Base\Tests\Fixtures;

class GlobalSearchProcessDescriptorProbeResource extends GlobalSearchProcessResource
{
    public static ?string $slug = 'process-search-descriptor-probe';

    public static string $type = 'ProcessSearchDescriptorProbe';

    public static function getGlobalSearch()
    {
        $descriptors = collect([
            ...glob('/proc/self/fd/*') ?: [],
            ...glob('/dev/fd/*') ?: [],
        ])->map(fn (string $path): int => (int) basename($path))
            ->filter(fn (int $descriptor): bool => $descriptor > 2)
            ->unique();

        foreach ($descriptors as $descriptor) {
            $stream = @fopen("php://fd/{$descriptor}", 'r');

            if (! is_resource($stream)) {
                continue;
            }

            $status = fstat($stream);
            $isRegularFile = is_array($status) && (($status['mode'] ?? 0) & 0170000) === 0100000;
            $contents = $isRegularFile ? @stream_get_contents($stream, 256) : false;
            fclose($stream);

            if (is_string($contents) && str_contains($contents, '"name": "eminiarts/aura-cms"')) {
                file_put_contents((string) getenv('AURA_GLOBAL_SEARCH_HOOK_MARKER'), "inherited-fd-{$descriptor}");

                break;
            }
        }

        return true;
    }
}
