<?php

declare(strict_types=1);

namespace App\Tests\Unit\Image;

use App\Image\Action;
use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Action::class)]
class ActionTest extends TestCase
{
    #[DataProvider('parseProvider')]
    public function testParse(string $action, string $expectedAction, string $expectedParameters): void
    {
        $action = Action::parse($action);
        $this->assertSame($expectedAction, $action->action);
        $this->assertSame($expectedParameters, $action->parameters);
    }

    /**
     * @return array<string,array<string>>
     */
    public static function parseProvider(): array
    {
        return [
            ':' => [ ':', '', '' ],
            'a:b' => [ 'a:b', 'a', 'b' ],
            'a:b:c:d' => [ 'a:b:c:d', 'a', 'b:c:d' ],
        ];
    }

    #[DataProvider('parseThrowsExceptionProvider')]
    public function testParseThrowsException(string $action): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid action format');

        Action::parse($action);
    }

    /**
     * @return array<string,array<string>>
     */
    public static function parseThrowsExceptionProvider(): array
    {
        return [
            'nothing' => [ '' ],
            'blank space' => [ '           ' ],
            'no colon' => [ 'abcdefghijk' ],
        ];
    }

    /**
     * @param array{w: int, h: int, x: int, y: int} $expected
     */
    #[DataProvider('getCropParametersProvider')]
    public function testGetCropParameters(string $parameters, array $expected): void
    {
        $action = new Action('test', $parameters);
        $this->assertSame($expected, $action->getCropParameters());
    }

    /**
     * @return array<string,array<mixed>>
     */
    public static function getCropParametersProvider(): array
    {
        return [
            'basic' => [ '0x0+0+0', [ 'w' => 0, 'h' => 0, 'x' => 0, 'y' => 0 ] ],
            'values' => [ '1x2+3+4', [ 'w' => 1, 'h' => 2, 'x' => 3, 'y' => 4 ] ],
            'negatives' => [ '1x2-3-4', [ 'w' => 1, 'h' => 2, 'x' => -3, 'y' => -4 ] ],
        ];
    }

    #[DataProvider('getCropParametersThrowsExceptionProvider')]
    public function testGetCropParametersThrowsException(string $parameters): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unable to parse crop parameters');

        $action = new Action('test', $parameters);
        $action->getCropParameters();
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

    #[DataProvider('getResizeParametersThrowsExceptionProvider')]
    public function testGetResizeParametersThrowsException(string $parameters): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unable to parse resize parameters');

        $action = new Action('test', $parameters);
        $action->getResizeParameters();
    }

    /**
     * @return array<string,array<string>>
     */
    public static function getResizeParametersThrowsExceptionProvider(): array
    {
        return [
            'nothing' => [ '' ],
            'no area' => [ '+1+2' ],
            'junk' => [ 'asdfasdfadfa' ],
        ];
    }
}
