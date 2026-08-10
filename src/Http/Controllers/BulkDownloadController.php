<?php

namespace Aura\Base\Http\Controllers;

use Aura\Base\Livewire\Table\SignedBulkDownloadRequest;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class BulkDownloadController
{
    public function __invoke(string $token, SignedBulkDownloadRequest $downloads): StreamedResponse
    {
        return $downloads->resolve($token);
    }
}
