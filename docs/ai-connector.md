# AI in Aura

Aura uses the official Laravel AI SDK for AI features in core and plugins. Provider selection, models, endpoints, and credentials use the host application's `config/ai.php`. Aura does not copy credentials into its database or provide a second connector abstraction.

Values created by the retired database-backed connector are ignored. Aura removes those legacy AI keys the next time the affected settings record is saved; move any credential that is still needed into the application's environment first.

The Aura installer publishes `config/ai.php` when the file does not already exist. After installation, add credentials to the application's environment and clear Laravel's configuration cache:

```bash
php artisan config:clear
```

## Configure a provider

Laravel AI includes providers such as OpenAI, Anthropic, Gemini, Ollama, and OpenAI-compatible endpoints. Select the default text provider with `ai.default` and configure its entry under `ai.providers`.

For example, OpenAI uses `OPENAI_API_KEY`. An OpenAI-compatible GLM or local endpoint can use `OPENAI_COMPATIBLE_URL` and `OPENAI_COMPATIBLE_API_KEY`. A trusted local endpoint may omit the key if the server accepts unauthenticated requests. Refer to the published file for the complete provider list.

Aura reports a provider as configured only when the SDK can prompt it:

- **OpenAI-compatible** needs a URL and a default text model. The SDK has no model default for this driver, so add one to the provider entry:

  ~~~php
  'openai-compatible' => [
      'driver' => 'openai-compatible',
      'url' => env('OPENAI_COMPATIBLE_URL'),
      'key' => env('OPENAI_COMPATIBLE_API_KEY'),
      'models' => ['text' => ['default' => env('OPENAI_COMPATIBLE_MODEL')]],
  ],
  ~~~

- **Ollama** runs local models without a key. Set `ai.default` to `ollama` in `config/ai.php`. The standard `http://localhost:11434` endpoint counts as configured only when Ollama is the default provider, because the SDK's published config always contains it.
- **Bedrock** counts as configured with a bearer token, an access key pair, or an `assume_role` ARN. The AWS default credential chain (instance or task role, AWS profile) counts only when Bedrock is the default provider, for the same reason.

Aura-wide AI features can be disabled independently:

```dotenv
AURA_AI_ENABLED=false
```

This sets `aura.ai.enabled` to `false`. `AiStatus::configured()` then returns `false`, and plugins should hide or disable AI actions.

## Provider status

There is no settings page for AI providers: they are configured only through the environment and `config/ai.php`. To check them from code, use `AiStatus`. `providers()` lists each provider with its driver, whether credentials are present, whether it is usable, and whether it is the default; it never returns credential values. `test($provider)` sends a short prompt through that provider and redacts configured credentials from errors.

After changing environment values in production, rebuild or clear the configuration cache.

## Use the SDK directly

Core features and plugins call Laravel AI directly. Do not resolve an Aura connector or select a separate Aura provider:

```php
use function Laravel\Ai\agent;

$response = agent(
    instructions: 'Write concise search metadata.',
)->prompt('Summarize this article for a search result.');

$text = $response->text;
```

Omitting the provider lets the host application's `ai.default` configuration control deployment. Before presenting an AI action, check the shared status service:

```php
use Aura\Base\Ai\AiStatus;

if (app(AiStatus::class)->configured()) {
    // Show the AI action.
}
```

Use the Laravel AI SDK's fakes in automated tests so tests do not make network requests.

## Rules for plugins

- Call `laravel/ai` directly.
- Do not set a provider or model in plugin code. The application's default applies.
- Do not read `config('ai.*')` to decide whether AI is available. Use `AiStatus::configured()`.
- Protect every AI route or action with a permission check and a rate limit. Every call spends the application owner's tokens.

## Costs and data processing

- **One key and one bill for all teams.** Anyone with a plugin's AI permission in any team spends the application owner's tokens. Aura has no per-team limits.
- **Content from every team goes to one provider account.** Customers with data-processing requirements need to know which provider and region the application uses.

## Installation footprint

Installing Aura installs Laravel AI and registers its normal SDK services and generator commands. Aura publishes only the SDK configuration file. Laravel AI's optional conversation migration is not published or run by `aura:install`; applications that use persisted SDK conversations can publish that migration explicitly.
