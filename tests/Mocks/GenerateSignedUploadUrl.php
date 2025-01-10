<?php

namespace Facades\Livewire\Features\SupportFileUploads;

use Livewire\Features\SupportFileUploads\GenerateSignedUploadUrl as BaseGenerateSignedUploadUrl;

class GenerateSignedUploadUrl extends BaseGenerateSignedUploadUrl
{
    public function forLocal()
    {
        return 'http://localhost/test-upload-url';
    }

    public function forS3($file, $visibility = '')
    {
        return [];
    }
}
