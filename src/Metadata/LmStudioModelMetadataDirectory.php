<?php

declare(strict_types=1);

namespace ToroUnit\LmStudioAiProvider\Metadata;

use ToroUnit\LmStudioAiProvider\Provider\LmStudioProvider;
use WordPress\AiClient\Messages\Enums\ModalityEnum;
use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\DTO\Response;
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;
use WordPress\AiClient\Providers\Http\Exception\ResponseException;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\DTO\SupportedOption;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Providers\Models\Enums\OptionEnum;
use WordPress\AiClient\Providers\OpenAiCompatibleImplementation\AbstractOpenAiCompatibleModelMetadataDirectory;

/**
 * Class for the LM Studio model metadata directory.
 *
 * @since 0.1.0
 *
 * @phpstan-type ModelsResponseData array{
 *     data: list<array{id: string, type?: string}>
 * }
 */
class LmStudioModelMetadataDirectory extends AbstractOpenAiCompatibleModelMetadataDirectory
{
    /**
     * {@inheritDoc}
     *
     * Uses the LM Studio native /api/v0/models endpoint instead of the
     * OpenAI-compatible /v1/models endpoint because only the native endpoint
     * returns a `type` field that distinguishes embedding models from LLMs.
     *
     * @since 0.1.0
     *
     * @return array<string, ModelMetadata>
     */
    protected function sendListModelsRequest(): array
    {
        $apiV0Url = LmStudioProvider::serverUrl() . '/api/v0/models';
        $request  = new Request(HttpMethodEnum::GET(), $apiV0Url);
        $request  = $this->getRequestAuthentication()->authenticateRequest($request);
        $response = $this->getHttpTransporter()->send($request);
        $this->throwIfNotSuccessful($response);

        $list = $this->parseResponseToModelMetadataList($response);
        $map  = [];
        foreach ($list as $meta) {
            $map[$meta->getId()] = $meta;
        }
        return $map;
    }

    /**
     * {@inheritDoc}
     *
     * @since 0.1.0
     */
    protected function createRequest(HttpMethodEnum $method, string $path, array $headers = [], $data = null): Request
    {
        return new Request(
            $method,
            LmStudioProvider::url($path),
            $headers,
            $data
        );
    }

    /**
     * {@inheritDoc}
     *
     * @since 0.1.0
     */
    protected function parseResponseToModelMetadataList(Response $response): array
    {
        /** @var ModelsResponseData $responseData */
        $responseData = $response->getData();
        if (!isset($responseData['data']) || !$responseData['data']) {
            throw ResponseException::fromMissingData('LM Studio', 'data');
        }

        $textCapabilities = [
            CapabilityEnum::textGeneration(),
            CapabilityEnum::chatHistory(),
        ];

        $textOptions = [
            new SupportedOption(OptionEnum::systemInstruction()),
            new SupportedOption(OptionEnum::maxTokens()),
            new SupportedOption(OptionEnum::temperature()),
            new SupportedOption(OptionEnum::topP()),
            new SupportedOption(OptionEnum::stopSequences()),
            new SupportedOption(OptionEnum::presencePenalty()),
            new SupportedOption(OptionEnum::frequencyPenalty()),
            new SupportedOption(OptionEnum::outputMimeType(), ['text/plain', 'application/json']),
            new SupportedOption(OptionEnum::outputSchema()),
            new SupportedOption(OptionEnum::functionDeclarations()),
            new SupportedOption(OptionEnum::customOptions()),
            new SupportedOption(OptionEnum::inputModalities(), [[ModalityEnum::text()]]),
            new SupportedOption(OptionEnum::outputModalities(), [[ModalityEnum::text()]]),
        ];

        $modelsData = (array) $responseData['data'];

        return array_values(
            array_map(
                static function (array $modelData) use ($textCapabilities, $textOptions): ModelMetadata {
                    $modelId = (string) $modelData['id'];
                    $type    = isset($modelData['type']) ? (string) $modelData['type'] : '';

                    if ($type === 'embeddings') {
                        return new ModelMetadata(
                            $modelId,
                            $modelId,
                            [CapabilityEnum::embeddingGeneration()],
                            []
                        );
                    }

                    return new ModelMetadata(
                        $modelId,
                        $modelId,
                        $textCapabilities,
                        $textOptions
                    );
                },
                $modelsData
            )
        );
    }
}
