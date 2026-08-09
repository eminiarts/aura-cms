<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Tests\Resources\TemporalPickerPage;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->actingAs(createSuperAdmin());

    Aura::registerResources([TemporalPickerPage::class]);
    Aura::registerRoutes('temporal-picker-page');
    Aura::captureBaselineState();
});

test('legacy temporal payloads stay inert and intact in Alpine picker data', function () {
    $datePayload = "', [(window.__auraDateXss=1)]: '";
    $datetimePayload = "Quotes '\"; </script>; &quot; &amp;; \\\\; Zürich 雪; {\"nested\":[\"</script>\",\"'\"]}";
    $resource = TemporalPickerPage::create([
        'title' => 'Temporal payloads',
        'legacy_date' => '2026-08-09',
        'legacy_datetime' => '2026-08-09 12:30:00',
    ]);

    DB::table('meta')
        ->where('metable_id', $resource->id)
        ->where('key', 'legacy_date')
        ->update(['value' => $datePayload]);
    DB::table('meta')
        ->where('metable_id', $resource->id)
        ->where('key', 'legacy_datetime')
        ->update(['value' => $datetimePayload]);

    $page = visit('/admin/temporal-picker-page/'.$resource->id.'/edit');

    $page->assertSee('Legacy date')->wait(2);

    expect($page->script('typeof window.__auraDateXss'))->toBe('undefined');

    $serializedOptions = $page->script(<<<'JS'
        JSON.stringify(
            Array.from(document.querySelectorAll('[data-aura-datetime-picker]'))
                .map((element) => Alpine.$data(element).pickerOptions)
        )
    JS);
    $options = json_decode((string) $serializedOptions, true, flags: JSON_THROW_ON_ERROR);

    expect($options)->toHaveCount(2)
        ->and($options[0]['defaultDate'])->toBe($datePayload)
        ->and($options[0]['locale'])->toBe(['firstDayOfWeek' => 1])
        ->and($options[1]['defaultDate'])->toBe($datetimePayload)
        ->and($options[1]['locale'])->toBe(['firstDayOfWeek' => 1]);

    $page->assertNoSmoke();
});
