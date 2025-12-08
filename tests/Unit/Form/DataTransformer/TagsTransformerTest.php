<?php

declare(strict_types=1);

namespace App\Tests\Unit\Form\DataTransformer;

use App\Entity\Tag;
use App\Form\DataTransformer\TagsTransformer;
use App\Repository\TagRepository;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;

#[CoversClass(TagsTransformer::class)]
class TagsTransformerTest extends TestCase
{
    #[DataProvider('notArrayProvider')]
    public function testTransformNotArray(mixed $value): void
    {
        $tagRepository = $this->createStub(TagRepository::class);
        $transformer = new TagsTransformer($tagRepository);
        $this->assertNull($transformer->transform($value)); /** @phpstan-ignore argument.type */
    }

    /**
     * @return array<string,array<mixed>>
     */
    public static function notArrayProvider(): array
    {
        return [
            'null' => [ null ],
            'string' => [ 'test' ],
            'integer' => [ 1234 ],
            'collection' => [ new Collection([ 'test' ]) ],
        ];
    }

    /**
     * @param array<mixed> $values
     */
    #[DataProvider('transformNotTagProvider')]
    public function testTransformNotTag(array $values): void
    {
        $tagRepository = $this->createStub(TagRepository::class);
        $transformer = new TagsTransformer($tagRepository);

        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('$value is not a collection of tags');

        $transformer->transform($values); /** @phpstan-ignore argument.type */
    }

    /**
     * @return array<string,array<mixed>>
     */
    public static function transformNotTagProvider(): array
    {
        return [
            'string' => [ [ 'test' ] ],
            'integer' => [ [ 1234 ] ],
            'null' => [ [ null ] ],
            'mixed' => [ [ 'test', 1234, null ] ],
            'tag and string' => [ [ new Tag('Test', 'test'), 'test' ] ],
            'string and tag' => [ [ 'test', new Tag('Test', 'test') ] ],
        ];
    }

    /**
     * @param array<Tag> $values
     * @param array<string> $expected
     */
    #[DataProvider('transformProvider')]
    public function testTransform(array $values, array $expected): void
    {
        $tagRepository = $this->createStub(TagRepository::class);
        $transformer = new TagsTransformer($tagRepository);

        $this->assertSame($expected, $transformer->transform($values));
    }

    /**
     * @return array<string,array<mixed>>
     */
    public static function transformProvider(): array
    {
        $tag1 = new Tag('Test 1', 'test-1');
        $tag2 = new Tag('Test 2', 'test-2');

        return [

            'nothing' => [ [], [] ],
            'one tag' => [ [ $tag1 ], [ 'Test 1' ] ],
            'two tags' => [ [ $tag1, $tag2 ], [ 'Test 1', 'Test 2' ] ],
            'two tags (alt)' => [ [ $tag2, $tag1 ], [ 'Test 2', 'Test 1' ] ],
        ];
    }

    #[DataProvider('notArrayProvider')]
    public function testReverseTransformNotArray(mixed $value): void
    {
        $tagRepository = $this->createStub(TagRepository::class);
        $transformer = new TagsTransformer($tagRepository);

        $this->assertSame([], $transformer->reverseTransform($value)); /** @phpstan-ignore argument.type */
    }

    public function testReverseTransformNoTags(): void
    {
        $tagRepository = $this->createMock(TagRepository::class);
        $tagRepository->expects($this->once())
            ->method('findOrCreate')
            ->with([ 'Test 1' ])
            ->willReturn(new Collection())
        ;

        $transformer = new TagsTransformer($tagRepository, createIfNotFound: false);

        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage("Could not find existing tags for: array (\n  0 => 'Test 1',\n)");

        $transformer->reverseTransform([ 'Test 1' ]);
    }

    public function testReverseTransformCreatesTags(): void
    {
        $tagRepository = $this->createMock(TagRepository::class);
        $tagRepository->expects($this->once())
            ->method('findOrCreate')
            ->with([ 'Test 1' ])
            ->willReturn(new Collection([]))
        ;
        $tagRepository->expects($this->once())
            ->method('createMany')
        ;

        $transformer = new TagsTransformer($tagRepository);
        $tags = $transformer->reverseTransform([ 'Test 1' ]);
        $this->assertCount(1, $tags);
        $this->assertSame('test-1', $tags[0]->getAlias());
    }

    public function testReverseTransformFindsTags(): void
    {
        $tag = new Tag('Test 1', 'test-1');
        $tagRepository = $this->createMock(TagRepository::class);
        $tagRepository->expects($this->once())
            ->method('findOrCreate')
            ->with([ 'Test 1' ])
            ->willReturn(new Collection([ $tag ]))
        ;

        $transformer = new TagsTransformer($tagRepository);
        $this->assertSame([ $tag ], $transformer->reverseTransform([ 'Test 1' ]));
    }

    public function testReverseTransform(): void
    {
        $tag = new Tag('Test 1', 'test-1');
        $tagRepository = $this->createMock(TagRepository::class);
        $tagRepository->expects($this->once())
            ->method('findOrCreate')
            ->with([ 'Test 1', 'Test 2' ])
            ->willReturn(new Collection([ $tag ]))
        ;

        $transformer = new TagsTransformer($tagRepository);
        $tags = $transformer->reverseTransform([ 'Test 1', 'Test 2' ]);
        $this->assertCount(2, $tags);
        $this->assertSame('test-2', $tags[1]->getAlias());
    }
}
