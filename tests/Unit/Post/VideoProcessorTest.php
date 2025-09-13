<?php

declare(strict_types=1);

namespace App\Tests\Unit\Post;

use App\Entity\Post;
use App\Post\VideoProcessor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(VideoProcessor::class)]
class VideoProcessorTest extends TestCase
{
    #[DataProvider('processProvider')]
    public function testProcess(string $content, string $expected): void
    {
        $processor = new VideoProcessor();
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
        $videoOne = <<<HTML
            <p>
                <video class="video-js vjs-default-skin" controls preload="auto" width="544" height="304" poster="https://chaostangent.com/media/2025/09/test-544x304.jpg" data-setup="">
                    <source src="https://chaostangent.com/media/2025/09/test.mp4" type="video/mp4">
                    <source src="https://chaostangent.com/media/2025/09/test.webm" type="video/webm">
                </video>
            </p>
        HTML;

        $expectedOne = <<<TWIG
            {{ video([ { 'src': '2025/09/test.mp4', 'type': 'video/mp4' },
        { 'src': '2025/09/test.webm', 'type': 'video/webm' } ], '2025/09/test.jpg') }}
        TWIG;

        $videoNoSources = <<<HTML
            <p>
                <video class="video-js vjs-default-skin" controls preload="auto" width="544" height="304" poster="https://chaostangent.com/media/2025/09/test-544x304.jpg" data-setup=""></video>
            </p>
        HTML;

        $expectedNoSources = <<<TWIG
            {{ video([  ], '2025/09/test.jpg') }}
        TWIG;

        $videoNoPoster = <<<HTML
            <p>
                <video class="video-js vjs-default-skin" controls preload="auto" width="544" height="304" data-setup="">
                    <source src="https://chaostangent.com/media/2025/09/test.mp4" type="video/mp4">
                    <source src="https://chaostangent.com/media/2025/09/test.webm" type="video/webm">
                </video>
            </p>
        HTML;

        $expectedNoPoster = <<<TWIG
            {{ video([ { 'src': '2025/09/test.mp4', 'type': 'video/mp4' },
        { 'src': '2025/09/test.webm', 'type': 'video/webm' } ]) }}
        TWIG;

        $videoNoTypes = <<<HTML
            <p>
                <video class="video-js vjs-default-skin" controls preload="auto" width="544" height="304" data-setup="">
                    <source src="https://chaostangent.com/media/2025/09/test.mp4">
                    <source src="https://chaostangent.com/media/2025/09/test.webm">
                </video>
            </p>
        HTML;

        $expectedNoTypes = <<<TWIG
            {{ video([ { 'src': '2025/09/test.mp4', 'type': null },
        { 'src': '2025/09/test.webm', 'type': null } ]) }}
        TWIG;
        // phpcs:enable
        return [
            'nothing' => [ '', '' ],
            'just text' => [ 'Lorem ipsum dolor sit amet', 'Lorem ipsum dolor sit amet' ],
            'one' => [ $videoOne, $expectedOne ],
            'no sources' => [ $videoNoSources, $expectedNoSources ],
            'no poster' => [ $videoNoPoster , $expectedNoPoster ],
            'no types' => [ $videoNoTypes, $expectedNoTypes ],
        ];
    }
}
