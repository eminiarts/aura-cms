<?php

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
    $attachment = Attachment::factory()->create(['team_id' => $this->actor->current_team_id]);
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
