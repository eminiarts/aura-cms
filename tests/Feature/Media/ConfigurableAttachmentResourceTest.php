<?php

use Aura\Base\Fields\Image;
use Aura\Base\Resources\Attachment;

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

/**
 * The Image/File field blades and Aura\Base\Fields\Image::display() resolve the
 * attachment class from config('aura.resources.attachment', Attachment::class).
 * These tests lock that contract: the default stays Attachment, and a
 * configured subclass is honoured end-to-end.
 */
test('attachment resource resolution defaults to Attachment when no config override', function () {
    // The package ships aura.resources.attachment => Attachment::class.
    expect(config('aura.resources.attachment', Attachment::class))
        ->toBe(Attachment::class);

    // With the key removed entirely, the supplied fallback still applies.
    config(['aura.resources' => array_diff_key(
        config('aura.resources'),
        ['attachment' => null]
    )]);

    expect(config('aura.resources.attachment', Attachment::class))
        ->toBe(Attachment::class);
});

test('a configured attachment subclass is used for resolution', function () {
    config(['aura.resources.attachment' => ConfiguredAttachment::class]);

    expect(config('aura.resources.attachment', Attachment::class))
        ->toBe(ConfiguredAttachment::class);

    $attachment = ConfiguredAttachment::create([
        'name' => 'Configured',
        'url' => 'configured.png',
        'mime_type' => 'image/png',
        'size' => 1234,
    ]);

    $class = config('aura.resources.attachment', Attachment::class);

    expect($class::find($attachment->id))
        ->toBeInstanceOf(ConfiguredAttachment::class);
});

test('Image::display renders through the configured attachment class', function () {
    config(['aura.resources.attachment' => ConfiguredAttachment::class]);

    $attachment = ConfiguredAttachment::create([
        'name' => 'Configured',
        'url' => 'configured.png',
        'mime_type' => 'image/png',
        'size' => 1234,
    ]);

    $field = ['slug' => 'image', 'name' => 'Image'];

    $html = (new Image)->display($field, $attachment->id, $attachment);

    // The sentinel proves display() routed the lookup + thumbnail() through the
    // configured subclass rather than the hard-coded Attachment resource.
    expect($html)->toContain('SENTINEL-THUMB/'.$attachment->id);
});

class ConfiguredAttachment extends Attachment
{
    public function thumbnail(?string $size = 'sm'): string
    {
        return 'SENTINEL-THUMB/'.$this->id;
    }
}
