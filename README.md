# AI Provider for LM Studio

LM Studio AI Provider for the [PHP AI Client SDK](https://github.com/WordPress/php-ai-client).

## Requirements

- PHP 7.4+
- [wordpress/php-ai-client](https://github.com/WordPress/php-ai-client) ^1.4 (bundled with WordPress 7.2+)
- [LM Studio](https://lmstudio.ai/) running locally with the server enabled

## Installation

### As a Composer Package

```bash
composer require torounit/ai-provider-for-lm-studio
```

### As a WordPress Plugin

1. Upload the plugin files to `/wp-content/plugins/ai-provider-for-lm-studio/`
2. Activate the plugin through the **Plugins** menu in WordPress
3. Start LM Studio and enable the local server (Developer tab → Start Server)

## Configuration

| Variable | Default | Description |
|---|---|---|
| `LM_STUDIO_SERVER_URL` | `http://localhost:1234` | LM Studio server URL (scheme + host + port, no API path) |

Both environment variables and PHP constants (e.g. `define('LM_STUDIO_SERVER_URL', '...')` in `wp-config.php`) are supported.

## Usage

### Composer (standalone)

```php
<?php
require __DIR__ . '/vendor/autoload.php';

use WordPress\AiClient\AiClient;
use ToroUnit\LmStudioAiProvider\Provider\LmStudioProvider;

// putenv('LM_STUDIO_SERVER_URL=http://localhost:1234');

$registry = AiClient::defaultRegistry();
$registry->registerProvider(LmStudioProvider::class);

// List available models
$models = LmStudioProvider::modelMetadataDirectory()->listModelMetadata();
foreach ($models as $model) {
    echo $model->getId() . "\n";
}

// Generate text
$result = AiClient::prompt('Hello, who are you?')
    ->usingProvider('lm_studio')
    ->generateText();

echo $result . "\n";

// Generate embeddings (an embedding model must be loaded in LM Studio)
$embeddings = AiClient::input(['Hello', 'World'])
    ->usingProviderModel('lm_studio', 'text-embedding-nomic-embed-text-v1.5')
    ->generateEmbeddings();
```

### WordPress Plugin

After activating the plugin, the LM Studio provider is automatically registered with the AI Client. Use the AI Client API as usual:

```php
use WordPress\AiClient\AiClient;

$result = AiClient::prompt('Hello, who are you?')
    ->usingProvider('lm_studio')
    ->generateText();
```

Embeddings are generated through the WordPress 7.2 embedding API. A model must always be specified, since vectors are only comparable when produced by the same model:

```php
$embedding = wp_ai_client_embedding('Hello, world')
    ->using_provider_model('lm_studio', 'text-embedding-nomic-embed-text-v1.5')
    ->generate_embedding();

if (is_wp_error($embedding)) {
    // Handle the error.
}
```

Only text inputs are supported. The `dimensions` option is not supported: LM Studio's embeddings API does not accept it and always returns the model's native vector size, so requests that set `using_dimensions()` are rejected instead of silently returning vectors of a different size.

## License

GPL-2.0-or-later
