<?php

declare(strict_types=1);

namespace App\Tests\Unit\Post;

use App\Entity\Post;
use App\Post\CodeBlockProcessor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(CodeBlockProcessor::class)]
class CodeBlockProcessorTest extends TestCase
{
    #[DataProvider('processProvider')]
    public function testProcess(string $content, string $expected): void
    {
        $processor = new CodeBlockProcessor();
        $post = new Post('Test post', null, '', $content, null, false, []);

        $processor->process($post);

        $this->assertSame($expected, $post->getContent());
    }

    /**
     * @return array<string,mixed>
     */
    public static function processProvider(): array
    {
        $codeOne = <<<HTML
            <pre><code>public class TestCode { }</code></pre>
        HTML;

        $codeOneAlt = <<<HTML
            <p><code>public class TestCode { }</code></p>
        HTML;

        $expectedOne = <<<TWIG
            {% apply code('shellscript') %}
        public class TestCode { }
        {% endapply %}
        TWIG;

        $codeTwo = <<<HTML
            <pre class="brush: php">public class TestCode { }</pre>
        HTML;

        $expectedTwo = <<<TWIG
            {% apply code('php') %}
        public class TestCode { }
        {% endapply %}
        TWIG;

        $multiple = <<<HTML
            <p><code>public class One { }</code></p>
            <p>Test content</p>
            <p><code>public class Two { }</code></p>
        HTML;

        $expectedMultiple = <<<TWIG
            {% apply code('shellscript') %}
        public class One { }
        {% endapply %}
            <p>Test content</p>
            {% apply code('shellscript') %}
        public class Two { }
        {% endapply %}
        TWIG;

        return [
            'nothing' => [ '', '' ],
            'some html' => [ '<a href="">hello</a>', '<a href="">hello</a>' ],
            'code format one' => [ $codeOne, $expectedOne ],
            'code format two' => [ $codeTwo, $expectedTwo ],
            'code format three' => [ $codeOneAlt, $expectedOne ],
            'multiple' => [ $multiple, $expectedMultiple ],
            'mixed 1' => [
                '<div></div>' . $codeOne . '<a href="">hello</a>',
                '<div></div>' . $expectedOne . '<a href="">hello</a>',
            ],
            'mixed 2' => [
                '<div></div>' . $codeTwo . '<a href="">hello</a>',
                '<div></div>' . $expectedTwo . '<a href="">hello</a>',
            ],
            'mixed 3' => [ '<div></div>' . $codeOne . $codeTwo, '<div></div>' . $expectedOne . $expectedTwo ],
        ];
    }
}
