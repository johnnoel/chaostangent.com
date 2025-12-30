<?php

declare(strict_types=1);

namespace App\Controller;

use App\Form\Model\WebmentionModel;
use App\Form\Type\WebmentionType;
use App\Message\ProcessIncomingLinkback;
use Laminas\XmlRpc\Request as XmlRpcRequest;
use Laminas\XmlRpc\Server;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

final class LinkbacksController extends AbstractController
{
    public function __construct(private readonly MessageBusInterface $messageBus)
    {
    }

    #[Route('/webmention', name: 'webmention', defaults: [ '_format' => 'html' ], methods: [ 'POST' ])]
    public function webmention(Request $request): Response
    {
        $form = $this->createForm(WebmentionType::class);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return new Response(status: Response::HTTP_BAD_REQUEST);
        }

        /** @var WebmentionModel $data */
        $data = $form->getData();

        $this->messageBus->dispatch(
            new ProcessIncomingLinkback($data->source, $data->target, strval($request->getClientIp()))
        );

        return new Response(status: Response::HTTP_ACCEPTED);
    }

    #[Route('/pingback', name: 'pingback', defaults: [ '_format' => 'xml' ], methods: [ 'POST' ])]
    public function pingback(Server $xmlRpcServer, Request $request): Response
    {
        $xmlRpcRequest = new XmlRpcRequest();

        try {
            $xmlRpcRequest->loadXml($request->getContent());
        } catch (Throwable) {
        }

        return new Response((string)$xmlRpcServer->handle($xmlRpcRequest));
    }
}
