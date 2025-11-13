<?php

declare(strict_types=1);

namespace App\Tests\Unit\Form\DataTransformer;

use App\Entity\Category;
use App\Form\DataTransformer\CategoriesTransformer;
use App\Repository\CategoryRepository;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;

#[CoversClass(CategoriesTransformer::class)]
class CategoriesTransformerTest extends TestCase
{
    #[DataProvider('notArrayProvider')]
    public function testTransformNotArray(mixed $value): void
    {
        $categoryRepository = $this->createMock(CategoryRepository::class);
        $transformer = new CategoriesTransformer($categoryRepository);
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
    #[DataProvider('transformNotCategoryProvider')]
    public function testTransformNotCategory(array $values): void
    {
        $categoryRepository = $this->createMock(CategoryRepository::class);
        $transformer = new CategoriesTransformer($categoryRepository);

        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('$value is not a collection of categories');

        $transformer->transform($values); /** @phpstan-ignore argument.type */
    }

    /**
     * @return array<string,array<mixed>>
     */
    public static function transformNotCategoryProvider(): array
    {
        return [
            'string' => [ [ 'test' ] ],
            'integer' => [ [ 1234 ] ],
            'null' => [ [ null ] ],
            'mixed' => [ [ 'test', 1234, null ] ],
            'category and string' => [ [ new Category('Test', 'test', null), 'test' ] ],
            'string and category' => [ [ 'test', new Category('Test', 'test', null) ] ],
        ];
    }

    /**
     * @param array<Category> $values
     * @param array<string> $expected
     */
    #[DataProvider('transformProvider')]
    public function testTransform(array $values, array $expected): void
    {
        $categoryRepository = $this->createMock(CategoryRepository::class);
        $transformer = new CategoriesTransformer($categoryRepository);

        $this->assertSame($expected, $transformer->transform($values));
    }

    /**
     * @return array<string,array<mixed>>
     */
    public static function transformProvider(): array
    {
        $category1 = new Category('Test 1', 'test-1', null);
        $category2 = new Category('Test 2', 'test-2', null);

        return [

            'nothing' => [ [], [] ],
            'one category' => [ [ $category1 ], [ 'Test 1' ] ],
            'two categories' => [ [ $category1, $category2 ], [ 'Test 1', 'Test 2' ] ],
            'two categories (alt)' => [ [ $category2, $category1 ], [ 'Test 2', 'Test 1' ] ],
        ];
    }

    #[DataProvider('notArrayProvider')]
    public function testReverseTransformNotArray(mixed $value): void
    {
        $categoryRepository = $this->createMock(CategoryRepository::class);
        $transformer = new CategoriesTransformer($categoryRepository);

        $this->assertSame([], $transformer->reverseTransform($value)); /** @phpstan-ignore argument.type */
    }

    public function testReverseTransformNoCategories(): void
    {
        $categoryRepository = $this->createMock(CategoryRepository::class);
        $categoryRepository->expects($this->once())
            ->method('findManyByTitle')
            ->with([ 'Test 1' ])
            ->willReturn(new Collection())
        ;

        $transformer = new CategoriesTransformer($categoryRepository, createIfNotFound: false);

        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage("Could not find existing categories for: array (\n  0 => 'Test 1',\n)");

        $transformer->reverseTransform([ 'Test 1' ]);
    }

    public function testReverseTransformCreatesCategories(): void
    {
        $categoryRepository = $this->createMock(CategoryRepository::class);
        $categoryRepository->expects($this->once())
            ->method('findManyByTitle')
            ->with([ 'Test 1' ])
            ->willReturn(new Collection())
        ;
        $categoryRepository->expects($this->once())
            ->method('createMany')
        ;

        $transformer = new CategoriesTransformer($categoryRepository);
        $this->assertCount(1, $transformer->reverseTransform([ 'Test 1' ]));
    }

    public function testReverseTransformFindsCategories(): void
    {
        $category = new Category('Test 1', 'test-1', null);
        $categoryRepository = $this->createMock(CategoryRepository::class);
        $categoryRepository->expects($this->once())
            ->method('findManyByTitle')
            ->with([ 'Test 1' ])
            ->willReturn(new Collection([ $category ]))
        ;

        $transformer = new CategoriesTransformer($categoryRepository);
        $this->assertSame([ $category ], $transformer->reverseTransform([ 'Test 1' ]));
    }

    public function testReverseTransform(): void
    {
        $category = new Category('Test 1', 'test-1', null);
        $categoryRepository = $this->createMock(CategoryRepository::class);
        $categoryRepository->expects($this->once())
            ->method('findManyByTitle')
            ->with([ 'Test 1', 'Test 2' ])
            ->willReturn(new Collection([ $category ]))
        ;
        $categoryRepository->expects($this->once())
            ->method('createMany')
        ;

        $transformer = new CategoriesTransformer($categoryRepository);
        $this->assertCount(2, $transformer->reverseTransform([ 'Test 1', 'Test 2' ]));
    }
}
