<?php

declare(strict_types=1);

namespace App\Tests\Feature\Controller;

use App\Tests\WebTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class LinkbacksControllerTest extends WebTestCase
{
    /**
     * @param array<string,mixed> $postData
     */
    #[DataProvider('webmentionProvider')]
    public function testWebmention(array $postData, int $expectedResponseCode): void
    {
        $client = static::createClient();
        $client->request('POST', '/webmention', $postData);

        $this->assertResponseStatusCodeSame($expectedResponseCode);
    }

    /**
     * @return array<mixed>
     */
    public static function webmentionProvider(): array
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

    #[DataProvider('pingbackProvider')]
    public function testPingback(string $request, ?int $expectedErrorCode): void
    {
        $client = static::createClient();
        $client->request('POST', '/pingback', content: $request);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseFormatSame('xml');

        if ($expectedErrorCode !== null) {
            $this->assertStringContainsString(
                '<name>faultCode</name><value><int>' . $expectedErrorCode . '</int></value>',
                strval($client->getResponse()->getContent())
            );
        } else {
            $this->assertStringContainsString(
                '<value><string>Done</string></value>',
                strval($client->getResponse()->getContent())
            );
        }
    }

    /**
     * @return array<mixed>
     */
    public static function pingbackProvider(): array
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $default = trim(<<<XML
            $xml<methodCall><methodName>ping.pingback</methodName>
            <params>
            <param><value><string>{source}</string></value></param>
            <param><value><string>{target}</string></value></param>
            </params>
            </methodCall>
        XML);

        return [
            'nothing' => [ '', 620 ],
            'junk' => [ '!"£$%^&*()', 631 ],
            'no params' => [ <<<XML
            $xml<methodCall><methodName>ping.pingback</methodName><params></params></methodCall>
            XML, 623 ],
            'source no target' => [ <<<XML
            $xml<methodCall><methodName>ping.pingback</methodName>
            <params><param><value><string>http://test.test/source</string></value></param></params>
            </methodCall>
            XML, 623 ],
            'source empty target' => [
                str_replace([ '{source}', '{target}' ], [ 'http://test.test/source', '' ], $default),
                404,
            ],
            'target empty source' => [
                str_replace([ '{source}', '{target}' ], [ '', 'http://test.test/target' ], $default),
                404,
            ],
            'source junk' => [
                str_replace([ '{source}', '{target}' ], [ '!"£$%^*()', 'http://test.test/target' ], $default),
                404,
            ],
            'target junk' => [
                str_replace([ '{source}', '{target}' ], [ 'http://test.test/source', '!"£$%^*()' ], $default),
                404,
            ],
            'source no protocol' => [
                str_replace([ '{source}', '{target}' ], [ '//test.test/test', 'http://test.test/target' ], $default),
                404,
            ],
            'target no protocol' => [
                str_replace([ '{source}', '{target}' ], [ 'https://test.test/test', '//test.test/target/' ], $default),
                404,
            ],
            'source bad protocol' => [
                str_replace(
                    [ '{source}', '{target}' ],
                    [ 'ftp://test.test/test', 'http://test.test/target/' ],
                    $default
                ),
                404,
            ],
            'target bad protocol' => [
                str_replace(
                    [ '{source}', '{target}' ],
                    [ 'https://test.test/test', 'ssh+git://test.test/target' ],
                    $default
                ),
                404,
            ],
            'source and target same' => [
                str_replace(
                    [ '{source}', '{target}' ],
                    [ 'https://test.test/test', 'https://test.test/test' ],
                    $default
                ),
                404,
            ],
            'extra params' => [ <<<XML
            $xml<methodCall><methodName>ping.pingback</methodName>
            <params>
            <param><value><string>http://test.test/source</string></value></param>
            <param><value><string>http://test.test/target</string></value></param>
            <param><value><string>extra</string></value></param>
            </params>
            </methodCall>
            XML, 623 ],
            'target and source' => [
                str_replace(
                    [ '{source}', '{target}' ],
                    [ 'http://test.test/test', 'http://test2.test/2025/12/test-post/' ],
                    $default
                ),
                null,
            ],
        ];
    }
}
