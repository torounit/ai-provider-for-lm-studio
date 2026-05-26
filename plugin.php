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

    $registry->registerProvider(LmStudioProvider::class);
}

add_action('init', __NAMESPACE__ . '\\register_provider', 5);
