<?php

declare(strict_types=1);

namespace App\Tests\Unit\Post;

use App\Entity\Post;
use App\Post\PullQuoteProcessor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(PullQuoteProcessor::class)]
class PullQuoteProcessorTest extends TestCase
{
    /**
     * @param array<string,string> $postExtra
     * @param array<string,string> $expectedExtra
     */
    #[DataProvider('processProvider')]
    public function testProcess(
        string $postContent,
        array $postExtra,
        string $expectedContent,
        array $expectedExtra
    ): void {
        $processor = new PullQuoteProcessor();
        $post = new Post('Test post', null, '', $postContent, null, false, $postExtra);

        $processor->process($post);

        $this->assertSame($expectedContent, $post->getContent());
        $this->assertSame($expectedExtra, $post->getExtra());
    }

    /**
     * @return array<string,array<mixed>>
     */
    public static function processProvider(): array
    {
        return [
            'nothing' => [ '', [], '', [] ],
            'placeholder, no pullquote' => [ '<!--pullquote1-->', [], '<!--pullquote1-->', [] ],
            'no placeholder, pullquote' => [ '', [ 'pullquote1' => 'test' ], '', [ 'pullquote1' => 'test' ] ],
            'incorrect placeholder number' => [
                '<!--pullquote2-->',
                [ 'pullquote1' => 'test' ],
                '<!--pullquote2-->',
                [ 'pullquote1' => 'test' ],
            ],
            'incorrect placeholder text' => [
                '<!--pullquotea-->',
                [ 'pullquote1' => 'test' ],
                '<!--pullquotea-->',
                [ 'pullquote1' => 'test' ],
            ],
            'placeholder, pullquote' => [
                '<!--pullquote1-->',
                [ 'pullquote1' => 'test' ],
                '<blockquote class="pullquote">test</blockquote>',
                [],
            ],
            '2 placeholders, 1 pullquote' => [
                '<!--pullquote1-->lorem ipsum<!--pullquote2-->',
                [ 'pullquote1' => 'test' ],
                '<blockquote class="pullquote">test</blockquote>lorem ipsum<!--pullquote2-->',
                [],
            ],
            '2 placeholders, 2 pullquotes' => [
                '<!--pullquote1-->lorem ipsum<!--pullquote2-->',
                [ 'pullquote1' => 'test1', 'pullquote2' => 'test2' ],
                <<<CONTENT
<blockquote class="pullquote">test1</blockquote>lorem ipsum<blockquote class="pullquote">test2</blockquote>
CONTENT,
                [],
            ],
        ];
    }
}
