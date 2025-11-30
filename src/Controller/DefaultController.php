<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Post;
use App\Entity\Tag;
use App\Post\Feed;
use App\Repository\CategoryRepository;
use App\Repository\CommentRepository;
use App\Repository\Criteria\FilterPostsCriteria;
use App\Repository\DTO\PostDTO;
use App\Repository\DTO\TagDTO;
use App\Repository\PostRepository;
use App\Repository\TagRepository;
use Presta\SitemapBundle\Sitemap\Url\UrlConcrete as Sitemap;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Uid\Ulid;

class DefaultController extends AbstractController
{
    public const PER_PAGE = 5;

    public function __construct(
        private readonly PostRepository $postRepository,
        private readonly SerializerInterface $serializer,
    ) {
    }

    #[Route('/page/{page}/', name: 'home:paginated', requirements: [ 'page' => '\d+' ], methods: [ 'GET' ])]
    #[Route('/feed/', name: 'home:rss', defaults: [ '_format' => 'rss' ], methods: [ 'GET' ])]
    #[Route('/feed/atom/', name: 'home:atom', defaults: [ '_format' => 'atom' ], methods: [ 'GET' ])]
    #[Route('/', name: 'home', options: [
        'sitemap' => [ 'priority' => 1.0, 'changefreq' => Sitemap::CHANGEFREQ_WEEKLY ],
    ], methods: [ 'GET' ])]
    public function index(Request $request, int $page = 1): Response
    {
        $criteria = new FilterPostsCriteria(page: $page, perPage: self::PER_PAGE);
        $templateParams = [];

        if ($page === 1) {
            $recentPostIds = $this->postRepository->filterPosts(new FilterPostsCriteria(page: 1, perPage: 30))
                ->map(fn (PostDTO $dto): Ulid => $dto->post->getId())
            ;
            $templateParams = [ 'image_strip' => $this->postRepository->findTopPosts(postIds: $recentPostIds) ];
        }

        return $this->posts($criteria, 'index.html.twig', $request, $templateParams);
    }

    #[Route('/{year}/page/{page}/', name: 'year:paginated', requirements: [
        'year' => '\d{4}', 'page' => '\d+',
    ], methods: [ 'GET' ])]
    #[Route('/{year}/', name: 'year', requirements: [ 'year' => '\d{4}' ], defaults: [
        'page' => 1,
    ], methods: [ 'GET' ])]
    public function year(int $year, Request $request, int $page = 1): Response
    {
        $criteria = new FilterPostsCriteria(year: $year, page: $page, perPage: self::PER_PAGE);

        return $this->posts($criteria, 'year.html.twig', $request, [ 'year' => $year ]);
    }

    #[Route('/{year}/{month}/page/{page}/', name: 'month:paginated', requirements: [
        'year' => '\d{4}', 'month' => '(0[1-9]|1[0-2])', 'page' => '\d+',
    ], methods: [ 'GET' ])]
    #[Route('/{year}/{month}/', name: 'month', requirements: [
        'year' => '\d{4}', 'month' => '(0[1-9]|1[0-2])',
    ], defaults: [ 'page' => 1 ], methods: [ 'GET' ])]
    public function month(int $year, int $month, Request $request, int $page = 1): Response
    {
        $criteria = new FilterPostsCriteria(month: $month, year: $year, page: $page, perPage: self::PER_PAGE);

        return $this->posts($criteria, 'year-month.html.twig', $request, [
            'year' => $year,
            'month' => $month,
        ]);
    }

    #[Route('/category/{alias:category}/page/{page}/', name: 'category:paginated', requirements: [
        'page' => '\d+', 'alias' => '.+',
    ], methods: [ 'GET' ])]
    #[Route('/category/{alias:category}/feed/', name: 'category:rss', requirements: [ 'alias' => '.+' ], defaults: [
        'page' => 1, '_format' => 'rss',
    ], methods: [ 'GET' ])]
    #[Route('/category/{alias:category}/feed/atom/', name: 'category:atom', requirements: [
        'alias' => '.+',
    ], defaults: [ 'page' => 1, '_format' => 'atom' ], methods: [ 'GET' ])]
    #[Route('/category/{alias:category}/', name: 'category', requirements: [ 'alias' => '.+' ], methods: [ 'GET' ])]
    public function category(Category $category, Request $request, int $page = 1): Response
    {
        $criteria = new FilterPostsCriteria(category: $category, page: $page, perPage: self::PER_PAGE);

        return $this->posts($criteria, 'category.html.twig', $request, [
            'category' => $category,
        ]);
    }

    #[Route('/tag/{alias:tag}/', name: 'tag', defaults: [ 'page' => 1 ], methods: [ 'GET' ])]
    #[Route('/tag/{alias:tag}/page/{page}/', name: 'tag:paginated', requirements: [
        'page' => '\d+',
    ], methods: [ 'GET' ])]
    #[Route('/tag/{alias:tag}/feed/', name: 'tag:rss', defaults: [
        'page' => 1, '_format' => 'rss',
    ], methods: [ 'GET' ])]
    #[Route('/tag/{alias:tag}/feed/atom/', name: 'tag:atom', defaults: [
        'page' => 1, '_format' => 'atom',
    ], methods: [ 'GET' ])]
    public function tag(Tag $tag, Request $request, int $page = 1): Response
    {
        $criteria = new FilterPostsCriteria(tag: $tag, page: $page, perPage: self::PER_PAGE);

        return $this->posts($criteria, 'tag.html.twig', $request, [
            'tag' => $tag,
        ]);
    }

    #[Route('/about/', name: 'about', options: [
        'sitemap' => [ 'priority' => 0.1, 'changefreq' => Sitemap::CHANGEFREQ_WEEKLY ],
    ], methods: [ 'GET' ])]
    public function about(CommentRepository $commentRepository): Response
    {
        $postCount = $this->postRepository->countFilteredPosts(new FilterPostsCriteria());
        $popularPosts = $this->postRepository->findTopPosts(8);
        $postCalendar = $this->postRepository->getPostCountByWeek();
        $postCalendarMax = (count($postCalendar) === 0) ?
            0 : max(array_map(fn (array $counts): int => max($counts) ?: 0, $postCalendar));

        $wordCount = 0;
        foreach ($this->postRepository->getSitemapPosts() as $p) {
            $wordCount += str_word_count(trim(strip_tags($p->post->getContent())));
        }

        $commentCount = $commentRepository->countComments();
        $participantCount = $commentRepository->countParticipants();
        $mostParticipated = $commentRepository->findMostParticipated(10);
        $longestComments = $commentRepository->findLongestComments(10);
        $commentCalendar = $commentRepository->getCommentCountByWeek();
        $commentCalendarMax = (count($commentCalendar) === 0) ?
            0 : max(array_map(fn (array $counts): int => max($counts) ?: 0, $commentCalendar));

        return $this->render('about.html.twig', [
            'post_count' => $postCount,
            'word_count' => $wordCount,
            'popular_posts' => $popularPosts,
            'post_calendar' => $postCalendar,
            'post_calendar_max' => $postCalendarMax,
            'comment_count' => $commentCount,
            'participant_count' => $participantCount,
            'most_participated' => $mostParticipated,
            'longest_comments' => $longestComments,
            'comment_calendar' => $commentCalendar,
            'comment_calendar_max' => $commentCalendarMax,
        ]);
    }

    public function filter(CategoryRepository $categoryRepository, TagRepository $tagRepository): Response
    {
        $calendar = $this->postRepository->getPostCalendar();
        $categories = $categoryRepository->getTree();
        $tags = $tagRepository->getTagsWithMostPosts();
        $maxPostCount = $tags->map(fn (TagDTO $t): int => $t->postCount ?? 0)->max();

        return $this->render('filter.html.twig', [
            'calendar' => $calendar,
            'categories' => $categories,
            'tags' => $tags,
            'max_post_count' => $maxPostCount,
        ]);
    }

    /**
     * @param array<string,mixed> $templateParams
     */
    private function posts(
        FilterPostsCriteria $criteria,
        string $template,
        Request $request,
        array $templateParams = []
    ): Response {
        $posts = $this->postRepository->filterPosts($criteria);

        if ($posts->isEmpty()) {
            throw new NotFoundHttpException();
        }

        $requestFormat = $request->getRequestFormat();

        if (in_array($requestFormat, [ 'rss', 'atom' ], strict: true)) {
            /** @var array<Post> $feedItems */
            $feedItems = $posts->pluck('post')->all();

            return new Response($this->serializer->serialize(new Feed($feedItems), $requestFormat));
        }

        $pageCount = ceil($this->postRepository->countFilteredPosts($criteria) / self::PER_PAGE);

        return $this->render($template, array_merge([
            'posts' => $posts,
            'page' => $criteria->page,
            'page_count' => $pageCount,
        ], $templateParams));
    }
}
