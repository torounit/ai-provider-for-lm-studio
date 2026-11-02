<?php

declare(strict_types=1);

namespace ToroUnit\LmStudioAiProvider\Tests\unit\Provider;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ToroUnit\LmStudioAiProvider\Models\LmStudioEmbeddingGenerationModel;
use ToroUnit\LmStudioAiProvider\Models\LmStudioTextGenerationModel;
use ToroUnit\LmStudioAiProvider\Provider\LmStudioProvider;
use WordPress\AiClient\Common\Exception\RuntimeException;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;

#[CoversClass(LmStudioProvider::class)]
class LmStudioProviderTest extends TestCase
{
    protected function tearDown(): void
    {
        putenv('LM_STUDIO_SERVER_URL');
        parent::tearDown();
    }

    public function testMetadata(): void
    {
        $metadata = LmStudioProvider::metadata();

        $this->assertSame('lm_studio', $metadata->getId());
        $this->assertSame('LM Studio', $metadata->getName());
    }

    public function testServerUrlDefaultsToLocalhost(): void
    {
        putenv('LM_STUDIO_SERVER_URL');

        $this->assertSame('http://localhost:1234', LmStudioProvider::serverUrl());
        $this->assertSame('http://localhost:1234/v1/embeddings', LmStudioProvider::url('embeddings'));
    }

    public function testServerUrlReadsEnvironmentVariable(): void
    {
        putenv('LM_STUDIO_SERVER_URL=http://host.docker.internal:1234/');

        $this->assertSame('http://host.docker.internal:1234', LmStudioProvider::serverUrl());
        $this->assertSame(
            'http://host.docker.internal:1234/v1/chat/completions',
            LmStudioProvider::url('chat/completions')
        );
    }

    public function testCreateModelReturnsTextGenerationModel(): void
    {
        $model = $this->createModel([CapabilityEnum::textGeneration(), CapabilityEnum::chatHistory()]);

        $this->assertInstanceOf(LmStudioTextGenerationModel::class, $model);
    }

    public function testCreateModelReturnsEmbeddingGenerationModel(): void
    {
        $model = $this->createModel([CapabilityEnum::embeddingGeneration()]);

        $this->assertInstanceOf(LmStudioEmbeddingGenerationModel::class, $model);
    }

    public function testCreateModelThrowsForUnsupportedCapabilities(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported model capabilities');
        $this->createModel([CapabilityEnum::imageGeneration()]);
    }

    /**
     * @param list<CapabilityEnum> $capabilities The model capabilities.
     */
    private function createModel(array $capabilities): ModelInterface
    {
        $provider = new class extends LmStudioProvider {
            public static function exposeCreateModel(ModelMetadata $modelMetadata): ModelInterface
            {
                return static::createModel($modelMetadata, static::metadata());
            }
        };

        return $provider::exposeCreateModel(new ModelMetadata('test-model', 'test-model', $capabilities, []));
    }
}
