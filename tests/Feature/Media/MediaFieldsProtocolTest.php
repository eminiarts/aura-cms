<?php

use Aura\Base\Fields\Image;
use Aura\Base\Livewire\Media\MediaOwnerTokenBroker;
use Aura\Base\Livewire\Media\MediaSelectionBroker;
use Aura\Base\Resources\Attachment;
use Aura\Base\Tests\Resources\Post;
use Aura\Base\Traits\MediaFields;
use Livewire\Component;
use Livewire\Livewire;

class Core20MediaOwnerHarness extends Component
{
    use MediaFields;

    public int $applications = 0;

    public array $form = ['fields' => ['image' => []]];

    public Post $model;

    public string $ownerTokenForTest = '';

    public function mount(): void
    {
        $this->model = new Post;
    }

    public function render(): string
    {
        $this->ownerTokenForTest = $this->mediaOwnerToken('image');

        return '<div>owner</div>';
    }

    public function updateField($data): void
    {
        $this->applications++;
        $this->form['fields'][$data['slug']] = $data['value'];
    }
}

beforeEach(function () {
    $this->actingAs($this->actor = createSuperAdmin());
    app('aura')::registerResources([Post::class]);
});

test('media field owner keeps only a locked digest and applies a correlated request once', function () {
    $owner = Livewire::test(Core20MediaOwnerHarness::class);
    $ownerToken = $owner->get('ownerTokenForTest');
    $attachment = Attachment::factory()->create(config('aura.teams') ? ['team_id' => $this->actor->current_team_id] : []);
    $value = [(string) $attachment->getKey()];
    $request = app(MediaSelectionBroker::class)->begin(
        ownerToken: $ownerToken,
        managerComponentId: 'manager-component',
        value: $value,
        actor: $this->actor,
    );

    expect($owner->get('mediaOwnerTokenDigests.image'))
        ->toBe(app(MediaOwnerTokenBroker::class)->digest($ownerToken));

    $owner->dispatch(
        'aura-media-selection-requested',
        ownerToken: $ownerToken,
        requestToken: $request->token,
        slug: 'image',
        value: $value,
    )
        ->assertSet('form.fields.image', $value)
        ->assertSet('applications', 1)
        ->assertDispatched('aura-media-selection-acknowledged', function (string $event, array $payload) use ($ownerToken, $request): bool {
            return $payload === [
                'ownerToken' => $ownerToken,
                'requestToken' => $request->token,
                'outcome' => 'succeeded',
                'errorCode' => null,
            ];
        });

    $owner->dispatch(
        'aura-media-selection-requested',
        ownerToken: $ownerToken,
        requestToken: $request->token,
        slug: 'image',
        value: $value,
    )->assertSet('applications', 1);
});

test('media field owner ignores events for another token slug or value', function () {
    $owner = Livewire::test(Core20MediaOwnerHarness::class);
    $ownerToken = $owner->get('ownerTokenForTest');
    $request = app(MediaSelectionBroker::class)->begin($ownerToken, 'manager', ['1'], $this->actor);

    foreach ([
        ['ownerToken' => $ownerToken.'x', 'requestToken' => $request->token, 'slug' => 'image', 'value' => ['1']],
        ['ownerToken' => $ownerToken, 'requestToken' => $request->token, 'slug' => 'other', 'value' => ['1']],
        ['ownerToken' => $ownerToken, 'requestToken' => $request->token, 'slug' => 'image', 'value' => ['2']],
    ] as $payload) {
        $owner->dispatch('aura-media-selection-requested', ...$payload)
            ->assertSet('form.fields.image', [])
            ->assertSet('applications', 0);
    }
});

test('media field owner rejects missing and unauthorized attachments without mutating state', function () {
    $owner = Livewire::test(Core20MediaOwnerHarness::class);
    $ownerToken = $owner->get('ownerTokenForTest');
    $request = app(MediaSelectionBroker::class)->begin($ownerToken, 'manager', ['999999'], $this->actor);

    $owner->dispatch(
        'aura-media-selection-requested',
        ownerToken: $ownerToken,
        requestToken: $request->token,
        slug: 'image',
        value: ['999999'],
    )
        ->assertSet('form.fields.image', [])
        ->assertSet('applications', 0)
        ->assertDispatched('aura-media-selection-acknowledged', fn (string $event, array $payload): bool => $payload['outcome'] === 'failed'
            && $payload['errorCode'] === 'processing_failed');
});

test('locked owner digest cannot be changed by a browser update', function () {
    $owner = Livewire::test(Core20MediaOwnerHarness::class);

    expect(fn () => $owner->set('mediaOwnerTokenDigests.image', str_repeat('0', 64)))
        ->toThrow(Exception::class);
});

test('media owner tokens can only be issued for an actual media field on the owner', function () {
    $owner = Livewire::test(Core20MediaOwnerHarness::class);
    $context = app(MediaOwnerTokenBroker::class)->resolve($owner->get('ownerTokenForTest'), $this->actor);

    expect($context->fieldType)->toBe(Image::class)
        ->and(fn () => $owner->instance()->mediaOwnerToken('title'))
        ->toThrow(InvalidArgumentException::class)
        ->and(fn () => $owner->instance()->mediaOwnerToken('missing'))
        ->toThrow(InvalidArgumentException::class);
});

test('simultaneous forms with the same slug route a selection only to its token owner', function () {
    $firstOwner = Livewire::test(Core20MediaOwnerHarness::class);
    $secondOwner = Livewire::test(Core20MediaOwnerHarness::class);
    $firstToken = $firstOwner->get('ownerTokenForTest');
    $secondToken = $secondOwner->get('ownerTokenForTest');
    $attachment = Attachment::factory()->create(config('aura.teams') ? ['team_id' => $this->actor->current_team_id] : []);
    $value = [(string) $attachment->getKey()];
    $request = app(MediaSelectionBroker::class)->begin($firstToken, 'manager', $value, $this->actor);
    $payload = [
        'ownerToken' => $firstToken,
        'requestToken' => $request->token,
        'slug' => 'image',
        'value' => $value,
    ];

    expect($firstToken)->not->toBe($secondToken);

    $secondOwner->dispatch('aura-media-selection-requested', ...$payload)
        ->assertSet('form.fields.image', [])
        ->assertSet('applications', 0)
        ->assertNotDispatched('aura-media-selection-acknowledged');

    $firstOwner->dispatch('aura-media-selection-requested', ...$payload)
        ->assertSet('form.fields.image', $value)
        ->assertSet('applications', 1)
        ->assertDispatched('aura-media-selection-acknowledged');
});
