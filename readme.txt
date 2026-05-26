=== AI Provider for LM Studio ===
Contributors: torounit
Tags: ai, lm-studio, llm, artificial-intelligence, connector
Requires at least: 6.9
Tested up to: 6.9
Stable tag: 0.1.0
Requires PHP: 7.4
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

LM Studio AI Provider for the PHP AI Client SDK.

== Description ==

This plugin provides LM Studio integration for the PHP AI Client SDK. It enables WordPress sites to use locally running LLM models via LM Studio's OpenAI-compatible API.

**Features:**

* Text generation with any model loaded in LM Studio
* Chat history support
* Function calling support
* JSON output mode support
* Automatic provider registration

Available models are dynamically discovered from the LM Studio local server API.

**Requirements:**

* PHP 7.4 or higher
* [LM Studio](https://lmstudio.ai/) running locally with the local server enabled
* For WordPress 7.0 and above, no additional changes are required

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/ai-provider-for-lm-studio/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Start LM Studio and enable the local server
4. Optionally set `LM_STUDIO_BASE_URL` if your LM Studio server is not on the default `http://localhost:1234/v1`

== Frequently Asked Questions ==

= Do I need an API key for LM Studio? =

No. The plugin automatically uses a placeholder Bearer token so LM Studio works out of the box without any API key configuration.

If LM Studio's server authentication is enabled, enter your API key in **Settings > Connectors** or set the `LM_STUDIO_API_KEY` environment variable.

= How do I change the LM Studio server URL? =

Set the `LM_STUDIO_BASE_URL` environment variable to the URL of your LM Studio server (e.g. `http://localhost:1234/v1`).

= Does this plugin work without the PHP AI Client? =

No, this plugin requires the PHP AI Client plugin to be installed and activated. It provides the LM Studio-specific implementation that the PHP AI Client uses.

== Changelog ==

= 0.1.0 =

* Initial release
