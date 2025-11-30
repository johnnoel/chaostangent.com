<?php

declare(strict_types=1);

namespace App\Tests\Feature\Controller;

use App\Controller\DefaultController;
use App\Factory\CategoryFactory;
use App\Factory\PostFactory;
use App\Factory\TagFactory;
use App\Repository\Criteria\FilterPostsCriteria;
use App\Repository\PostRepository;
use App\Tests\WebTestCase;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(DefaultController::class)]
class DefaultControllerTest extends WebTestCase
{
    public function testIndex(): void
    {
        $client = static::createClient();
        PostFactory::new()->published()->many(5)->create();

        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
    }

    public function testIndexRss(): void
    {
        $client = static::createClient();
        PostFactory::new()->published()->many(5)->create();

        $client->request('GET', '/feed/');

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('rss');
    }

    public function testIndexAtom(): void
    {
        $client = static::createClient();
        PostFactory::new()->published()->many(5)->create();

        $client->request('GET', '/feed/atom/');

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('atom');
    }

    public function testIndexPaginated(): void
    {
        $client = static::createClient();
        PostFactory::new()->published()->many(10)->create();

        /** @var PostRepository $postRepository */
        $postRepository = static::getContainer()->get(PostRepository::class);
        $page1Posts = $postRepository->filterPosts(new FilterPostsCriteria(page: 1, perPage: 5));
        $page2Posts = $postRepository->filterPosts(new FilterPostsCriteria(page: 2, perPage: 5));

        $client->request('GET', '/page/2/');
        $response = $client->getResponse()->getContent();

        foreach ($page2Posts as $post) {
            $this->assertStringContainsString($post->post->getTitle(), $response ?: '');
        }

        foreach ($page1Posts as $post) {
            $this->assertStringNotContainsString($post->post->getTitle(), $response ?: '');
        }

        $this->assertResponseIsSuccessful();
    }

    public function testYear(): void
    {
        $client = static::createClient();
        $posts = PostFactory::new()->published()->many(2)->create([
            'date' => DateTimeImmutable::createFromFormat('Y-m-d', '2025-06-27'),
        ]);

        $otherPosts = PostFactory::new()->published()->many(2)->create([
            'date' => DateTimeImmutable::createFromFormat('Y-m-d', '2024-06-27'),
        ]);

        $client->request('GET', '/2025/');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString($posts[0]->getTitle(), strval($client->getResponse()->getContent()));
        $this->assertStringNotContainsString($otherPosts[0]->getTitle(), strval($client->getResponse()->getContent()));
    }

    public function testYearNotFound(): void
    {
        $client = static::createClient();
        PostFactory::new()->published()->many(2)->create([
            'date' => DateTimeImmutable::createFromFormat('Y-m-d', '2025-06-27'),
        ]);

        $client->request('GET', '/2024/');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testYearPaginated(): void
    {
        $client = static::createClient();
        PostFactory::new()->published()->many(6)->create([
            'date' => DateTimeImmutable::createFromFormat('Y-m-d', '2025-06-27'),
        ]);

        $client->request('GET', '/2025/page/2/');

        $this->assertResponseIsSuccessful();

        $client->request('GET', '/2025/page/3/');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testMonth(): void
    {
        $client = static::createClient();
        $posts = PostFactory::new()->published()->many(2)->create([
            'date' => DateTimeImmutable::createFromFormat('Y-m-d', '2025-06-24'),
        ]);

        $otherPosts = PostFactory::new()->published()->many(2)->create([
            'date' => DateTimeImmutable::createFromFormat('Y-m-d', '2025-05-24'),
        ]);

        $client->request('GET', '/2025/06/');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString($posts[0]->getTitle(), strval($client->getResponse()->getContent()));
        $this->assertStringNotContainsString($otherPosts[0]->getTitle(), strval($client->getResponse()->getContent()));
    }

    public function testMonthNotFound(): void
    {
        $client = static::createClient();
        PostFactory::new()->published()->many(2)->create([
            'date' => DateTimeImmutable::createFromFormat('Y-m-d', '2025-06-24'),
        ]);

        $client->request('GET', '/2025/05/');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testMonthPaginated(): void
    {
        $client = static::createClient();
        PostFactory::new()->published()->many(6)->create([
            'date' => DateTimeImmutable::createFromFormat('Y-m-d', '2025-06-24'),
        ]);

        $client->request('GET', '/2025/06/page/2/');

        $this->assertResponseIsSuccessful();

        $client->request('GET', '/2025/06/page/3/');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testCategory(): void
    {
        $client = static::createClient();
        $categories = CategoryFactory::createMany(2);
        $posts = PostFactory::new()->published()->many(2)->create(static function (int $i) use ($categories) {
            return [ 'categories' => [ $categories[$i - 1] ] ];
        });

        $client->request('GET', '/category/' . $categories[0]->getAlias() . '/');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString($posts[0]->getTitle(), strval($client->getResponse()->getContent()));
        $this->assertStringNotContainsString($posts[1]->getTitle(), strval($client->getResponse()->getContent()));
    }

    public function testCategoryRss(): void
    {
        $client = static::createClient();
        $category = CategoryFactory::createOne();
        $post = PostFactory::new()->published()->create([
            'categories' => [ $category ],
        ]);

        $client->request('GET', '/category/' . $category->getAlias() . '/feed/');

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('rss');
        $this->assertStringContainsString($post->getTitle(), strval($client->getResponse()->getContent()));
    }

    public function testCategoryAtom(): void
    {
        $client = static::createClient();
        $category = CategoryFactory::createOne();
        $post = PostFactory::new()->published()->create([
            'categories' => [ $category ],
        ]);

        $client->request('GET', '/category/' . $category->getAlias() . '/feed/atom/');

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('atom');
        $this->assertStringContainsString($post->getTitle(), strval($client->getResponse()->getContent()));
    }

    public function testCategoryPaginated(): void
    {
        $client = static::createClient();
        $category = CategoryFactory::createOne();
        PostFactory::new()->published()->many(6)->create([
            'categories' => [ $category ],
        ]);

        $client->request('GET', '/category/' . $category->getAlias() . '/page/2/');

        $this->assertResponseIsSuccessful();
    }

    public function testTag(): void
    {
        $client = static::createClient();
        $tags = TagFactory::createMany(2);
        $posts = PostFactory::new()->published()->many(2)->create(static function (int $i) use ($tags) {
            return [ 'tags' => [ $tags[$i - 1] ] ];
        });

        $client->request('GET', '/tag/' . $tags[0]->getAlias() . '/');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString($posts[0]->getTitle(), strval($client->getResponse()->getContent()));
        $this->assertStringNotContainsString($posts[1]->getTitle(), strval($client->getResponse()->getContent()));
    }

    public function testTagRss(): void
    {
        $client = static::createClient();
        $tag = TagFactory::createOne();
        $post = PostFactory::new()->published()->create([
            'tags' => [ $tag ],
        ]);

        $client->request('GET', '/tag/' . $tag->getAlias() . '/feed/');

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('rss');
        $this->assertStringContainsString($post->getTitle(), strval($client->getResponse()->getContent()));
    }

    public function testTagAtom(): void
    {
        $client = static::createClient();
        $tag = TagFactory::createOne();
        $post = PostFactory::new()->published()->create([
            'tags' => [ $tag ],
        ]);

        $client->request('GET', '/tag/' . $tag->getAlias() . '/feed/atom/');

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('atom');
        $this->assertStringContainsString($post->getTitle(), strval($client->getResponse()->getContent()));
    }

    public function testTagPaginated(): void
    {
        $client = static::createClient();
        $tag = TagFactory::createOne();
        PostFactory::new()->published()->many(6)->create([
            'tags' => [ $tag ],
        ]);

        $client->request('GET', '/tag/' . $tag->getAlias() . '/page/2/');

        $this->assertResponseIsSuccessful();
    }

    public function testSitemap(): void
    {
        $client = static::createClient();
        PostFactory::new()->published()->many(5)->create();

        $client->request('GET', '/sitemap.xml');

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('xml');

        $client->request('GET', '/sitemap.default.xml');

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('xml');
    }

    public function testAbout(): void
    {
        $client = static::createClient();

        $client->request('GET', '/about/');

        $this->assertResponseIsSuccessful();
    }
}
