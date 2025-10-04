<?php

declare(strict_types=1);

namespace App\Tests\Unit\Form\DataTransformer;

use App\Form\DataTransformer\HtmlPurifierTransformer;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(HtmlPurifierTransformer::class)]
class HtmlPurifierTransformerTest extends TestCase
{
    #[DataProvider('notStringProvider')]
    public function testTransformNotString(mixed $value): void
    {
        $transformer = new HtmlPurifierTransformer();
        $this->assertNull($transformer->transform($value)); /** @phpstan-ignore argument.type */
    }

    /**
     * @return array<string,array<mixed>>
     */
    public static function notStringProvider(): array
    {
        return [
            'null' => [ null ],
            'integer' => [ 1234 ],
            'array' => [ [ 'test' ] ],
            'collection' => [ new Collection([ 'test' ]) ],
        ];
    }

    #[DataProvider('transformProvider')]
    public function testTransform(string $value): void
    {
        $transformer = new HtmlPurifierTransformer();
        $this->assertSame($value, $transformer->transform($value));
    }

    /**
     * @return array<string,array<mixed>>
     */
    public static function transformProvider(): array
    {
        return [
            'nothing' => [ '' ],
            'test' => [ 'test 123' ],
            'html' => [ '<img src="" alt="">' ],
        ];
    }

    #[DataProvider('notStringProvider')]
    public function testReverseTransformNotString(mixed $value): void
    {
        $transformer = new HtmlPurifierTransformer();
        $this->assertNull($transformer->reverseTransform($value)); /** @phpstan-ignore argument.type */
    }

    #[DataProvider('reverseTransformProvider')]
    public function testReverseTransform(string $value, string $expected): void
    {
        $transformer = new HtmlPurifierTransformer();
        $this->assertSame($expected, $transformer->reverseTransform($value));
    }

    /**
     * @return array<string,array<mixed>>
     */
    public static function reverseTransformProvider(): array
    {
        $allowedHtml = <<<HTML
            <p><b>Hello</b><i>Test</i><strong>One</strong><em>Two</em></p>
            <a href="https://chaostangent.com" rel="nofollow">Test</a><blockquote>One<br>Two</blockquote>
        HTML;

        return [
            'nothing' => [ '', '' ],
            'not allowed html' => [ '<img src="https://test.test" alt="Test">', '' ],
            'allowed html' => [ $allowedHtml, $allowedHtml ],
            'no rel' => [
                '<a href="https://test.test">Test</a>',
                '<a href="https://test.test" rel="nofollow">Test</a>',
            ],
            'multi rel' => [
                '<a href="https://test.test" rel="noopener nofollow test">Test</a>',
                '<a href="https://test.test" rel="nofollow">Test</a>',
            ],
            'onclick' => [
                '<a href="https://test.test" onclick="alert(\'hey\')">Test</a>',
                '<a href="https://test.test" rel="nofollow">Test</a>',
            ],
            'attributes' => [
                '<p class="123><b id="456">Test</b></p>',
                '<p>Test</p>',
            ],
        ];
    }
}
