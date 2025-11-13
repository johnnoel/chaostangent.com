<?php

declare(strict_types=1);

namespace App\Form\DataTransformer;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * @implements DataTransformerInterface<array<Category>,array<string>>
 */
readonly final class CategoriesTransformer implements DataTransformerInterface
{
    public function __construct(private CategoryRepository $categoryRepository)
    {
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

        if ($categories->isEmpty()) {
            throw new TransformationFailedException('No categories found for ' . var_export($value, true));
        }

        return $categories->all();
    }
}
