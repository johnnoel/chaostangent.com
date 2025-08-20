<?php

declare(strict_types=1);

namespace App\Tests\Feature\Controller;

use App\Comment\SpamDecider;
use App\Comment\StaticDecider;
use App\Controller\CommentsController;
use App\Factory\CommentFactory;
use App\Factory\PostFactory;
use App\Form\DataTransformer\SignedDateTransformer;
use App\Tests\WebTestCase;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\UriSigner;

#[CoversClass(CommentsController::class)]
class CommentsControllerTest extends WebTestCase
{
    /**
     * @param array{authorName: string, authorEmail: string, comment: string, authorUrl?: string} $postData
     */
    #[DataProvider('commentProvider')]
    public function testComment(array $postData): void
    {
        $client = static::createClient();
        $post = PostFactory::new()->published()->create();
        $postUrl = sprintf(
            '/%s/%s/%s/',
            $post->getDate()?->format('Y'),
            $post->getDate()?->format('m'),
            $post->getAlias()
        );
        $commentUrl = $postUrl . 'comment/';

        /** @var SignedDateTransformer $transformer */
        $transformer = static::getContainer()->get(SignedDateTransformer::class);
        $postData['formRendered'] = $transformer->transform(new DateTimeImmutable('-10 minutes'));

        $client->request('POST', $commentUrl, [ 'comment' => $postData ]);

        $this->assertResponseRedirects($postUrl . '#respond');
        $client->followRedirect();
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString($postData['comment'], strval($client->getResponse()->getContent()));
    }

    public function testCommentSpam(): void
    {
        $client = static::createClient();
        $post = PostFactory::new()->published()->create();
        $postUrl = sprintf(
            '/%s/%s/%s/',
            $post->getDate()?->format('Y'),
            $post->getDate()?->format('m'),
            $post->getAlias()
        );
        $commentUrl = $postUrl . 'comment/';

        $container = static::getContainer();

        $container->set(SpamDecider::class, new StaticDecider(true));

        /** @var SignedDateTransformer $transformer */
        $transformer = $container->get(SignedDateTransformer::class);

        $client->request('POST', $commentUrl, [ 'comment' => [
            'authorName' => 'Test author',
            'authorEmail' => 'test@test.test',
            'comment' => 'Test test test test test test',
            'formRendered' => $transformer->transform(new DateTimeImmutable('-10 minutes')),
        ] ]);

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString(
            'Your comment has been marked as spam',
            strval($client->getResponse()->getContent())
        );
    }

    /**
     * @return array<string,array<mixed>>
     */
    public static function commentProvider(): array
    {
        $default = [
            'authorName' => 'Test author',
            'authorEmail' => 'test@test.test',
            'comment' => 'Test test test test test test',
        ];

        return [
            'minimal' => [ $default ],
            'with url' => [ array_merge($default, [ 'authorUrl' => 'https://chaostangent.com' ]) ],
        ];
    }

    /**
     * @param array<string,mixed> $postData
     */
    #[DataProvider('commentFailProvider')]
    public function testCommentFail(array $postData, string $expectedMessage): void
    {
        $client = static::createClient();
        $post = PostFactory::new()->published()->create();
        $postUrl = sprintf(
            '/%s/%s/%s/',
            $post->getDate()?->format('Y'),
            $post->getDate()?->format('m'),
            $post->getAlias()
        );
        $commentUrl = $postUrl . 'comment/';

        /** @var SignedDateTransformer $transformer */
        $transformer = static::getContainer()->get(SignedDateTransformer::class);
        $postData['formRendered'] = $transformer->transform(new DateTimeImmutable('-10 minutes'));

        $client->request('POST', $commentUrl, [ 'comment' => $postData ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertStringContainsString($expectedMessage, strval($client->getResponse()->getContent()));
    }

    /**
     * @return array<string,array<mixed>>
     */
    public static function commentFailProvider(): array
    {
        $default = [
            'authorName' => 'Test author',
            'authorEmail' => 'test@test.test',
            'comment' => 'Test test test test test test',
        ];

        return [
            'no author name' => [ array_diff_key($default, [ 'authorName' => null ]), 'Please enter your name' ],
            'short author name' => [
                array_merge($default, [ 'authorName' => 'A' ]),
                'Please enter at least 2 characters for your name',
            ],
            'long author name' => [
                array_merge($default, [ 'authorName' => str_repeat('a', 256) ]),
                'Please enter at most 255 characters for your name',
            ],
            'no author email' => [
                array_diff_key($default, [ 'authorEmail' => null ]),
                'Please enter your email address',
            ],
            'long author email' => [
                array_merge($default, [ 'authorEmail' => str_repeat('a', 255) . '@a.a' ]),
                'Please enter at most 255 characters for your email address',
            ],
            'invalid author email' => [
                array_merge($default, [ 'authorEmail' => '!"$£%^&*(' ]),
                'Please enter a valid email address',
            ],
            'invalid author url' => [
                array_merge($default, [ 'authorUrl' => 'ftp://test.test/test.ext' ]),
                'Please enter a URL beginning with http or https',
            ],
            'long author url' => [
                array_merge($default, [ 'authorUrl' => 'https://' . str_repeat('a', 1024) . '.com' ]),
                'Please enter at most 1024 characters for your URL',
            ],
            'no comment' => [ array_diff_key($default, [ 'comment' => null ]), 'Please enter a comment' ],
            'short comment' => [
                array_merge($default, [ 'comment' => 'Test test' ]),
                'Please enter at least 10 characters for your comment',
            ],
            'long comment' => [
                array_merge($default, [ 'comment' => str_repeat('a', 8193) ]),
                'Please enter at most 8192 characters for your comment',
            ],
        ];
    }

    #[DataProvider('commentNotFoundProvider')]
    public function testCommentNotFound(string $url): void
    {
        $client = static::createClient();
        PostFactory::new()->published()->create([
            'date' => DateTimeImmutable::createFromFormat('Y-m-d', '2025-08-02'),
            'alias' => 'test-post-1',
        ]);
        PostFactory::new()->create([ // unpublished
            'date' => DateTimeImmutable::createFromFormat('Y-m-d', '2025-08-02'),
            'alias' => 'test-post-2',
        ]);

        /** @var SignedDateTransformer $transformer */
        $transformer = static::getContainer()->get(SignedDateTransformer::class);

        $postData = [
            'comment' => [
                'authorName' => 'Test author',
                'authorEmail' => 'test@test.test',
                'authorUrl' => 'https://chaostangent.com',
                'comment' => 'Test test test test test',
                'formRendered' => $transformer->transform(new DateTimeImmutable('-10 minutes')),
            ],
        ];

        $client->request('POST', '/2025/08/test-post-1/comment/', $postData);
        $this->assertResponseRedirects('/2025/08/test-post-1/#respond');

        $client->request('POST', $url, $postData);
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    /**
     * @return array<string,array<string>>
     */
    public static function commentNotFoundProvider(): array
    {
        return [
            'incorrect alias' => [ '/2025/08/test-post-0/comment/' ],
            'incorrect year' => [ '2024/08/test-post-1/comment/' ],
            'incorrect month' => [ '2025/09/test-post-1/comment/' ],
            'unpublished' => [ '2025/08/test-post-2/comment/' ],
        ];
    }

    public function testCommentNoFormRenderedValue(): void
    {
        $client = static::createClient();
        PostFactory::new()->published()->create([
            'date' => DateTimeImmutable::createFromFormat('Y-m-d', '2025-08-20'),
            'alias' => 'test-post-1',
        ]);

        $postData = [
            'comment' => [
                'authorName' => 'Test author',
                'authorEmail' => 'test@test.test',
                'authorUrl' => 'https://chaostangent.com',
                'comment' => 'Test test test test test',
            ],
        ];

        $client->request('POST', '/2025/08/test-post-1/comment/', $postData);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testMarkCommentAsSpam(): void
    {
        $client = static::createClient();
        $post = PostFactory::new()->published()->create();
        $comment = CommentFactory::createOne([ 'post' => $post ]);

        /** @var UriSigner $uriSigner */
        $uriSigner = static::getContainer()->get(UriSigner::class);
        $signedUri = $uriSigner->sign('http://localhost/comment/' . $comment->getId() . '/spam');

        $postUrl = sprintf(
            '/%s/%s/%s/',
            $post->getDate()?->format('Y'),
            $post->getDate()?->format('m'),
            $post->getAlias()
        );

        $this->assertFalse($comment->isSpam());

        $client->request('POST', $signedUri);
        $this->assertResponseRedirects($postUrl);
        $this->assertTrue($comment->isSpam());
    }

    public function testMarkCommentAsSpamUnsigned(): void
    {
        $client = static::createClient();
        $post = PostFactory::new()->published()->create();
        $comment = CommentFactory::createOne([ 'post' => $post ]);

        $this->assertFalse($comment->isSpam());

        $client->request('POST', '/comment/' . $comment->getId() . '/spam');
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $this->assertFalse($comment->isSpam());
    }

    public function testMarkCommentAsUnapproved(): void
    {
        $client = static::createClient();
        $post = PostFactory::new()->published()->create();
        $comment = CommentFactory::createOne([ 'post' => $post ]);

        /** @var UriSigner $uriSigner */
        $uriSigner = static::getContainer()->get(UriSigner::class);
        $signedUri = $uriSigner->sign('http://localhost/comment/' . $comment->getId() . '/unapprove');

        $postUrl = sprintf(
            '/%s/%s/%s/',
            $post->getDate()?->format('Y'),
            $post->getDate()?->format('m'),
            $post->getAlias()
        );

        $this->assertTrue($comment->isApproved());

        $client->request('POST', $signedUri);
        $this->assertResponseRedirects($postUrl);
        $this->assertFalse($comment->isApproved());
    }

    public function testMarkCommentAsUnapprovedUnsigned(): void
    {
        $client = static::createClient();
        $post = PostFactory::new()->published()->create();
        $comment = CommentFactory::createOne([ 'post' => $post ]);

        $this->assertTrue($comment->isApproved());

        $client->request('POST', '/comment/' . $comment->getId() . '/unapprove');
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $this->assertTrue($comment->isApproved());
    }
}
