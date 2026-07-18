<?php

namespace Aura\Base\Tests\Browser\Support;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

/**
 * Pest's in-process browser HTTP server hands multipart POST bodies to the
 * framework unparsed (files are a documented gap in its request bridge).
 * This test-only middleware parses the raw body and populates the request's
 * files and fields so Livewire file uploads work in browser tests.
 */
class ParseMultipartBody
{
    public function handle(Request $request, Closure $next)
    {
        $contentType = (string) $request->headers->get('content-type', '');

        if ($request->isMethod('POST')
            && str_starts_with(strtolower($contentType), 'multipart/form-data')
            && $request->files->count() === 0
            && preg_match('/boundary="?([^";]+)"?/', $contentType, $matches)
            && ($content = $request->getContent()) !== '') {
            [$fields, $files] = $this->parse($content, $matches[1]);

            $request->request->add($fields);
            $request->files->add($files);
        }

        return $next($request);
    }

    private static function assign(array &$target, string $name, mixed $value): void
    {
        if (preg_match('/^([^\[]+)\[([^\]]*)\]$/', $name, $matches)) {
            if ($matches[2] === '') {
                $target[$matches[1]][] = $value;
            } else {
                $target[$matches[1]][$matches[2]] = $value;
            }

            return;
        }

        $target[$name] = $value;
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    private function parse(string $content, string $boundary): array
    {
        $fields = [];
        $files = [];

        foreach (explode('--'.$boundary, $content) as $part) {
            $part = ltrim($part, "\r\n");

            if ($part === '' || str_starts_with($part, '--')) {
                continue;
            }

            [$rawHeaders, $body] = explode("\r\n\r\n", $part, 2) + [1 => ''];
            $body = substr($body, 0, -2);

            $headers = [];
            foreach (explode("\r\n", $rawHeaders) as $line) {
                if (str_contains($line, ':')) {
                    [$key, $value] = explode(':', $line, 2);
                    $headers[strtolower(trim($key))] = trim($value);
                }
            }

            $disposition = $headers['content-disposition'] ?? '';

            if (! preg_match('/\bname="([^"]*)"/', $disposition, $nameMatch)) {
                continue;
            }

            if (preg_match('/\bfilename="([^"]*)"/', $disposition, $fileMatch)) {
                $tmpPath = tempnam(sys_get_temp_dir(), 'browser-upload');
                file_put_contents($tmpPath, $body);

                $file = new UploadedFile(
                    $tmpPath,
                    $fileMatch[1],
                    $headers['content-type'] ?? null,
                    UPLOAD_ERR_OK,
                    true,
                );

                self::assign($files, $nameMatch[1], $file);
            } else {
                self::assign($fields, $nameMatch[1], $body);
            }
        }

        return [$fields, $files];
    }
}
