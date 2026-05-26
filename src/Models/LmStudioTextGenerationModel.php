<?php

declare(strict_types=1);

namespace ToroUnit\LmStudioAiProvider\Models;

use ToroUnit\LmStudioAiProvider\Provider\LmStudioProvider;
use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;
use WordPress\AiClient\Providers\OpenAiCompatibleImplementation\AbstractOpenAiCompatibleTextGenerationModel;

/**
 * Class for a LM Studio text generation model.
 *
 * @since 0.1.0
 */
class LmStudioTextGenerationModel extends AbstractOpenAiCompatibleTextGenerationModel
{
    /**
     * {@inheritDoc}
     *
     * @since 0.1.0
     */
    protected function createRequest(
        HttpMethodEnum $method,
        string $path,
        array $headers = [],
        $data = null
    ): Request {
        return new Request(
            $method,
            LmStudioProvider::url($path),
            $headers,
            $data,
            $this->getRequestOptions()
        );
    }
}
