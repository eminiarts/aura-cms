<?php

use Aura\Base\Tests\Resources\EmbeddedComponentPage;

test('embedded field stays console clean on every resource surface', function () {
    $this->actingAs(createGlobalAdmin());

    $resource = EmbeddedComponentPage::create(['title' => 'Browser owner']);

    $create = visit('/admin/embedded-component-page/create');
    $create->assertSee('edit:new')
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();

    $edit = visit('/admin/embedded-component-page/'.$resource->getKey().'/edit');
    $edit->assertSee('edit:'.$resource->getKey())
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();

    $view = visit('/admin/embedded-component-page/'.$resource->getKey());
    $view->assertSee('view:'.$resource->getKey())
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();

    $index = visit('/admin/embedded-component-page');
    $index->assertSee('index:'.$resource->getKey())
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();
});
