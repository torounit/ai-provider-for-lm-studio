<?php

declare(strict_types=1);

namespace ToroUnit\LmStudioAiProvider\Provider;

use ToroUnit\LmStudioAiProvider\Metadata\LmStudioModelMetadataDirectory;
use ToroUnit\LmStudioAiProvider\Models\LmStudioTextGenerationModel;
use WordPress\AiClient\Providers\ApiBasedImplementation\AbstractApiProvider;
use WordPress\AiClient\Providers\ApiBasedImplementation\ListModelsApiBasedProviderAvailability;
use WordPress\AiClient\Providers\Contracts\ModelMetadataDirectoryInterface;
use WordPress\AiClient\Providers\Contracts\ProviderAvailabilityInterface;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Enums\ProviderTypeEnum;
use WordPress\AiClient\Providers\Http\Enums\RequestAuthenticationMethod;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;

/**
 * Class for the AI Provider for LM Studio.
 *
 * @since 0.1.0
 */
class LmStudioProvider extends AbstractApiProvider
{
    /**
     * Default server URL for LM Studio (scheme + host + port, no API path).
     *
     * @since 0.1.0
     */
    private const DEFAULT_BASE_URL = 'http://localhost:1234';

    /**
     * Returns the configured LM Studio server URL without any API path suffix.
     *
     * Reads from the LM_STUDIO_BASE_URL environment variable or PHP constant,
     * falling back to the default. The value should be the server root only,
     * e.g. "http://localhost:1234" — without a trailing /v1 or similar.
     *
     * @since 0.1.0
     *
     * @return string Server URL with no trailing slash.
     */
    public static function serverUrl(): string
    {
        $url = getenv('LM_STUDIO_BASE_URL');
        $constant = defined('LM_STUDIO_BASE_URL') ? constant('LM_STUDIO_BASE_URL') : null;
        if (empty($url) && is_string($constant)) {
            $url = $constant;
        }
        return rtrim($url ?: self::DEFAULT_BASE_URL, '/');
    }

    /**
     * {@inheritDoc}
     *
     * @since 0.1.0
     */
    protected static function baseUrl(): string
    {
        return self::serverUrl() . '/v1';
    }

    /**
     * {@inheritDoc}
     *
     * @since 0.1.0
     */
    protected static function createProviderMetadata(): ProviderMetadata
    {
        return new ProviderMetadata(
            'lm_studio',
            'LM Studio',
            ProviderTypeEnum::server(),
            null,
            RequestAuthenticationMethod::apiKey(),
            'Local LLM inference via LM Studio OpenAI-compatible API.'
        );
    }

    /**
     * {@inheritDoc}
     *
     * @since 0.1.0
     */
    protected static function createProviderAvailability(): ProviderAvailabilityInterface
    {
        return new ListModelsApiBasedProviderAvailability(
            static::modelMetadataDirectory()
        );
    }

    /**
     * {@inheritDoc}
     *
     * @since 0.1.0
     */
    protected static function createModelMetadataDirectory(): ModelMetadataDirectoryInterface
    {
        return new LmStudioModelMetadataDirectory();
    }

    /**
     * {@inheritDoc}
     *
     * @since 0.1.0
     */
    protected static function createModel(
        ModelMetadata $modelMetadata,
        ProviderMetadata $providerMetadata
    ): ModelInterface {
        return new LmStudioTextGenerationModel($modelMetadata, $providerMetadata);
    }
}
