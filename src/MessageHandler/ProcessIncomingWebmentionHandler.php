<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Webmention;
use App\Message\ProcessIncomingWebmention;
use App\Repository\PostRepository;
use App\Repository\WebmentionRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Routing\Exception\ExceptionInterface;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsMessageHandler]
readonly final class ProcessIncomingWebmentionHandler
{
    private const array ALLOWED_ROUTES = [ 'post' ];

    /**
     * @param array<string> $allowedHosts
     */
    public function __construct(
        private WebmentionRepository $webmentionRepository,
        private PostRepository $postRepository,
        private UrlMatcherInterface $urlMatcher,
        private HttpClientInterface $http,
        private LoggerInterface $logger,
        private array $allowedHosts,
    ) {
    }

    public function __invoke(ProcessIncomingWebmention $message): void
    {
        $target = $message->target;
        $source = $message->source;
        $path = parse_url($target, PHP_URL_PATH);

        if (!is_string($path)) {
            $this->logger->debug('No path in URL ' . $target);

            return;
        }

        $host = parse_url($target, PHP_URL_HOST);
        if (!in_array($host, $this->allowedHosts, strict: true)) {
            $this->logger->debug('Invalid host ' . $target);

            return;
        }

        try {
            $originalContext = $this->urlMatcher->getContext();
            $newContext = clone $originalContext;
            $newContext->setMethod('GET');

            $this->urlMatcher->setContext($newContext);
            $matched = $this->urlMatcher->match($path);
            $this->urlMatcher->setContext($originalContext);
        } catch (ExceptionInterface $e) {
            $this->logger->debug('Unable to match target to a route ' . $target . ' - ' . $e->getMessage());

            return;
        }

        if (!array_key_exists('_route', $matched) || !in_array($matched['_route'], self::ALLOWED_ROUTES)) {
            $this->logger->debug('No route parameter in matched route');

            return;
        }

        /** @var array{_controller: string, _route: string, year: string, month: string, alias: string} $matched */

        // devtodo rate limit based on host
        $resp = $this->http->request('GET', $source);
        if ($resp->getStatusCode() !== 200) {
            $this->logger->debug('Status code was not 200 - ' . $resp->getStatusCode());

            return;
        }

        if (!str_contains($resp->getContent(), $target)) {
            $this->logger->debug($source . ' does not contain target ' . $target);

            return;
        }

        // lookup the Post
        $post = $this->postRepository->getPost(
            $matched['alias'],
            intval($matched['year']),
            intval($matched['month']),
            published: true
        );

        if ($post === null) {
            $this->logger->debug(
                sprintf(
                    'Unable to find post with parameters alias: %s, year: %s, month: %s',
                    $matched['alias'],
                    $matched['year'],
                    $matched['month']
                )
            );

            return;
        }

        $webmention = new Webmention($post->post, $source, $message->ipAddress);
        $this->webmentionRepository->create($webmention);
    }
}
