<?php

use Eminiarts\Aura\Livewire\Table\Table;
use Eminiarts\Aura\Models\User;
use Eminiarts\Aura\Resources\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

// Before each test, create a Superadmin and login
beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

test('check default table settings', function () {
});

test('table settings can be modified', function () {
});

test('header settings', function () {
});

test('actions settings', function () {
});

test('create settings', function () {
});

test('filters settings', function () {
});

test('selectable settings', function () {
});

test('table_before settings', function () {
});

test('header_before settings', function () {
});

test('search settings', function () {
});

test('table columns settings', function () {
});

test('global_filters settings', function () {
});

test('sort_columns settings', function () {
});

test('views settings', function () {
});

test('default_view settings', function () {
});

test('sort_columns_key settings', function () {
});

test('title settings', function () {
});

test('attach settings', function () {
});
