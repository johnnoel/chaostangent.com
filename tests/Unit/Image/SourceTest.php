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
        $crop = new Action('crop', '1x2+3+4');

        return [
            'no actions' => [ [], [ 'w' => 0, 'h' => 0 ] ],
            'just crop' => [ [ $crop ], [ 'w' => 0, 'h' => 0 ] ],
            'just resize' => [ [ $resizeOne ], [ 'w' => 123, 'h' => 456 ] ],
            'two resizes' => [ [ $resizeOne, $resizeTwo ], [ 'w' => 456, 'h' => 123 ] ],
            'two resizes (reverse)' => [ [ $resizeTwo, $resizeOne ], [ 'w' => 123, 'h' => 456 ] ],
            'resize last' => [ [ $crop, $resizeOne ], [ 'w' => 123, 'h' => 456 ] ],
            'resize first' => [ [ $resizeOne, $crop ], [ 'w' => 123, 'h' => 456 ] ],
        ];
    }
}
