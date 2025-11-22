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

        // Anime / 3 Episode Taste Test - hierarchy, path information so found
        // Short Stories - no path information, not found, top level creation
        // Holidays / Japan 2023 - hierarchy, not found, parent found, need to create with parent
        // New Category / New Subcategory - hierarchy, none found, need to create parent and child

        // devtodo Need a common collection of categories to create in case there's a common mission ancestor amongst
        // several titles e.g. [ 'Not Found / One', 'Not Found / Two' ], current 'Not Found' would attempt to be created
        // twice which would throw an error

        $categoryTitles = array_unique(array_filter(array_map('trim', $value)));
        $categories = [];
        $slugger = new AsciiSlugger();
        $notFound = [];

        foreach ($categoryTitles as $categoryTitle) {
            $pathParts = array_filter(array_map('trim', explode('/', $categoryTitle)));
            $pathSoFar = [];
            $lastFound = null;

            foreach ($pathParts as $title) {
                $alias = $slugger->slug($title)->lower()->toString();
                $pathSoFar[] = $alias;
                $path = implode('/', $pathSoFar);
                $category = $this->categoryRepository->findOneBy([ 'alias' => $path ]);

                if ($category === null) {
                    $category = new Category($title, $path, $lastFound);
                    $notFound[] = $category;
                }

                $lastFound = $category;
            }

            if ($lastFound instanceof Category) {
                $categories[] = $lastFound;
            }
        }

        if (count($notFound) > 0) {
            if (!$this->createIfNotFound) {
                $titles = array_map(fn (Category $c): string => $c->getTitle(), $notFound);
                throw new TransformationFailedException(
                    'Could not find existing categories for: ' . implode(', ', $titles)
                );
            }

            $this->categoryRepository->createMany(...$notFound);
        }

        return $categories;
    }
}
