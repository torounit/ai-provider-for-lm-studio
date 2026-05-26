<?php

/**
 * Plugin Name: AI Provider for LM Studio
 * Plugin URI: https://github.com/torounit/ai-provider-for-lm-studio
 * Description: LM Studio AI Provider for the WordPress AI Client.
 * Requires at least: 6.9
 * Requires PHP: 7.4
 * Version: 1.0.0
 * Author: ToroUnit
 * Author URI: https://torounit.com/
 * License: GPL-2.0-or-later
 * License URI: https://spdx.org/licenses/GPL-2.0-or-later.html
 * Text Domain: ai-provider-for-lm-studio
 *
 * @package ToroUnit\LmStudioAiProvider
 */

declare(strict_types=1);

namespace ToroUnit\LmStudioAiProvider;

use ToroUnit\LmStudioAiProvider\Provider\LmStudioProvider;
use WordPress\AiClient\AiClient;
use WordPress\AiClient\Providers\Http\DTO\ApiKeyRequestAuthentication;

if (!defined('ABSPATH')) {
    return;
}

require_once __DIR__ . '/src/autoload.php';

/**
 * Registers the AI Provider for LM Studio with the AI Client.
 *
 * @since 1.0.0
 *
 * @return void
 */
function register_provider(): void
{
    if (!class_exists(AiClient::class)) {
        return;
    }

    $registry = AiClient::defaultRegistry();

    if ($registry->hasProvider(LmStudioProvider::class)) {
        return;
    }

    /*
     * LM Studio accepts any Bearer token, so we set a placeholder key when none is configured.
     * putenv() ensures the connector registry detects a key source of 'env' (preventing the admin
     * UI from prompting for an API key). We also call setProviderRequestAuthentication() directly
     * because putenv() can be unreliable in some environments (e.g. wp-now / PHP WASM).
     */
    $needsDefaultKey = getenv('LM_STUDIO_API_KEY') === false && !defined('LM_STUDIO_API_KEY');
    if ($needsDefaultKey) {
        putenv('LM_STUDIO_API_KEY=lm-studio');
    }

    $registry->registerProvider(LmStudioProvider::class);

    if ($needsDefaultKey && $registry->getProviderRequestAuthentication(LmStudioProvider::class) === null) {
        $registry->setProviderRequestAuthentication(
            LmStudioProvider::class,
            new ApiKeyRequestAuthentication('lm-studio')
        );
    }
}

add_action('init', __NAMESPACE__ . '\\register_provider', 5);

/**
 * Adds LM Studio's port to WordPress's safe ports list so wp_http_validate_url() passes.
 *
 * By default WordPress only allows ports 80, 443, and 8080. LM Studio defaults to 1234.
 *
 * @since 1.0.0
 *
 * @param int[]  $ports Allowed ports.
 * @param string $host  Request hostname.
 * @param string $url   Request URL.
 * @return int[] Modified allowed ports.
 */
function allow_lm_studio_port(array $ports, string $host, string $url): array
{
    $parsed = parse_url(LmStudioProvider::url());
    if (
        isset($parsed['host'], $parsed['port']) &&
        strtolower($parsed['host']) === strtolower($host)
    ) {
        $ports[] = $parsed['port'];
    }
    return $ports;
}

add_filter('http_allowed_safe_ports', __NAMESPACE__ . '\\allow_lm_studio_port', 10, 3);

/**
 * Allows WordPress to make HTTP requests to LM Studio even when it runs on a private/local IP.
 *
 * @since 1.0.0
 *
 * @param bool   $is_external Whether the host is considered external.
 * @param string $host        Request hostname.
 * @param string $url         Request URL.
 * @return bool True when the host matches LM Studio's configured host.
 */
function allow_lm_studio_host(bool $is_external, string $host, string $url): bool
{
    $parsed = parse_url(LmStudioProvider::url());
    if (isset($parsed['host']) && strtolower($parsed['host']) === strtolower($host)) {
        return true;
    }
    return $is_external;
}

add_filter('http_request_host_is_external', __NAMESPACE__ . '\\allow_lm_studio_host', 10, 3);
