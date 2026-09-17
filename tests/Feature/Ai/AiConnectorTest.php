<?php

use Aura\Base\Contracts\AiConnector;
use Aura\Base\Settings\SettingsStore;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

test('the OpenAI-compatible adapter sends a chat completion request', function () {
    configureAi([
        'ai-provider' => 'openai',
        'ai-endpoint' => 'https://ai.example.test/v1',
        'ai-model' => 'test-model',
        'ai-api-key' => 'openai-secret',
    ]);

    Http::fake([
        'ai.example.test/*' => Http::response([
            'choices' => [['message' => ['content' => 'Generated metadata']]],
        ]),
    ]);

    $result = app(AiConnector::class)->generate('Write metadata', 'Be concise');

    expect($result)->toBe('Generated metadata');

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://ai.example.test/v1/chat/completions'
        && $request->hasHeader('Authorization', 'Bearer openai-secret')
        && $request['model'] === 'test-model'
        && $request['messages'] === [
            ['role' => 'system', 'content' => 'Be concise'],
            ['role' => 'user', 'content' => 'Write metadata'],
        ]);
});

test('the Anthropic adapter uses the messages protocol', function () {
    configureAi([
        'ai-provider' => 'anthropic',
        'ai-endpoint' => 'https://claude.example.test/v1',
        'ai-model' => 'claude-test',
        'ai-api-key' => 'anthropic-secret',
    ]);

    Http::fake([
        'claude.example.test/*' => Http::response([
            'content' => [['type' => 'text', 'text' => 'Claude response']],
        ]),
    ]);

    expect(app(AiConnector::class)->generate('Hello'))->toBe('Claude response');

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://claude.example.test/v1/messages'
        && $request->hasHeader('x-api-key', 'anthropic-secret')
        && $request->hasHeader('anthropic-version', '2023-06-01')
        && $request['model'] === 'claude-test');
});

test('the Gemini adapter uses generateContent', function () {
    configureAi([
        'ai-provider' => 'gemini',
        'ai-endpoint' => 'https://gemini.example.test/v1beta',
        'ai-model' => 'gemini-test',
        'ai-api-key' => 'gemini-secret',
    ]);

    Http::fake([
        'gemini.example.test/*' => Http::response([
            'candidates' => [[
                'content' => ['parts' => [['text' => 'Gemini response']]],
            ]],
        ]),
    ]);

    expect(app(AiConnector::class)->generate('Hello'))->toBe('Gemini response');

    Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/models/gemini-test:generateContent')
        && $request->hasHeader('x-goog-api-key', 'gemini-secret')
        && $request['contents'][0]['parts'][0]['text'] === 'Hello');
});

test('custom local endpoints work without an API key', function () {
    configureAi([
        'ai-provider' => 'custom',
        'ai-endpoint' => 'http://192.168.1.20:11434/v1',
        'ai-model' => 'local-model',
    ]);

    Http::fake([
        '192.168.1.20:11434/*' => Http::response([
            'choices' => [['message' => ['content' => 'Local response']]],
        ]),
    ]);

    expect(app(AiConnector::class)->generate('Hello'))->toBe('Local response');

    Http::assertSent(fn (Request $request): bool => ! $request->hasHeader('Authorization'));
});

test('switching providers never forwards a key entered for another provider', function () {
    configureAi([
        'ai-provider' => 'openai',
        'ai-endpoint' => 'https://api.openai.test/v1',
        'ai-model' => 'openai-model',
        'ai-api-key' => 'openai-only-secret',
    ]);

    $store = app(SettingsStore::class);
    $option = $store->findOrCreate();
    $store->store($option, [
        'ai-provider' => 'custom',
        'ai-endpoint' => 'http://192.168.1.20:11434/v1',
        'ai-model' => 'local-model',
        'ai-api-key' => '',
    ], ['ai-api-key']);

    Http::fake([
        '192.168.1.20:11434/*' => Http::response([
            'choices' => [['message' => ['content' => 'Local response']]],
        ]),
    ]);

    expect(app(AiConnector::class)->generate('Hello'))->toBe('Local response');

    Http::assertSent(fn (Request $request): bool => ! $request->hasHeader('Authorization'));
});

test('connection test failures redact the configured API key', function () {
    configureAi([
        'ai-provider' => 'openai',
        'ai-endpoint' => 'https://ai.example.test/v1',
        'ai-model' => 'test-model',
        'ai-api-key' => 'must-never-leak',
    ]);

    Http::fake([
        'ai.example.test/*' => Http::response([
            'error' => ['message' => 'Rejected key must-never-leak'],
        ], 401),
    ]);

    $result = app(AiConnector::class)->testConnection();

    expect($result->successful)->toBeFalse()
        ->and($result->message)->toContain('[redacted]')
        ->and($result->message)->not->toContain('must-never-leak');
});

/** @param  array<string, mixed>  $settings */
function configureAi(array $settings): void
{
    $store = app(SettingsStore::class);
    $option = $store->findOrCreate();
    $secretFields = array_key_exists('ai-api-key', $settings) ? ['ai-api-key'] : [];

    $store->store($option, $settings, $secretFields);
}
