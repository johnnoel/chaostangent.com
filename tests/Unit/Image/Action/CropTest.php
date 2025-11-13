<?php

declare(strict_types=1);

namespace App\Tests\Unit\Image\Action;

use App\Image\Action\Crop;
use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Crop::class)]
class CropTest extends TestCase
{
    #[DataProvider('getCropParametersProvider')]
    public function testGetCropParameters(string $parameters, string $expected): void
    {
        $crop = Crop::createFromParameters($parameters);
        $this->assertSame($expected, (string)$crop);
    }

    /**
     * @return array<string,array<mixed>>
     */
    public static function getCropParametersProvider(): array
    {
        return [
            'basic' => [ '0x0+0+0', 'crop:0x0+0+0' ],
            'values' => [ '1x2+3+4', 'crop:1x2+3+4' ],
            'negatives' => [ '1x2-3-4', 'crop:1x2-3-4' ],
        ];
    }

    #[DataProvider('getCropParametersThrowsExceptionProvider')]
    public function testGetCropParametersThrowsException(string $parameters): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unable to parse crop parameters');

        Crop::createFromParameters($parameters);
    }

    /**
     * @return array<string,array<string>>
     */
    public static function getCropParametersThrowsExceptionProvider(): array
    {
        return [
            'nothing' => [ '' ],
            'no offset' => [ '1x2' ],
            'no area' => [ '+1+2' ],
            'junk' => [ 'asdfasdfadfa' ],
        ];
    }
}
