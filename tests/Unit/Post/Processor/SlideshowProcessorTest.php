<?php

declare(strict_types=1);

namespace App\Tests\Unit\Post\Processor;

use App\Entity\Post;
use App\Post\Processor\SlideshowProcessor;
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

        $linkNoImage = '<p class="slideshow"><a href="https://chaostangent.com/media/test.jpg"></a></p>';
        $noImageQueryString = '<p class="thumbnails four"><a href="https://chaostangent.com/media/test.jpg"><img src="abc"></a></p>';
        $noGroup = '<p class="thumbnails two"><a href="https://chaostangent.com/media/1.jpg"><img src="abc?t=1"></a></p>';
        $unknownGroup = '<p class="thumbnails"><a href="https://chaostangent.com/media/test.jpg"><img src="?g=test"></a></p>';
        // phpcs:enable

        return [
            'nothing' => [ '', '' ],
            'just text' => [ 'Lorem ipsum dolor sit amet', 'Lorem ipsum dolor sit amet' ],
            'no images' => [ '<p class="slideshow"></p>', '<p class="slideshow"></p>' ],
            'one' => [ $slideshowOne, $expectedOne ],
            'many' => [ $slideshowMany, $expectedMany ],
            'captions' => [ $slideshowCaptions, $expectedCaptions ],
            'link no image' => [ $linkNoImage, $linkNoImage ],
            'no image query string' => [ $noImageQueryString, $noImageQueryString ],
            'query string but no group' => [ $noGroup, $noGroup],
            'unknown group' => [ $unknownGroup, $unknownGroup ],
        ];
    }
}
