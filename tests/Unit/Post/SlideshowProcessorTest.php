<?php

declare(strict_types=1);

namespace App\Tests\Unit\Post;

use App\Entity\Post;
use App\Post\SlideshowProcessor;
use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(SlideshowProcessor::class)]
class SlideshowProcessorTest extends TestCase
{
    #[DataProvider('processProvider')]
    public function testProcess(string $content, string $expected): void
    {
        $processor = new SlideshowProcessor();
        $post = new Post('Test', null, 'test', $content, null, false, []);
        $processor->process($post);

        $this->assertSame($expected, $post->getContent());
    }

    /**
     * @return array<string,array<mixed>>
     */
    public static function processProvider(): array
    {
        // phpcs:disable Generic.Files.LineLength
        $slideshowOne = <<<HTML
            <p class="slideshow">
                <a href="https://chaostangent.com/media/2025/09/test.jpg"><img src="?g=oldthumb&amp;c=1280x548%2B0%2B86&amp;sig=aaaaa" alt=""></a>
            </p>
        HTML;

        $expectedOne = <<<TWIG
            {{ slideshow([ { 'src': '2025/09/test.jpg', 'actions': [ 'crop:1280x548+0+86', 'resize:thumb' ] } ]) }}
        TWIG;

        $slideshowMany = <<<HTML
            <p class="slideshow">
                <a href="https://chaostangent.com/media/2025/09/test1.jpg"><img src="?g=oldthumb&amp;c=1280x548%2B0%2B86&amp;sig=aaaaa" alt=""></a>
                <a href="https://chaostangent.com/media/2025/09/test2.jpg"><img src="?g=oldthumb&amp;c=1280x548%2B0%2B86&amp;sig=aaaaa" alt=""></a>
                <a href="https://chaostangent.com/media/2025/09/test3.jpg"><img src="?g=oldthumb&amp;c=1280x548%2B0%2B86&amp;sig=aaaaa" alt=""></a>
                <a href="https://chaostangent.com/media/2025/09/test4.jpg"><img src="?g=oldthumb&amp;c=1280x548%2B0%2B86&amp;sig=aaaaa" alt=""></a>
            </p>
        HTML;

        $expectedMany = <<<TWIG
            {{ slideshow([ { 'src': '2025/09/test1.jpg', 'actions': [ 'crop:1280x548+0+86', 'resize:thumb' ] },
        { 'src': '2025/09/test2.jpg', 'actions': [ 'crop:1280x548+0+86', 'resize:thumb' ] },
        { 'src': '2025/09/test3.jpg', 'actions': [ 'crop:1280x548+0+86', 'resize:thumb' ] },
        { 'src': '2025/09/test4.jpg', 'actions': [ 'crop:1280x548+0+86', 'resize:thumb' ] } ]) }}
        TWIG;

        $slideshowCaptions = <<<HTML
            <p class="slideshow">
                <a href="https://chaostangent.com/media/2025/09/test1.jpg"><img title="abc" src="?g=oldthumb&amp;c=1280x548%2B0%2B86&amp;sig=aaaaa" alt=""></a>
                <a href="https://chaostangent.com/media/2025/09/test2.jpg"><img src="?g=oldthumb&amp;c=1280x548%2B0%2B86&amp;sig=aaaaa" title="def" alt=""></a>
                <a href="https://chaostangent.com/media/2025/09/test3.jpg"><img src="?g=oldthumb&amp;c=1280x548%2B0%2B86&amp;sig=aaaaa" alt="" title="ghi"></a>
                <a href="https://chaostangent.com/media/2025/09/test4.jpg"><img src="?g=oldthumb&amp;c=1280x548%2B0%2B86&amp;sig=aaaaa" alt=""></a>
            </p>
        HTML;

        $expectedCaptions = <<<TWIG
            {{ slideshow([ { 'src': '2025/09/test1.jpg', 'actions': [ 'crop:1280x548+0+86', 'resize:thumb' ], 'caption': 'abc' },
        { 'src': '2025/09/test2.jpg', 'actions': [ 'crop:1280x548+0+86', 'resize:thumb' ], 'caption': 'def' },
        { 'src': '2025/09/test3.jpg', 'actions': [ 'crop:1280x548+0+86', 'resize:thumb' ], 'caption': 'ghi' },
        { 'src': '2025/09/test4.jpg', 'actions': [ 'crop:1280x548+0+86', 'resize:thumb' ] } ]) }}
        TWIG;
        // phpcs:enable

        return [
            'nothing' => [ '', '' ],
            'just text' => [ 'Lorem ipsum dolor sit amet', 'Lorem ipsum dolor sit amet' ],
            'no images' => [ '<p class="slideshow"></p>', '<p class="slideshow"></p>' ],
            'one' => [ $slideshowOne, $expectedOne ],
            'many' => [ $slideshowMany, $expectedMany ],
            'captions' => [ $slideshowCaptions, $expectedCaptions ],
        ];
    }

    #[DataProvider('processThrowsExceptionProvider')]
    public function testProcessThrowsException(string $content, string $expectedMessage): void
    {
        $processor = new SlideshowProcessor();
        $post = new Post('Test', null, 'test', $content, null, false, []);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage($expectedMessage);

        $processor->process($post);
    }

    /**
     * @return array<string,array<mixed>>
     */
    public static function processThrowsExceptionProvider(): array
    {
        return [
            'link no image' => [
                '<p class="slideshow"><a href="https://chaostangent.com/media/test.jpg"></a></p>',
                'No images within the link: <a href="https://chaostangent.com/media/test.jpg"></a>',
            ],
            'no image query string' => [
                '<p class="slideshow"><a href="https://chaostangent.com/media/test.jpg"><img src="abc"></a></p>',
                'No query string on the image source: abc',
            ],
            'query string but no group' => [
                '<p class="slideshow"><a href="https://chaostangent.com/media/test.jpg"><img src="abc?t=1"></a></p>',
                'No group found in query string: t=1',
            ],
            'unknown group' => [
                '<p class="slideshow"><a href="https://chaostangent.com/media/test.jpg"><img src="?g=test"></a></p>',
                'Unknown group found: \'test\'',
            ],
        ];
    }
}
