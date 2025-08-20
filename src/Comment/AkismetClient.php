<?php

declare(strict_types=1);

namespace App\Comment;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use RuntimeException;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

readonly final class AkismetClient
{
    public function __construct(
        private string $blog,
        private string $apiKey,
        private HttpClientInterface $http,
        private bool $testing = false
    ) {
    }

    public function verifyKey(): void
    {
        $response = $this->http->request('POST', 'https://rest.akismet.com/1.1/verify-key', [
            'body' => [
                'blog' => $this->blog,
                'key' => $this->apiKey,
            ],
        ]);

        try {
            if ($response->getContent() !== 'invalid') {
                return;
            }
        } catch (HttpExceptionInterface | TransportException $e) {
            throw new RuntimeException('Unable to contact Akismet and verify key', previous: $e);
        }

        $message = $response->getHeaders()['x-akismet-debug-help'] ?? [];
        $message = (count($message) === 0) ? '' : implode(', ', $message);

        throw new RuntimeException($message);
    }

    public function checkComment(
        string $clientIp,
        ?string $userAgent,
        ?string $referrer,
        string $url,
        string $authorName,
        string $authorEmail,
        ?string $authorUrl,
        string $comment,
        ?string $userRole = null
    ): AkismetResponse {
        $params = [
            'blog' => $this->blog,
            'user_ip' => $clientIp,
            'user_agent' => $userAgent,
            'referrer' => $referrer,
            'permalink' => $url,
            'comment_type' => 'comment',
            'comment_author' => $authorName,
            'comment_author_email' => $authorEmail,
            'comment_author_url' => $authorUrl,
            'comment_content' => $comment,
            'blog_lang' => 'en_GB',
            'blog_charset' => 'UTF-8',
        ];

        if ($this->testing) {
            $params['is_test'] = true;
            $params['user_role'] = $userRole;
        }

        $response = $this->http->request('POST', 'https://' . $this->apiKey . '.rest.akismet.com/1.1/comment-check', [
            'body' => $params,
        ]);

        try {
            if ($response->getContent() !== 'true') {
                return AkismetResponse::HAM;
            }
        } catch (HttpExceptionInterface | TransportException $e) {
            throw new RuntimeException('Unable to contact Akismet', previous: $e);
        }

        $discardHeader = $response->getHeaders()['x-akismet-pro-tip'] ?? [];
        $discard = (count($discardHeader) > 0 && $discardHeader[0] === 'discard');

        if ($discard) {
            return AkismetResponse::DISCARD;
        }

        return AkismetResponse::SPAM;
    }

    public function submitSpam(
        string $clientIp,
        ?string $userAgent,
        ?string $referrer,
        string $url,
        string $authorName,
        string $authorEmail,
        ?string $authorUrl,
        string $comment,
        DateTimeImmutable $date,
        DateTimeImmutable $postModified,
        ?string $userRole = null
    ): void {
        $params = [
            'blog' => $this->blog,
            'user_ip' => $clientIp,
            'user_agent' => $userAgent,
            'referrer' => $referrer,
            'permalink' => $url,
            'comment_type' => 'comment',
            'comment_author' => $authorName,
            'comment_author_email' => $authorEmail,
            'comment_author_url' => $authorUrl,
            'comment_content' => $comment,
            'comment_date_gmt' => $date->setTimezone(new DateTimeZone('GMT'))->format(DateTimeInterface::ATOM),
            'comment_post_modified_gmt' => $postModified
                ->setTimezone(new DateTimeZone('GMT'))
                ->format(DateTimeInterface::ATOM),
            'blog_lang' => 'en_GB',
            'blog_charset' => 'UTF-8',
        ];

        if ($this->testing) {
            $params['is_test'] = true;
            $params['user_role'] = $userRole;
        }

        try {
            $this->http->request('POST', 'https://' . $this->apiKey . '.rest.akismet.com/1.1/submit-spam', [
                'form_params' => $params,
            ]);
        } catch (HttpExceptionInterface | TransportException $e) {
            throw new RuntimeException('Unable to contact Akismet', previous: $e);
        }
    }
}
