=== AI Provider for LM Studio ===
Contributors: torounit
Tags: ai, lm-studio, llm, artificial-intelligence, connector
Requires at least: 7.2
Tested up to: 7.2
Stable tag: 0.2.0
Requires PHP: 7.4
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

LM Studio AI Provider for the PHP AI Client SDK.

== Description ==

This plugin provides LM Studio integration for the PHP AI Client SDK. It enables WordPress sites to use locally running LLM models via LM Studio's OpenAI-compatible API.

**Features:**

* Text generation with any model loaded in LM Studio
* Embedding generation with embedding models loaded in LM Studio (via `wp_ai_client_embedding()`)
* Chat history support
* Function calling support
* JSON output mode support
* Automatic provider registration

Available models are dynamically discovered from the LM Studio local server API.

**Requirements:**

* PHP 7.4 or higher
* [LM Studio](https://lmstudio.ai/) running locally with the local server enabled
* WordPress 7.2 or higher (bundles the PHP AI Client with embedding support)

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/ai-provider-for-lm-studio/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Start LM Studio and enable the local server
4. Optionally set `LM_STUDIO_SERVER_URL` if your LM Studio server is not on the default `http://localhost:1234`

== Frequently Asked Questions ==

= Do I need an API key for LM Studio? =

No. The plugin automatically uses a placeholder Bearer token so LM Studio works out of the box without any API key configuration.

If LM Studio's server authentication is enabled, enter your API key in **Settings > Connectors** or set the `LM_STUDIO_API_KEY` environment variable.

= How do I change the LM Studio server URL? =

Set the `LM_STUDIO_SERVER_URL` environment variable or PHP constant to the URL of your LM Studio server (e.g. `http://localhost:1234`).

= Does this plugin work without the PHP AI Client? =

No, this plugin requires the PHP AI Client plugin to be installed and activated. It provides the LM Studio-specific implementation that the PHP AI Client uses.

== Changelog ==

= 0.2.0 =

* Add embedding generation support using the WordPress 7.2 AI Client embedding API
* Require WordPress 7.2

= 0.1.0 =

* Initial release
