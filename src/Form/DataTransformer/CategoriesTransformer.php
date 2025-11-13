<?php

declare(strict_types=1);

namespace App\Form\DataTransformer;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\String\Slugger\AsciiSlugger;

/**
 * @implements DataTransformerInterface<array<Category>,array<string>>
 */
readonly final class CategoriesTransformer implements DataTransformerInterface
{
    public function __construct(
        private CategoryRepository $categoryRepository,
        private bool $createIfNotFound = true
    ) {
    }

    /**
     * @return array<string>|null
     */
    public function transform(mixed $value): ?array
    {
        if (!is_array($value)) {
            return null;
        }

        $ret = [];

        foreach ($value as $category) {
            if (!($category instanceof Category)) { /** @phpstan-ignore instanceof.alwaysTrue */
                throw new TransformationFailedException('$value is not a collection of categories');
            }

            $ret[] = $category->getTitle();
        }

        return $ret;
    }

    /**
     * @return array<Category>
     */
    public function reverseTransform(mixed $value): array
    {
        if (!is_array($value) || count($value) === 0) {
            return [];
        }

        $categoryTitles = array_unique(array_filter(array_map('trim', $value)));
        $categories = $this->categoryRepository->findManyByTitle($categoryTitles);

        $foundCategories = $categories->map(fn (Category $category) => $category->getTitle())->all();
        $notFound = array_diff($value, $foundCategories);

        if (count($notFound) > 0) {
            if (!$this->createIfNotFound) {
                throw new TransformationFailedException(
                    'Could not find existing categories for: ' . var_export($notFound, true)
                );
            }

            $slugger = new AsciiSlugger();
            $toCreate = array_map(
                fn (string $t): Category => new Category($t, $slugger->slug($t)->toString()),
                $notFound
            );

            $this->categoryRepository->createMany(...$toCreate);
            $categories = $categories->merge($toCreate);
        }

        return $categories->all();
    }
}
