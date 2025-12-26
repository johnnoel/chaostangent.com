<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Post;
use App\Form\Model\CommentModel;
use App\Form\Type\CommentType;
use App\Message\GenerateImages;
use App\Message\ImportPost;
use App\Post\Feed;
use App\Repository\CommentRepository;
use App\Repository\DTO\PostDTO;
use App\Repository\PostRepository;
use App\Repository\TagRepository;
use App\Security\ApiKeyChecker;
use DateTimeImmutable;
use Exception;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\SerializerInterface;

class PostsController extends AbstractController
{
    use CalculatesETags;
    use HandleTrait;

    public function __construct(
        private readonly PostRepository $postRepository,
        private readonly CommentRepository $commentRepository,
        private readonly TagRepository $tagRepository,
        private readonly SerializerInterface $serializer,
        string $assetManifestPath,
        MessageBusInterface $messageBus,
    ) {
        $this->assetManifestPath = $assetManifestPath;
        $this->messageBus = $messageBus;
    }

    #[Route('/{year}/{month}/{alias}/', name: 'post', requirements: [
        'year' => '\d{4}', 'month' => '(0[1-9]|1[0-2])',
    ], methods: [ 'GET' ])]
    #[Route('/{year}/{month}/{alias}/feed/', name: 'post:comments:rss', requirements: [
        'year' => '\d{4}', 'month' => '(0[1-9]|1[0-2])',
    ], defaults: [ '_format' => 'rss' ], methods: [ 'GET' ])]
    #[Route('/{year}/{month}/{alias}/feed/atom/', name: 'post:comments:atom', requirements: [
        'year' => '\d{4}', 'month' => '(0[1-9]|1[0-2])',
    ], defaults: [ '_format' => 'atom' ], methods: [ 'GET' ])]
    public function post(
        #[MapEntity(class: Post::class, expr: 'repository.getPost(alias, year, month)')]
        PostDTO $postDto,
        Request $request
    ): Response {
        $response = (new Response(headers: [
            'Link' => sprintf(
                '<%s>; rel="webmention"',
                $this->generateUrl('webmention', referenceType: UrlGeneratorInterface::ABSOLUTE_URL),
            ),
        ]))->setCache([
            'max_age' => 60 * 60,
            'public' => true,
            'must_revalidate' => true,
            'last_modified' => $postDto->lastModified,
            'etag' => $this->calculateETag($postDto->lastModified),
        ]);

        if ($response->isNotModified($request)) {
            return $response;
        }

        $requestFormat = $request->getRequestFormat();
        $post = $postDto->post;

        if (in_array($requestFormat, [ 'rss', 'atom' ], strict: true)) {
            return new Response($this->serializer->serialize(new Feed($post), $requestFormat));
        }

        $comments = $this->commentRepository->getTree($post);
        $commentModel = new CommentModel(formRendered: new DateTimeImmutable('now'));
        $commentForm = $this->createForm(CommentType::class, $commentModel);
        $tags = $this->tagRepository->getTagsForPost($post);
        [ 'prev' => $prevPost, 'next' => $nextPost ] = $this->postRepository->getSurroundingPosts($post);

        return $this->render('post.html.twig', [
            'post' => $post,
            'comments' => $comments,
            'tags' => $tags,
            'comment_form' => $commentForm,
            'prev_post' => $prevPost,
            'next_post' => $nextPost,
        ], $response);
    }

    #[Route('/import', name: 'post:import', defaults: [ '_format' => 'json' ], methods: [ 'POST' ])]
    public function import(ApiKeyChecker $apiKeyChecker, Request $request): Response
    {
        // devtodo Move to securitybundle
        $apiKey = strval($request->headers->get('X-Api-Key'));
        if (!$apiKeyChecker->isValid($apiKey)) {
            throw $this->createAccessDeniedException(); // devtodo not sure 403 is correct here
        }

        try {
            /** @var Post $post */
            $post = $this->handle(new ImportPost($request->getContent()));
            $this->handle(new GenerateImages($post));
        } catch (Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        return new Response(status: Response::HTTP_OK);
    }
}
