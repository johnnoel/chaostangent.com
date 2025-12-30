<?php

declare(strict_types=1);

namespace App\Pingback;

use App\Form\Model\WebmentionModel;
use App\Message\ProcessIncomingLinkback;
use Exception;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

readonly final class PingbackServer
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private RequestStack $requestStack,
        private ValidatorInterface $validator
    ) {
    }

    /**
     * @param string $source Needed for XML-RPC server introspection
     * @param string $target Needed for XML-RPC server introspection
     */
    public function pingback(string $source, string $target): string
    {
        $webmention = new WebmentionModel();
        $webmention->source = $source;
        $webmention->target = $target;

        $errors = $this->validator->validate($webmention);
        if (count($errors) > 0) {
            throw new Exception((string)$errors);
        }

        $request = $this->requestStack->getCurrentRequest();
        if ($request === null) {
            throw new Exception('No request');
        }

        $this->messageBus->dispatch(new ProcessIncomingLinkback($source, $target, strval($request->getClientIp())));

        return 'Done';
    }
}
