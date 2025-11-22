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

    /**
     * @param array<string> $values
     */
    #[DataProvider('reverseTransformProvider')]
    public function testReverseTransform(array $values, int $expectedFound, int $expectedCreated): void
    {
        $found = new Category('Test Found', 'test-found', null);
        $subFound = new Category('Test Sub Found', 'test-found/test-sub-found', $found);
        $alsoFound = new Category('Test Also Found', 'test-also-found', null);
        $subAlsoFound = new Category('Test Sub Also Found', 'test-also-found/test-sub-also-found', $alsoFound);

        $categoryRepository = $this->createMock(CategoryRepository::class);
        $categoryRepository->method('findOneBy')
            ->willReturnMap([
                [ [ 'alias' => 'test-found' ], $found ],
                [ [ 'alias' => 'test-not-found' ], null ],
                [ [ 'alias' => 'test-found/test-sub-found' ], $subFound ],
                [ [ 'alias' => 'test-found/test-sub-not-found' ], null ],
                [ [ 'alias' => 'test-not-found/test-sub-not-found' ], null ],
                [ [ 'alias' => 'test-also-found' ], $alsoFound ],
                [ [ 'alias' => 'test-also-not-found' ], null ],
                [ [ 'alias' => 'test-also-found/test-sub-also-found' ], $subAlsoFound ],
            ])
        ;

        $categoryRepository->expects($this->exactly($expectedCreated > 0 ? 1 : 0))
            ->method('createMany')
        ;

        $transformer = new CategoriesTransformer($categoryRepository, createIfNotFound: true);
        $categories = $transformer->reverseTransform($values);
        $this->assertCount(count($values), $categories);

        $actuallyFound = 0;
        $actuallyCreated = 0;
        foreach ($categories as $category) {
            if (in_array($category, [ $found, $alsoFound, $subFound, $subAlsoFound ], true)) {
                $actuallyFound++;
            } else {
                $actuallyCreated++;
            }
        }

        $this->assertSame($actuallyFound, $expectedFound);
        $this->assertSame($actuallyCreated, $expectedCreated);
    }

    /**
     * @return array<string,mixed>
     */
    public static function reverseTransformProvider(): array
    {
        return [
            'Top level, found' => [ [ 'Test Found' ], 1, 0 ],
            'Top level, not found' => [ [ 'Test Not Found' ], 0, 1 ],
            'Two levels, both found' => [ [ 'Test Found / Test Sub Found' ], 1, 0 ],
            'Two levels, parent found' => [ [ 'Test Found / Test Sub Not Found' ], 0, 1 ],
            'Two levels, neither found' => [ [ 'Test Not Found / Test Sub Not Found' ], 0, 1 ],
            'Multiple top level, all found' => [ [ 'Test Found', 'Test Also Found' ], 2, 0 ],
            'Multiple top level, one not found' => [ [ 'Test Found', 'Test Not Found' ], 1, 1 ],
            'Multiple top level, both not found' => [ [ 'Test Not Found', 'Test Also Not Found' ], 0, 2 ],
            'Multiple two level, all found' => [
                [ 'Test Found / Test Sub Found', 'Test Also Found / Test Sub Also Found' ], 2, 0,
            ],
            'Multiple two level, sub not found' => [
                [ 'Test Found / Test Sub Not Found', 'Test Also Found / Test Sub Also Not Found' ], 0, 2,
            ],
            'Multiple two level, common ancestor' => [
                [ 'Test Found / Test Sub Not Found', 'Test Found / Test Sub Also Not Found' ], 0, 2,
            ],
        ];
    }

    public function testReverseTransformNoCategories(): void
    {
        $categoryRepository = $this->createMock(CategoryRepository::class);
        $categoryRepository->expects($this->once())
            ->method('findOneBy')
            ->with([ 'alias' => 'test-1' ])
            ->willReturn(null)
        ;

        $transformer = new CategoriesTransformer($categoryRepository, createIfNotFound: false);

        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage("Could not find existing categories for: Test 1");

        $transformer->reverseTransform([ 'Test 1' ]);
    }
}
