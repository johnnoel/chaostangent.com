<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Post;
use App\Form\Model\CommentModel;
use App\Form\Type\CommentType;
use App\Message\MarkCommentAsSpam;
use App\Message\MarkCommentAsUnapproved;
use App\Message\PostComment;
use App\Repository\CommentRepository;
use App\Repository\DTO\PostDTO;
use App\Repository\TagRepository;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Requirement\Requirement;

class CommentsController extends AbstractController
{
    use HandleTrait;

    public function __construct(
        private readonly CommentRepository $commentRepository,
        private readonly TagRepository $tagRepository,
        private readonly UriSigner $uriSigner,
        MessageBusInterface $messageBus
    ) {
        $this->messageBus = $messageBus;
    }

    #[Route('/{year}/{month}/{alias}/comment/', name: 'comment', requirements: [
        'year' => '\d{4}', 'month' => '(0[1-9]|1[0-2])',
    ], methods: [ 'POST' ])]
    public function comment(
        #[MapEntity(class: Post::class, expr: 'repository.getPost(alias, year, month)')]
        PostDTO $postDto,
        Request $request
    ): Response {
        $post = $postDto->post;
        $commentModel = new CommentModel(
            authorIp: strval($request->getClientIp()),
            postUrl: $this->generateUrl('post', $post->getRouteParams(), UrlGeneratorInterface::ABSOLUTE_URL),
            userAgent: $request->headers->get('user-agent'),
            referrer: $request->headers->get('referer'),
        );
        $form = $this->createForm(CommentType::class, $commentModel);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            // see PostsController::post for what to render here
            $comments = $this->commentRepository->getTree($post);
            $tags = $this->tagRepository->getTagsForPost($post);

            return $this->render('post.html.twig', [
                'post' => $post,
                'comments' => $comments,
                'tags' => $tags,
                'comment_form' => $form,
            ]);
        }

        /** @var Comment $comment */
        $comment = $this->handle(new PostComment($post, $commentModel));

        if ($comment->isSpam()) {
            return $this->render('spam.html.twig');
        }

        return $this->redirectToRoute(
            'post',
            array_merge($post->getRouteParams(), [ '_fragment' => 'respond' ]),
            Response::HTTP_SEE_OTHER
        );
    }

    #[Route('/comment/{id}/spam', name: 'comment:spam', requirements: [
        'id' => Requirement::ULID,
    ], methods: [ 'POST' ])]
    public function markAsSpam(Comment $comment, Request $request): Response
    {
        if (!$this->uriSigner->checkRequest($request)) {
            throw $this->createNotFoundException();
        }

        $this->handle(new MarkCommentAsSpam($comment));

        return $this->redirectToRoute('post', $comment->getPost()->getRouteParams(), Response::HTTP_SEE_OTHER);
    }

    #[Route('/comment/{id}/unapprove', name: 'comment:unapprove', requirements: [
        'id' => Requirement::ULID,
    ], methods: [ 'POST' ])]
    public function markAsUnapproved(Comment $comment, Request $request): Response
    {
        if (!$this->uriSigner->checkRequest($request)) {
            throw $this->createNotFoundException();
        }

        $this->handle(new MarkCommentAsUnapproved($comment));

        return $this->redirectToRoute('post', $comment->getPost()->getRouteParams(), Response::HTTP_SEE_OTHER);
    }
}
