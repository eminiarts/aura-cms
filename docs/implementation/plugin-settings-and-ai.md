# Plugin Settings and AI Connector Implementation Plan

## Outcome

Aura will expose two core modules:

1. A settings-page registry that lets Composer plugins add titled, ordered, icon-labelled tabs to `/admin/settings` without defining a resource, route, or persistence layer.
2. A provider-neutral AI connector that reads encrypted credentials from the central settings record and supports OpenAI, Anthropic Claude, Google Gemini, GLM, and OpenAI-compatible local/custom endpoints.

## Module design

### Plugin settings

The external seam is `Aura::registerSettingsPages($source, $pages)`. Each plugin registers immutable `SettingsPage` definitions during its provider boot process. A definition owns its title, slug, icon, order, fields, defaults, and list of secret field slugs.

The registry validates package ownership, page identity, and global field-slug uniqueness. The existing Aura field renderer remains the implementation behind the seam, so plugins do not need to learn a second form system. All pages share the existing team-aware `settings` option for backward compatibility.

Secret values are write-only in the browser. Stored values are encrypted with Laravel's encrypter, omitted from Livewire form state, and preserved when an empty secret input is submitted.

### AI connector

The external seam is the `AiConnector` contract:

- `generate(prompt, systemPrompt)` returns generated text.
- `testConnection()` returns a structured success or failure result without leaking credentials.

The implementation selects one of three internal protocol adapters:

- OpenAI-compatible: OpenAI, GLM, custom cloud endpoints, and local endpoints.
- Anthropic Messages.
- Google Gemini `generateContent`.

Provider, endpoint, model, and encrypted API key are configured in the built-in AI settings page. Environment-backed configuration remains available as a deployment fallback.

## Delivery phases

### Phase 1 — Core registry and persistence

- Add immutable settings-page definitions and registry validation.
- Register General and AI pages from Aura core.
- Render page icons and descriptions in the existing tab interface.
- Add encrypted, write-only secret persistence.
- Document plugin registration and retrieval.

### Phase 2 — AI transport

- Add provider configuration resolution with safe defaults.
- Implement the OpenAI-compatible, Anthropic, and Gemini adapters.
- Bind the provider-neutral connector in the container.
- Add connection testing to the AI settings tab.
- Cover request shape, response parsing, error sanitization, and secret handling with mocked HTTP tests.

### Phase 3 — Plugin adoption

- Move SEO profile and diagnostics configuration into registered settings pages.
- Use the core connector for SEO metadata pre-fill.
- Move redirect policy switches into a Redirects settings page.
- Publish migration notes for plugins that used standalone configuration resources.

### Phase 4 — Hardening

- Add provider-specific model discovery only where providers expose stable endpoints.
- Add rate-limit and observability hooks without exposing prompts or credentials by default.
- Add optional per-feature AI policies and usage budgets.
- Exercise custom/local endpoints in browser and end-to-end tests.

## Compatibility and security constraints

- Existing theme settings keep their field slugs and option record names.
- Only super admins can open, save, or test settings.
- API keys are never rendered back into Livewire state.
- Local endpoints are intentionally permitted; access remains a trusted super-admin capability.
- Provider errors shown in the UI are sanitized and must not contain authorization headers or keys.
