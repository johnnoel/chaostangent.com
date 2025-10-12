<?php

declare(strict_types=1);

namespace App\Tests\Unit\Image;

use App\Image\Action;
use App\Image\Source;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Source::class)]
class SourceTest extends TestCase
{
    /**
     * @param array<Action> $actions
     * @param array{w: int, h: int} $expected
     */
    #[DataProvider('getTargetSizeProvider')]
    public function testGetTargetSize(array $actions, array $expected): void
    {
        $source = new Source('test', $actions);
        $this->assertSame($expected, $source->getTargetSize());
    }

    /**
     * @return array<string,mixed>
     */
    public static function getTargetSizeProvider(): array
    {
        $resizeOne = new Action('resize', '123x456');
        $resizeTwo = new Action('resize', '456x123');
        $resizeWidthOnly = new Action('resize', '123x');
        $resizeHeightOnly = new Action('resize', 'x456');
        $crop = new Action('crop', '1x2+3+4');
        $bigCrop = new Action('crop', '1280x720+0+0');

        return [
            'no actions' => [ [], [] ],
            'just crop' => [ [ $crop ], [ 'w' => 1, 'h' => 2 ] ],
            'just resize' => [ [ $resizeOne ], [ 'w' => 123, 'h' => 456 ] ],
            'two resizes' => [ [ $resizeOne, $resizeTwo ], [ 'w' => 456, 'h' => 123 ] ],
            'two resizes (reverse)' => [ [ $resizeTwo, $resizeOne ], [ 'w' => 123, 'h' => 456 ] ],
            'resize last' => [ [ $crop, $resizeOne ], [ 'w' => 123, 'h' => 456 ] ],
            'resize first' => [ [ $resizeOne, $crop ], [ 'w' => 1, 'h' => 2 ] ],
            'resize width only, no crop' => [ [ $resizeWidthOnly ], [ 'w' => 123 ] ],
            'resize height only, no crop' => [ [ $resizeHeightOnly ], [ 'h' => 456 ] ],
            'resize width only' => [ [ $bigCrop, $resizeWidthOnly ], [ 'w' => 123, 'h' => 69 ] ],
            'resize height only' => [ [ $bigCrop, $resizeHeightOnly ], [ 'w' => 811, 'h' => 456 ] ],
        ];
    }

    #[DataProvider('isSameAsProvider')]
    public function testIsSameAs(Source $compare, bool $expected): void
    {
        $source = new Source('Test source', [
            new Action('crop', '1x2+3+4'),
            new Action('resize', '123x456'),
        ], 'Test caption');

        $this->assertSame($expected, $source->isSameAs($compare));
        $this->assertSame($expected, $compare->isSameAs($source));
    }

    /**
     * @return array<string,array{0: Source, 1: bool}>
     */
    public static function isSameAsProvider(): array
    {
        $same = new Source('Test source', [
            new Action('crop', '1x2+3+4'),
            new Action('resize', '123x456'),
        ], 'Test caption');

        $reorderedActions = new Source('Test source', [
            new Action('resize', '123x456'),
            new Action('crop', '1x2+3+4'),
        ], 'Test caption');

        $noActions = new Source('Test source', [], 'Test caption');

        $modifiedAction = new Source('Test source', [
            new Action('crop', '1x2+3+5'),
            new Action('resize', '123x456'),
        ], 'Test caption');

        $differentSource = new Source('Test source 2', [
            new Action('crop', '1x2+3+4'),
            new Action('resize', '123x456'),
        ], 'Test caption');

        $differentCaption = new Source('Test source', [
            new Action('crop', '1x2+3+4'),
            new Action('resize', '123x456'),
        ], 'Test caption 2');

        $nullCaption = new Source('Test source', [
            new Action('crop', '1x2+3+4'),
            new Action('resize', '123x456'),
        ], null);

        return [
            'same' => [ $same, true ],
            'reordered actions' => [ $reorderedActions, false ],
            'no actions' => [ $noActions, false ],
            'modified action' => [ $modifiedAction, false ],
            'different source' => [ $differentSource, false ],
            'different caption' => [ $differentCaption, false ],
            'null caption' => [ $nullCaption, false ],
        ];
    }
}
