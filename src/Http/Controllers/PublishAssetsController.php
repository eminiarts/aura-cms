<?php

namespace Aura\Base\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;

class PublishAssetsController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        abort_unless(app()->environment('local'), 404);

        if (Artisan::call('aura:publish') !== 0) {
            return back()->with('aura.assets-publish-error', __('Publishing failed. Run the command in your terminal for details.'));
        }

        return back();
    }
}
