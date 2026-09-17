# AI Connector

Aura provides a provider-neutral AI connector for core features and plugins. Administrators configure it in `/admin/settings` under the **AI** tab and can test the active connection from the same page.

## Supported providers

- OpenAI
- Anthropic Claude
- Google Gemini
- GLM
- Custom or local OpenAI-compatible endpoints

Provider defaults live under `aura.ai.providers`. The administrator can override the endpoint and model. A custom endpoint may omit an API key, which supports trusted local-network runtimes.

## Deployment defaults

Database values entered in the settings screen take precedence over `config/aura.php`. A host application's published config may map deployment environment variables:

```php
'ai' => [
    'provider' => env('AURA_AI_PROVIDER', 'openai'),
    'endpoint' => env('AURA_AI_ENDPOINT'),
    'model' => env('AURA_AI_MODEL'),
    'api_key' => env('AURA_AI_API_KEY'),
    'timeout' => (int) env('AURA_AI_TIMEOUT', 30),
],
```

Keys saved in the UI are encrypted with Laravel's application encrypter. Aura never repopulates a stored key into the Livewire form; an empty submission preserves it. Each saved key is bound to the provider selected when it was entered, so changing providers cannot forward that credential to a different cloud or local endpoint.

## Generating text

Resolve the connector contract from Laravel's container:

```php
use Aura\Base\Contracts\AiConnector;

$text = app(AiConnector::class)->generate(
    prompt: 'Generate an SEO title and meta description for this article: ...',
    systemPrompt: 'Return concise plain text.',
);
```

The connector returns generated text and throws an exception when configuration or the provider response is invalid. Provider selection, authorization headers, endpoint paths, and response parsing remain inside the connector implementation.

## Testing a connection

Plugins can run the same non-destructive connection check used by the settings screen:

```php
$result = app(AiConnector::class)->testConnection();

if (! $result->successful) {
    report($result->message);
}
```

The result includes success, a display-safe message, the provider response when successful, and elapsed milliseconds. Error messages redact the configured API key.

## Team context

When Aura teams are enabled, UI-managed AI configuration follows the active team's settings record. Code running without an authenticated team context falls back to the environment configuration above. Queue jobs should establish the intended team context or use deployment defaults.
