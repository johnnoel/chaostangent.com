<?php

declare(strict_types=1);

namespace App\Tests\Unit\Comment;

use App\Comment\AkismetClient;
use App\Comment\AkismetDecider;
use App\Form\Model\CommentModel;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\ResponseInterface;

#[CoversClass(AkismetDecider::class)]
class AkismetDeciderTest extends TestCase
{
    #[DataProvider('isSpamProvider')]
    public function testIsSpam(ResponseInterface $response, bool $expected): void
    {
        $mockHttpClient = new MockHttpClient([ $response ]);

        $akismetClient = new AkismetClient('https://chaostangent.com', 'test123', $mockHttpClient);
        $decider = new AkismetDecider($akismetClient);
        $model = new CommentModel();
        $model->authorName = 'Test';
        $model->authorEmail = 'test@test.test';
        $model->authorUrl = 'https://test.test/test/';
        $model->comment = 'Test comment';

        $this->assertSame($expected, $decider->isSpam($model));
    }

    /**
     * @return array<string,mixed>
     */
    public static function isSpamProvider(): array
    {
        return [
            'ham' => [ new MockResponse('ham'), false ],
            'spam' => [ new MockResponse('true'), true ],
            'akismet down' => [ new MockResponse(info: [ 'error' => 'Akismet not available' ]), false ],
            'response problem' => [ new MockResponse([ new RuntimeException('Problems with response') ]), false ],
        ];
    }
}
