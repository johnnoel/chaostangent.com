<?php

declare(strict_types=1);

namespace App\Tests\Feature\MessageHandler;

use App\Factory\CategoryFactory;
use App\Factory\TagFactory;
use App\Message\ImportPost;
use App\MessageHandler\ImportPostHandler;
use App\Tests\WebTestCase;
use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Yaml\Yaml;

class ImportPostHandlerTest extends WebTestCase
{
    public function testInvokeNoFrontmatter(): void
    {
        static::bootKernel();

        /** @var ImportPostHandler $handler */
        $handler = $this->getContainer()->get(ImportPostHandler::class);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No frontmatter found');

        $handler(new ImportPost(''));
    }

    /**
     * @param array<mixed> $frontmatter
     */
    #[DataProvider('invokeInvalidPostProvider')]
    public function testInvokeInvalidPost(array $frontmatter, string $expectedMessage): void
    {
        static::bootKernel();

        CategoryFactory::createOne([ 'title' => 'One' ]);
        TagFactory::createOne([ 'tag' => 'Three' ]);

        /** @var ImportPostHandler $handler */
        $handler = $this->getContainer()->get(ImportPostHandler::class);

        $yaml = Yaml::dump($frontmatter);
        $content = <<<TWIG
            {#---
            $yaml
            ---#}
            Test content
            TWIG;


        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Could not import post: ERROR: ' . $expectedMessage);

        $handler(new ImportPost($content));
    }

    /**
     * @return array<string,array<mixed>>
     */
    public static function invokeInvalidPostProvider(): array
    {
        $defaultData = [
            'title' => 'Test title',
            'subtitle' => 'Test subtitle',
            'alias' => 'test-title',
            'summary' => 'Test summary',
            'date' => '2025-10-04T14:00:53+00:00',
            'created' => '2025-10-04T14:00:53+01:00',
            'updated' => '2025-10-04T14:00:53+01:00',
            'published' => false,
            'image' => [],
            'extra' => [],
            'categories' => [ 'One', 'Two' ],
            'tags' => [ 'Three', 'Four' ],
        ];

        return [
            'no title' => [ array_diff_key($defaultData, [ 'title' => '' ]), 'Please enter a title' ],
            'no alias' => [ array_diff_key($defaultData, [ 'alias' => '' ]), 'Please enter an alias' ],
            'invalid date' => [
                array_merge($defaultData, [ 'date' => 'test!"£$%' ]),
                'Please enter a valid date/time for date',
            ],
            'invalid created' => [
                array_merge($defaultData, [ 'created' => 'test!"£$%' ]),
                'Please enter a valid date/time for created',
            ],
            'future created' => [
                array_merge($defaultData, [ 'created' => '2099-01-01T00:00:00+00:00' ]),
                'Please enter a created date in the past',
            ],
            'invalid updated' => [
                array_merge($defaultData, [ 'updated' => 'test!"£$%' ]),
                'Please enter a valid date/time for updated',
            ],
            'future updated' => [
                array_merge($defaultData, [ 'updated' => '2099-01-01T00:00:00+00:00' ]),
                'Please enter an updated date in the past',
            ],
        ];
    }
}
