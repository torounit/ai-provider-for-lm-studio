<?php

declare(strict_types=1);

namespace ToroUnit\LmStudioAiProvider\Tests\unit\Models;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ToroUnit\LmStudioAiProvider\Models\LmStudioEmbeddingGenerationModel;
use WordPress\AiClient\Common\Exception\InvalidArgumentException;
use WordPress\AiClient\Files\DTO\File;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Enums\ProviderTypeEnum;
use WordPress\AiClient\Providers\Http\Contracts\HttpTransporterInterface;
use WordPress\AiClient\Providers\Http\Contracts\RequestAuthenticationInterface;
use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\DTO\Response;
use WordPress\AiClient\Providers\Http\Exception\ResponseException;
use WordPress\AiClient\Providers\Models\DTO\ModelConfig;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;

#[CoversClass(LmStudioEmbeddingGenerationModel::class)]
class LmStudioEmbeddingGenerationModelTest extends TestCase
{
    public function testPrepareGenerateEmbeddingsParamsIncludesModelInputAndCustomOptions(): void
    {
        $model = $this->createExposedModel();
        $model->setConfig(ModelConfig::fromArray([
            'customOptions' => ['encoding_format' => 'float'],
        ]));

        $params = $model->exposePrepareGenerateEmbeddingsParams([
            new MessagePart('First'),
            new MessagePart('Second'),
        ]);

        $this->assertSame(
            [
                'model' => 'text-embedding-nomic-embed-text-v1.5',
                'input' => ['First', 'Second'],
                'encoding_format' => 'float',
            ],
            $params
        );
    }

    public function testPrepareGenerateEmbeddingsParamsDoesNotSendDimensions(): void
    {
        $model = $this->createExposedModel();
        $model->setConfig(ModelConfig::fromArray(['dimensions' => 256]));

        $params = $model->exposePrepareGenerateEmbeddingsParams([new MessagePart('Search text')]);

        $this->assertArrayNotHasKey('dimensions', $params);
    }

    public function testPrepareGenerateEmbeddingsParamsRejectsConflictingCustomOptions(): void
    {
        $model = $this->createExposedModel();
        $model->setConfig(ModelConfig::fromArray([
            'customOptions' => ['model' => 'other-model'],
        ]));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The custom option "model" conflicts with an existing parameter.');
        $model->exposePrepareGenerateEmbeddingsParams([new MessagePart('Search text')]);
    }

    /**
     * @param array<mixed> $input   The invalid embedding input.
     * @param string       $message The expected exception message.
     */
    #[DataProvider('invalidInputs')]
    public function testPrepareGenerateEmbeddingsParamsRejectsInvalidInputs(array $input, string $message): void
    {
        $model = $this->createExposedModel();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);
        $model->exposePrepareGenerateEmbeddingsParams($input);
    }

    /**
     * @return array<string, array{0: array<mixed>, 1: string}>
     */
    public static function invalidInputs(): array
    {
        return [
            'empty list' => [[], 'The API requires at least one input.'],
            'non-list array' => [['first' => new MessagePart('Search text')], 'list of message parts'],
            'non-message part' => [[1], 'index 0 must be a MessagePart'],
            'file part' => [
                [new MessagePart(new File('https://example.com/image.jpg', 'image/jpeg'))],
                'index 0 must be a text part',
            ],
            'blank text part' => [[new MessagePart('   ')], 'index 0 must contain non-empty text'],
        ];
    }

    public function testGenerateEmbeddingResultSendsRequestAndParsesResponse(): void
    {
        $model = new LmStudioEmbeddingGenerationModel(
            $this->createModelMetadata(),
            $this->createProviderMetadata()
        );
        $httpTransporter = $this->createMock(HttpTransporterInterface::class);
        $requestAuthentication = $this->createMock(RequestAuthenticationInterface::class);

        $requestAuthentication
            ->expects($this->once())
            ->method('authenticateRequest')
            ->willReturnArgument(0);

        $httpTransporter
            ->expects($this->once())
            ->method('send')
            ->willReturnCallback(function (Request $request): Response {
                $this->assertSame('http://localhost:1234/v1/embeddings', $request->getUri());
                $this->assertSame('text-embedding-nomic-embed-text-v1.5', $request->getData()['model'] ?? null);
                $this->assertSame(['First', 'Second'], $request->getData()['input'] ?? null);
                return new Response(
                    200,
                    [],
                    (string) json_encode([
                        'object' => 'list',
                        'data' => [
                            ['object' => 'embedding', 'embedding' => [0.1, 0.2, 0.3], 'index' => 0],
                            ['object' => 'embedding', 'embedding' => [0.4, 0.5, 0.6], 'index' => 1],
                        ],
                        'model' => 'text-embedding-nomic-embed-text-v1.5',
                        'usage' => ['prompt_tokens' => 0, 'total_tokens' => 0],
                    ])
                );
            });

        $model->setHttpTransporter($httpTransporter);
        $model->setRequestAuthentication($requestAuthentication);

        $result = $model->generateEmbeddingResult([
            new MessagePart('First'),
            new MessagePart('Second'),
        ]);

        $this->assertSame('', $result->getId());
        $this->assertSame(3, $result->getDimensions());
        $this->assertCount(2, $result->getEmbeddings());
        $this->assertSame([0.1, 0.2, 0.3], $result->getEmbeddings()[0]->getValues());
        $this->assertSame([0.4, 0.5, 0.6], $result->getEmbeddings()[1]->getValues());
        $this->assertSame(0, $result->getTokenUsage()->getTotalTokens());
        $this->assertSame(
            ['object' => 'list', 'model' => 'text-embedding-nomic-embed-text-v1.5'],
            $result->getAdditionalData()
        );
    }

    public function testGenerateEmbeddingResultThrowsWhenDataIsMissing(): void
    {
        $model = new LmStudioEmbeddingGenerationModel(
            $this->createModelMetadata(),
            $this->createProviderMetadata()
        );
        $httpTransporter = $this->createMock(HttpTransporterInterface::class);
        $requestAuthentication = $this->createMock(RequestAuthenticationInterface::class);

        $requestAuthentication->method('authenticateRequest')->willReturnArgument(0);
        $httpTransporter->method('send')->willReturn(new Response(200, [], '{"object":"list"}'));

        $model->setHttpTransporter($httpTransporter);
        $model->setRequestAuthentication($requestAuthentication);

        $this->expectException(ResponseException::class);
        $model->generateEmbeddingResult([new MessagePart('First')]);
    }

    private function createExposedModel(): LmStudioEmbeddingGenerationModel
    {
        return new class (
            $this->createModelMetadata(),
            $this->createProviderMetadata()
        ) extends LmStudioEmbeddingGenerationModel {
            /**
             * @param array<mixed> $inputs
             * @return array<string, mixed>
             */
            public function exposePrepareGenerateEmbeddingsParams(array $inputs): array
            {
                /** @var list<MessagePart> $inputs */
                return $this->prepareGenerateEmbeddingsParams($inputs);
            }
        };
    }

    private function createModelMetadata(): ModelMetadata
    {
        return new ModelMetadata(
            'text-embedding-nomic-embed-text-v1.5',
            'text-embedding-nomic-embed-text-v1.5',
            [CapabilityEnum::embeddingGeneration()],
            []
        );
    }

    private function createProviderMetadata(): ProviderMetadata
    {
        return new ProviderMetadata('lm_studio', 'LM Studio', ProviderTypeEnum::server());
    }
}
