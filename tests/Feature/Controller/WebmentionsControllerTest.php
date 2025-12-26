<?php

declare(strict_types=1);

namespace App\Tests\Feature\Controller;

use App\Tests\WebTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class WebmentionsControllerTest extends WebTestCase
{
    /**
     * @param array<string,mixed> $postData
     */
    #[DataProvider('receiveProvider')]
    public function testReceive(array $postData, int $expectedResponseCode): void
    {
        $client = static::createClient();
        $client->request('POST', '/webmention', $postData);

        $this->assertResponseStatusCodeSame($expectedResponseCode);
    }

    /**
     * @return array<mixed>
     */
    public static function receiveProvider(): array
    {
        return [
            'nothing' => [ [], 400 ],
            'source no target' => [ [ 'source' => 'https://test.test/test' ], 400 ],
            'source empty target' => [ [ 'source' => 'https://test.test/test', 'target' => '' ], 400 ],
            'target no source' => [ [ 'target' => 'http://localhost/2025/12/test-post/' ], 400 ],
            'target empty source' => [ [ 'source' => '', 'target' => 'http://localhost/2025/12/test-post/' ], 400 ],
            'source junk' => [ [
                'source' => '!"£$%^&*()',
                'target' => 'http://test2.test/2025/12/test-post/',
            ], 400 ],
            'target junk' => [ [
                'source' => 'https://test.test/test',
                'target' => '!"£$%^&*()',
            ], 400 ],
            'source no protocol' => [ [
                'source' => '//test.test/test',
                'target' => 'http://test2.test/2025/12/test-post/',
            ], 400 ],
            'target no protocol' => [ [
                'source' => 'https://test.test/test',
                'target' => '//test2.test/2025/12/test-post/',
            ], 400 ],
            'source bad protocol' => [ [
                'source' => 'ftp://test.test/test',
                'target' => 'http://test2.test/2025/12/test-post/',
            ], 400 ],
            'target bad protocol' => [ [
                'source' => 'https://test.test/test',
                'target' => 'ssh+git://test2.test/2025/12/test-post/',
            ], 400 ],
            'source and target same' => [ [
                'source' => 'https://test.test/test',
                'target' => 'https://test.test/test',
            ], 400 ],
            'extra fields' => [ [
                'source' => 'https://test.test/test',
                'target' => 'http://test2.test/2025/12/test-post/',
                'extra' => 'could be a big file',
            ], 400 ],
            'target and source' => [ [
                'source' => 'https://test.test/test',
                'target' => 'http://test2.test/2025/12/test-post/',
            ], 202 ],
        ];
    }
}
