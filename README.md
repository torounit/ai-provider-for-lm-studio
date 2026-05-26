# AI Provider for LM Studio

LM Studio AI Provider for the [PHP AI Client SDK](https://github.com/WordPress/php-ai-client).

## Requirements

- PHP 7.4+
- [wordpress/php-ai-client](https://github.com/WordPress/php-ai-client) ^1.3
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

Set the following environment variables:

| Variable | Default | Description |
|---|---|---|
| `LM_STUDIO_BASE_URL` | `http://localhost:1234/v1` | Base URL of the LM Studio server |
| `LM_STUDIO_API_KEY` | *(required)* | Any string (e.g. `lm-studio`). LM Studio accepts any Bearer token. |

> **Note:** LM Studio does not require authentication, but the SDK needs an API key value set.
> Set `LM_STUDIO_API_KEY` to any arbitrary string such as `lm-studio`.

## Usage

### Composer (standalone)

```php
<?php
require __DIR__ . '/vendor/autoload.php';

use WordPress\AiClient\AiClient;
use ToroUnit\LmStudioAiProvider\Provider\LmStudioProvider;

putenv('LM_STUDIO_API_KEY=lm-studio');
// putenv('LM_STUDIO_BASE_URL=http://localhost:1234/v1');

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
```

### WordPress Plugin

After activating the plugin, the LM Studio provider is automatically registered with the AI Client. Use the AI Client API as usual:

```php
use WordPress\AiClient\AiClient;

$result = AiClient::prompt('Hello, who are you?')
    ->usingProvider('lm_studio')
    ->generateText();
```

## License

GPL-2.0-or-later
