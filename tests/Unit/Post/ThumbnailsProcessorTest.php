<?php

declare(strict_types=1);

namespace App\Tests\Unit\Post;

use App\Entity\Post;
use App\Post\ThumbnailsProcessor;
use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(ThumbnailsProcessor::class)]
class ThumbnailsProcessorTest extends TestCase
{
    #[DataProvider('processProvider')]
    public function testProcess(string $content, string $expected): void
    {
        $processor = new ThumbnailsProcessor();
        $post = new Post('Test', null, 'test', $content, null, false, []);
        $processor->process($post);

        $this->assertSame($expected, $post->getContent());
    }

    /**
     * @return array<string,mixed>
     */
    public static function processProvider(): array
    {
        // phpcs:disable Generic.Files.LineLength
        $thumbnailsOne = <<<HTML
            <p class="thumbnails one">
                <a href="https://chaostangent.com/media/2025/08/test.jpg"><img src="?g=oldthumb&amp;c=1280x548%2B0%2B86&amp;sig=aaaaa" alt=""></a>
            </p>
        HTML;

        // switch the alt attribute around
        $thumbnailsOneAlt = <<<HTML
            <p class="thumbnails one">
                <a href="https://chaostangent.com/media/2025/08/test.jpg"><img alt="" src="?g=oldthumb&amp;c=1280x548%2B0%2B86&amp;sig=aaaaa"></a>
            </p>
        HTML;


        $expectedOne = <<<TWIG
            {{ thumbnails([ { 'src': '2025/08/test.jpg', 'actions': [ 'crop:1280x548+0+86', 'resize:thumb' ] } ]) }}
        TWIG;

        $thumbnailsMany = <<<HTML
            <p class="thumbnails four">
                <a href="https://chaostangent.com/media/2025/08/test1.jpg"><img src="?g=oldthumb&amp;c=1280x548%2B0%2B86&amp;sig=aaaaa" alt=""></a>
                <a href="https://chaostangent.com/media/2025/08/test2.jpg"><img src="?g=oldlead&amp;c=1280x548%2B1%2B86&amp;sig=aaaaa" alt=""></a>
                <a href="https://chaostangent.com/media/2025/08/test3.jpg"><img src="?g=oldposter&amp;c=1280x548%2B2%2B86&amp;sig=aaaaa" alt=""></a>
                <a href="https://chaostangent.com/media/2025/08/test4.jpg"><img src="?g=oldsquare&amp;c=1280x548%2B3%2B86&amp;sig=aaaaa" alt=""></a>
            </p>
        HTML;

        $expectedMany = <<<TWIG
            {{ thumbnails([ { 'src': '2025/08/test1.jpg', 'actions': [ 'crop:1280x548+0+86', 'resize:thumb' ] },
        { 'src': '2025/08/test2.jpg', 'actions': [ 'crop:1280x548+1+86', 'resize:lead' ] },
        { 'src': '2025/08/test3.jpg', 'actions': [ 'crop:1280x548+2+86', 'resize:poster' ] },
        { 'src': '2025/08/test4.jpg', 'actions': [ 'crop:1280x548+3+86', 'resize:square' ] } ]) }}
        TWIG;

        $wrongUrl = <<<HTML
            <p class="thumbnails one">
                <a href="https://pixiv.net/some_url"><img src="?g=oldthumb&amp;c=1280x548%2B0%2B86&amp;sig=aaaaa" alt=""></a>
            </p>
        HTML;

        $noQueryString = <<<HTML
            <p class="thumbnails one">
                <a href="https://chaostangent.com/media/2025/08/test.jpg"><img src="https://chaostangent.com/media/2025/08/test-240x340.jpg" alt=""></a>
            </p>
        HTML;
        // phpcs:enable

        return [
            'nothing' => [ '', '' ],
            'just text' => [ 'Lorem ipsum dolor sit amet', 'Lorem ipsum dolor sit amet' ],
            'one' => [ $thumbnailsOne, $expectedOne ],
            'one alt attribute' => [ $thumbnailsOneAlt, $expectedOne ],
            'multiple' => [ $thumbnailsOne . $thumbnailsMany, $expectedOne . $expectedMany ],
            'many' => [ $thumbnailsMany, $expectedMany ],
            'wrong url' => [ $wrongUrl, $wrongUrl ],
            'wrong url amongst many' => [
                $thumbnailsOne . $wrongUrl . $expectedMany,
                $expectedOne . $wrongUrl . $expectedMany,
            ],
        ];
    }

    #[DataProvider('processThrowsExceptionProvider')]
    public function testProcessThrowsException(string $content, string $expectedMessage): void
    {
        $processor = new ThumbnailsProcessor();
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
                '<p class="thumbnails one"><a href="https://chaostangent.com/media/test.jpg"></a></p>',
                'No images within the link: <a href="https://chaostangent.com/media/test.jpg"></a>',
            ],
            'no image query string' => [
                '<p class="thumbnails four"><a href="https://chaostangent.com/media/test.jpg"><img src="abc"></a></p>',
                'No query string on the image source: abc',
            ],
            'query string but no group' => [
                '<p class="thumbnails two"><a href="https://chaostangent.com/media/1.jpg"><img src="abc?t=1"></a></p>',
                'No group found in query string: t=1',
            ],
            'unknown group' => [
                '<p class="thumbnails"><a href="https://chaostangent.com/media/test.jpg"><img src="?g=test"></a></p>',
                'Unknown group found: \'test\'',
            ],
        ];
    }
}
