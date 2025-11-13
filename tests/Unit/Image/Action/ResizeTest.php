<?php

declare(strict_types=1);

namespace App\Tests\Unit\Image\Action;

use App\Image\Action\Resize;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Resize::class)]
class ResizeTest extends TestCase
{
    #[DataProvider('getResizeParametersProvider')]
    public function testCreateFromParameters(string $parameters, string $expected): void
    {
        $resize = Resize::createFromParameters($parameters);
        $this->assertSame($expected, (string)$resize);
    }

    /**
     * @return array<string,array<string>>
     */
    public static function getResizeParametersProvider(): array
    {
        return [
            'basic' => [ '0x0', 'resize:0x0' ],
            'values' => [ '1x2', 'resize:1x2' ],
            'just width' => [ '1x', 'resize:1x0' ],
            'just height' => [ 'x2', 'resize:0x2' ],
        ];
    }

    #[DataProvider('getResizeParametersThrowsExceptionProvider')]
    public function testGetResizeParametersThrowsException(string $parameters): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to parse resize parameters: ' . $parameters);

        Resize::createFromParameters($parameters);
    }

    /**
     * @return array<string,array<string>>
     */
    public static function getResizeParametersThrowsExceptionProvider(): array
    {
        return [
            'nothing' => [ '' ],
            'no width or height' => [ 'x' ],
            'no area' => [ '+1+2' ],
            'junk' => [ 'asdfasdfadfa' ],
        ];
    }
}
