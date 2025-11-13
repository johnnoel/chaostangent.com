<?php

declare(strict_types=1);

namespace App\Tests\Unit\Image;

use App\Image\Action\Crop;
use App\Image\Action\Resize;
use App\Image\FileHandler;
use App\Image\MimeType;
use App\Image\Source;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(FileHandler::class)]
class FileHandlerTest extends TestCase
{
    #[DataProvider('getSourcePathProvider')]
    public function testGetSourcePath(string $mediaDirectory, string $src, string $expected): void
    {
        $fileHandler = new FileHandler($mediaDirectory, '');
        $source = new Source($src, []);

        $this->assertSame($expected, $fileHandler->getSourcePath($source));
    }

    /**
     * @return array<string,array<string>>
     */
    public static function getSourcePathProvider(): array
    {
        return [
            'empty media directory' => [ '', 'test123', '/test123' ],
            'empty src' => [ 'test123', '', 'test123/' ],
            'both' => [ 'test123', 'test345', 'test123/test345' ],
            'subdirectories' => [ '1/2/3', '4/5/6', '1/2/3/4/5/6' ],
            'subdirectories trailing /' => [ '1/2/3/', '4/5/6/', '1/2/3/4/5/6/' ],
            'subdirectories leading /' => [ '/1/2/3', '/4/5/6', '/1/2/3//4/5/6' ],
        ];
    }

    #[DataProvider('getSourceUrlProvider')]
    public function testGetSourceUrl(string $mediaUrl, string $src, string $expected): void
    {
        $fileHandler = new FileHandler('', $mediaUrl);
        $source = new Source($src, []);

        $this->assertSame($expected, $fileHandler->getSourceUrl($source));
    }

    /**
     * @return array<string,array<string>>
     */
    public static function getSourceUrlProvider(): array
    {
        return [
            'empty media url' => [ '', 'test123', '/test123' ],
            'empty src' => [ 'test123', '', 'test123/' ],
            'both' => [ 'test123', 'test345', 'test123/test345' ],
            'subdirectories' => [ '1/2/3', '4/5/6', '1/2/3/4/5/6' ],
            'subdirectories trailing /' => [ '1/2/3/', '4/5/6/', '1/2/3/4/5/6/' ],
            'subdirectories leading /' => [ '/1/2/3', '/4/5/6', '/1/2/3//4/5/6' ],
        ];
    }

    #[DataProvider('getVariantPathProvider')]
    public function testGetVariantPath(string $mediaDirectory, Source $source, string $expected): void
    {
        $mimeType = MimeType::JPEG;
        $fileHandler = new FileHandler($mediaDirectory, '');

        $this->assertSame($expected, $fileHandler->getVariantPath($source, $mimeType));
    }

    /**
     * @return array<string,mixed>
     */
    public static function getVariantPathProvider(): array
    {
        return [
            'empty' => [ '', new Source('', []), '/-0x0.jpg' ],
            'one dir' => [ 'test123/', new Source('', []), 'test123/-0x0.jpg' ],
            'many dir' => [ 'test/1/2/3/', new Source('', []), 'test/1/2/3/-0x0.jpg' ],
            'mix' => [
                'test/1/2/3/',
                new Source('test456.webp', [ new Resize(123, 456) ]),
                'test/1/2/3/test456-123x456.jpg',
            ],
            'width only' => [
                'test/1/2/3/',
                new Source('test456.webp', [ new Resize(123, null) ]),
                'test/1/2/3/test456-123x0.jpg',
            ],
            'height only' => [
                'test/1/2/3/',
                new Source('test456.webp', [ new Resize(null, 456) ]),
                'test/1/2/3/test456-0x456.jpg',
            ],
        ];
    }

    #[DataProvider('getVariantFilenameProvider')]
    public function testGetVariantFilename(Source $source, string $expected): void
    {
        $mimeType = MimeType::JPEG;
        $fileHandler = new FileHandler('', '');

        $this->assertSame($expected, $fileHandler->getVariantFilename($source, $mimeType));
    }

    /**
     * @return array<string,mixed>
     */
    public static function getVariantFilenameProvider(): array
    {
        $empty = new Source('', []);
        $noActions = new Source('test123.jxl', []);
        $cropAction = new Source('test123.png', [ new Crop(0, 0, 0, 0) ]);
        $resizeAction = new Source('test123.avif', [ new Resize(123, 456) ]);

        return [
            'empty' => [ $empty, '-0x0.jpg' ],
            'no actions' => [ $noActions, 'test123-0x0.jpg' ],
            'crop' => [ $cropAction, 'test123-0x0.jpg' ],
            'resize' => [ $resizeAction, 'test123-123x456.jpg' ],
        ];
    }

    #[DataProvider('isStaleProvider')]
    public function testIsStale(
        DateTimeImmutable $sourceModified,
        DateTimeImmutable $variantModified,
        bool $expected
    ): void {
        $sourceFile = __DIR__ . '/../../data/file1';
        touch($sourceFile, $sourceModified->getTimestamp());
        $variantFile = __DIR__ . '/../../data/file1-100x100.jpg';
        touch($variantFile, $variantModified->getTimestamp());

        $source = new Source('file1', [ new Resize(100, 100) ]);
        $mimeType = MimeType::JPEG;

        $fileHandler = new FileHandler(__DIR__ . '/../../data/', '');
        $this->assertSame($expected, $fileHandler->isStale($source, $mimeType));

        unlink($sourceFile);
        unlink($variantFile);
    }

    /**
     * @return array<string,mixed>
     */
    public static function isStaleProvider(): array
    {
        $now = new DateTimeImmutable('now');

        return [
            'is very stale' => [ $now->setTime(12, 0), $now->setTime(1, 0), true ],
            'is very fresh' => [ $now->setTime(12, 0), $now->setTime(23, 0), false ],
            'is fresh' => [ $now->setTime(12, 0), $now->setTime(12, 0), false ],
            'is stale' => [ $now->setTime(12, 0, 1), $now->setTime(12, 0), true ],
        ];
    }

    public function testIsStaleMissingSource(): void
    {
        $variantFile = __DIR__ . '/../../data/file1-100x100.jpg';
        touch($variantFile, (new DateTimeImmutable('now'))->getTimestamp());

        $source = new Source('file1', [ new Resize(100, 100) ]);
        $mimeType = MimeType::JPEG;

        $fileHandler = new FileHandler(__DIR__ . '/../../data/', '');
        $this->assertTrue($fileHandler->isStale($source, $mimeType));

        unlink($variantFile);
    }

    public function testIsStaleMissingVariant(): void
    {
        $sourceFile = __DIR__ . '/../../data/file1';
        touch($sourceFile, (new DateTimeImmutable('now'))->getTimestamp());

        $source = new Source('file1', [ new Resize(100, 100) ]);
        $mimeType = MimeType::JPEG;

        $fileHandler = new FileHandler(__DIR__ . '/../../data/', '');
        $this->assertTrue($fileHandler->isStale($source, $mimeType));

        unlink($sourceFile);
    }
}
