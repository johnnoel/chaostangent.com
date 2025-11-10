<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Post;
use App\Form\Model\CommentModel;
use App\Form\Type\CommentType;
use App\Post\Feed;
use App\Repository\CommentRepository;
use App\Repository\PostRepository;
use App\Repository\TagRepository;
use DateTimeImmutable;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

class PostsController extends AbstractController
{
    public function __construct(
        private readonly PostRepository $postRepository,
        private readonly CommentRepository $commentRepository,
        private readonly TagRepository $tagRepository,
        private readonly SerializerInterface $serializer
    ) {
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
        Post $post,
        Request $request
    ): Response {
        $requestFormat = $request->getRequestFormat();

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
        ]);
    }

    #[Route('/{year}/{month}/{alias}/raw', name: 'raw-post', requirements: [
        'year' => '\d{4}', 'month' => '(0[1-9]|1[0-2])',
    ], methods: [ 'GET' ], env: 'dev')]
    public function rawPost(
        #[MapEntity(expr: 'repository.getPost(alias, year, month)')]
        Post $post
    ): Response {
        return $this->render('raw-post.html.twig', [
            'post' => $post,
        ]);
    }
}
