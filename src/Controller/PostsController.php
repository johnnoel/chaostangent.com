<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Post;
use App\Form\Model\CommentModel;
use App\Form\Type\CommentType;
use App\Message\PostComment;
use App\Repository\CommentRepository;
use App\Repository\TagRepository;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

class PostsController extends AbstractController
{
    use HandleTrait;

    public function __construct(
        private readonly CommentRepository $commentRepository,
        private readonly TagRepository $tagRepository,
        MessageBusInterface $messageBus
    ) {
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
        #[MapEntity(expr: 'repository.getPost(alias, year, month)')]
        Post $post
    ): Response {
        $comments = $this->commentRepository->getTree($post);
        $commentForm = $this->createForm(CommentType::class);
        $tags = $this->tagRepository->getTagsForPost($post);

        return $this->render('post.html.twig', [
            'post' => $post,
            'comments' => $comments,
            'tags' => $tags,
            'comment_form' => $commentForm,
        ]);
    }

    #[Route('/{year}/{month}/{alias}/comment/', name: 'comment', requirements: [
        'year' => '\d{4}', 'month' => '(0[1-9]|1[0-2])',
    ], methods: [ 'POST' ])]
    public function comment(
        #[MapEntity(expr: 'repository.getPost(alias, year, month)')]
        Post $post,
        Request $request
    ): Response {
        $form = $this->createForm(CommentType::class);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            $comments = $this->commentRepository->getTree($post);
            $tags = $this->tagRepository->getTagsForPost($post);

            return $this->render('post.html.twig', [
                'post' => $post,
                'comments' => $comments,
                'tags' => $tags,
                'comment_form' => $form,
            ]);
        }

        /** @var CommentModel $commentModel */
        $commentModel = $form->getData();
        $this->handle(new PostComment($post, $commentModel));

        return $this->redirectToRoute('post', array_merge($post->getRouteParams(), [ '_fragment' => 'respond' ]));
    }
}
