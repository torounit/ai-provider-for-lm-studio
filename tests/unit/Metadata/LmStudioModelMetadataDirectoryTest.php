<?php

declare(strict_types=1);

namespace ToroUnit\LmStudioAiProvider\Tests\unit\Metadata;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ToroUnit\LmStudioAiProvider\Metadata\LmStudioModelMetadataDirectory;
use WordPress\AiClient\Messages\Enums\ModalityEnum;
use WordPress\AiClient\Providers\Http\DTO\Response;
use WordPress\AiClient\Providers\Http\Exception\ResponseException;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Providers\Models\Enums\OptionEnum;

#[CoversClass(LmStudioModelMetadataDirectory::class)]
class LmStudioModelMetadataDirectoryTest extends TestCase
{
    /**
     * @param array<string, string> $modelData            The model entry returned by /api/v0/models.
     * @param list<CapabilityEnum>  $expectedCapabilities The expected capabilities.
     */
    #[DataProvider('modelClassifications')]
    public function testParseResponseClassifiesModels(array $modelData, array $expectedCapabilities): void
    {
        $models = $this->parse(['data' => [$modelData]]);

        $this->assertCount(1, $models);
        $this->assertSame($modelData['id'], $models[0]->getId());
        $this->assertEquals($expectedCapabilities, $models[0]->getSupportedCapabilities());
    }

    /**
     * @return array<string, array{0: array<string, string>, 1: list<CapabilityEnum>}>
     */
    public static function modelClassifications(): array
    {
        $text = [CapabilityEnum::textGeneration(), CapabilityEnum::chatHistory()];
        $embedding = [CapabilityEnum::embeddingGeneration()];

        return [
            'embeddings type' => [
                ['id' => 'text-embedding-nomic-embed-text-v1.5', 'type' => 'embeddings', 'arch' => 'nomic-bert'],
                $embedding,
            ],
            'embedding arch reported as vlm' => [
                [
                    'id' => 'jina-embeddings-v5-omni-small-mlx',
                    'type' => 'vlm',
                    'arch' => 'jina_embeddings_v5_omni',
                ],
                $embedding,
            ],
            'llm' => [['id' => 'liquid/lfm2-24b-a2b', 'type' => 'llm', 'arch' => 'lfm2moe'], $text],
            'vlm' => [['id' => 'qwen/qwen3.5-9b', 'type' => 'vlm', 'arch' => 'qwen35'], $text],
            'missing type and arch' => [['id' => 'some-model'], $text],
        ];
    }

    public function testEmbeddingModelsSupportTextInputAndCustomOptionsOnly(): void
    {
        $models = $this->parse([
            'data' => [['id' => 'text-embedding-nomic-embed-text-v1.5', 'type' => 'embeddings']],
        ]);

        $options = [];
        foreach ($models[0]->getSupportedOptions() as $option) {
            $options[$option->getName()->value] = $option->getSupportedValues();
        }

        $this->assertSame(
            [
                OptionEnum::inputModalities()->value => [[ModalityEnum::text()]],
                OptionEnum::customOptions()->value => null,
            ],
            $options
        );
    }

    public function testParseResponseThrowsWhenDataIsMissing(): void
    {
        $this->expectException(ResponseException::class);
        $this->parse(['object' => 'list']);
    }

    /**
     * @param array<string, mixed> $responseData The response body.
     * @return list<ModelMetadata>
     */
    private function parse(array $responseData): array
    {
        $directory = new class extends LmStudioModelMetadataDirectory {
            /**
             * @return list<ModelMetadata>
             */
            public function exposeParseResponseToModelMetadataList(Response $response): array
            {
                return $this->parseResponseToModelMetadataList($response);
            }
        };

        return $directory->exposeParseResponseToModelMetadataList(
            new Response(200, [], (string) json_encode($responseData))
        );
    }
}
