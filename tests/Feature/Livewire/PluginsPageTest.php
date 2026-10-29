<?php

test('non super admin cannot render plugins page', function () {
    $this->actingAs(createAdmin());

    $this->get(route('aura.global.plugins'))->assertForbidden();
});

test('global admin can render plugins page', function () {
    $this->actingAs(createGlobalAdmin());

    $this->get(route('aura.global.plugins'))
        ->assertOk()
        ->assertSee('composer require vendor/plugin');
});
