<?php

test('non super admin cannot render plugins page', function () {
    $this->actingAs(createAdmin());

    $this->get(route('aura.plugins'))->assertForbidden();
});

test('super admin can render plugins page', function () {
    $this->actingAs(createSuperAdmin());

    $this->get(route('aura.plugins'))
        ->assertOk()
        ->assertSee('composer require vendor/plugin');
});
