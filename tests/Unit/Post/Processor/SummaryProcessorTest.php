<?php

declare(strict_types=1);

namespace App\Tests\Unit\Post\Processor;

use App\Entity\Post;
use App\Post\Processor\SummaryProcessor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(SummaryProcessor::class)]
class SummaryProcessorTest extends TestCase
{
    #[DataProvider('processProvider')]
    public function testProcess(string $postContent, ?string $expectedSummary, string $expectedContent): void
    {
        $processor = new SummaryProcessor();
        $post = new Post('Test post', null, '', $postContent, null, false, []);

        $processor->process($post);

        $this->assertSame($expectedSummary, $post->getSummary());
        $this->assertSame($expectedContent, $post->getContent());
    }

    /**
     * @return array<string,array<mixed>>
     */
    public static function processProvider(): array
    {
        return [
            'nothing' => [ '', null, '' ],
            'just content' => [ 'lorem ipsum dolor sit amet', null, 'lorem ipsum dolor sit amet' ],
            'placeholder no content' => [ '<!--more-->', '', '' ],
            'placeholder content before' => [ 'lorem ipsum<!--more-->', 'lorem ipsum', 'lorem ipsum' ],
            'placeholder content after' => [ '<!--more-->lorem ipsum', '', 'lorem ipsum' ],
            'placeholder content' => [ 'lorem ipsum<!--more-->dolor sit', 'lorem ipsum', 'lorem ipsumdolor sit' ],
            'multiple' => [
                'lorem ipsum<!--more-->dolor sit<!--more-->',
                'lorem ipsum',
                'lorem ipsumdolor sit<!--more-->',
            ],
            'suffix' => [ 'lorem ipsum<!--moreabcdefg-->dolor sit', 'lorem ipsum', 'lorem ipsumdolor sit' ],
        ];
    }
}
