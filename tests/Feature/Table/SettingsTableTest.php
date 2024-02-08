<?php

use Aura\Base\Livewire\Table\Table;
use Aura\Base\Models\User;
use Aura\Base\Resources\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

// Before each test, create a Superadmin and login
beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

test('check default table settings', function () {
})->todo();

test('table settings can be modified', function () {
})->todo();

test('header settings', function () {
})->todo();

test('actions settings', function () {
})->todo();

test('create settings', function () {
})->todo();

test('filters settings', function () {
})->todo();

test('selectable settings', function () {
})->todo();

test('table_before settings', function () {
})->todo();

test('header_before settings', function () {
})->todo();

test('search settings', function () {
})->todo();

test('table columns settings', function () {
})->todo();

test('global_filters settings', function () {
})->todo();

test('sort_columns settings', function () {
})->todo();

test('views settings', function () {
})->todo();

test('default_view settings', function () {
})->todo();

test('sort_columns_key settings', function () {
})->todo();

test('title settings', function () {
})->todo();

test('attach settings', function () {
})->todo();
