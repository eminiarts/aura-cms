<?php

test('the dark theme survives a wire:navigate page change', function () {
    config(['aura.theme.darkmode-type' => 'dark']);

    $this->actingAs(createSuperAdmin());

    $page = visit('/admin/attachment');

    expect($page->script("document.documentElement.classList.contains('dark')"))->toBeTrue();

    $page->script("Livewire.navigate('/admin/user')");
    $page->wait(2);

    expect($page->script('location.pathname'))->toBe('/admin/user')
        ->and($page->script("document.documentElement.classList.contains('dark')"))->toBeTrue();
});
