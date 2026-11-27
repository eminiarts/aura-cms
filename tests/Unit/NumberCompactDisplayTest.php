<?php

use Aura\Base\Fields\Number;

test('a large figure is shortened with its unit for narrow slots', function (mixed $value, ?string $expected) {
    expect(Number::compactDisplay(['prefix' => 'CHF'], $value))->toBe($expected);
})->with([
    [84500, "CHF\u{00A0}84.5K"],
    [84000, "CHF\u{00A0}84K"],
    [1250000, "CHF\u{00A0}1.3M"],
    [9999, null],
    ['n/a', null],
]);
