<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Comment;
use App\Message\MarkCommentAsSpam;
use App\Message\MarkCommentAsUnapproved;
use App\Repository\CommentRepository;
use App\Repository\Criteria\FilterCommentsCriteria;
use App\Repository\PostRepository;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\String\TruncateMode;

use function Symfony\Component\String\u;

#[AsCommand(name: 'comments', description: 'Manage comments')]
class ListCommentsCommand extends Command
{
    use HandleTrait;

    public function __construct(
        private readonly CommentRepository $commentRepository,
        private readonly PostRepository $postRepository,
        MessageBusInterface $messageBus,
    ) {
        parent::__construct();

        $this->messageBus = $messageBus;
    }

    protected function configure(): void
    {
        $this->addOption('alias', null, InputOption::VALUE_REQUIRED, 'Alias of a single post to fetch')
            ->addOption('spam', null, InputOption::VALUE_REQUIRED, 'Fetch comments flagged as spam')
            ->addOption('approved', null, InputOption::VALUE_REQUIRED, 'Fetch comments flagged as approved')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $currentPage = 1;

        do {
            $comments = $this->outputCommentsTable($io, $input, $currentPage);
            $answer = $io->askQuestion(new ChoiceQuestion('Action', [
                'n' => 'Next page',
                'p' => 'Previous page',
                's' => 'Mark as spam',
                'a' => 'Approve',
                'q' => 'Quit',
            ]));

            if ($answer === 's' || $answer === 'a') {
                $q = new Question('Index');
                $q->setValidator(function (string $a): int {
                    if (intval($a) < 1 || intval($a) > 10) {
                        throw new RuntimeException('Index should be between 1 and 10');
                    }

                    return intval($a);
                });

                /** @var int $idx */
                $idx = $io->askQuestion($q);
                $comment = $comments[$idx - 1];

                if ($answer === 's') {
                    $this->handle(new MarkCommentAsSpam($comment));
                    $io->success('Comment marked as spam');
                } else {
                    $this->handle(new MarkCommentAsUnapproved($comment));
                    $io->success('Comment marked as unapproved');
                }
            } elseif ($answer === 'n') {
                $currentPage++;
            } elseif ($answer === 'p') {
                $currentPage--;
            }
        } while ($answer !== 'q');

        return Command::SUCCESS;
    }

    /**
     * @return Collection<int,Comment>
     */
    private function outputCommentsTable(SymfonyStyle $io, InputInterface $input, int $page = 1): Collection
    {
        $comments = $this->commentRepository->filterComments($this->getCriteria($input, $page));

        $io->table(
            [ 'Idx', 'Date', 'Post', 'Author', 'Comment', 'Spam', 'Approved' ],
            $comments->map(function (Comment $c, int $idx): array {
                return [
                    $idx + 1,
                    $c->getCreated()->format('Y-m-d\\TH:i:s'),
                    $c->getPost()->getFullTitle(),
                    sprintf('%s <%s>', $c->getAuthorName(), $c->getAuthorEmail()),
                    u($c->getComment())->truncate(50, ellipsis: '…', cut: TruncateMode::WordBefore),
                    $c->isSpam() ? '✅' : '❌',
                    $c->isApproved() ? '✅' : '❌',
                ];
            })->all()
        );

        return $comments;
    }

    private function getCriteria(InputInterface $input, int $page = 1): FilterCommentsCriteria
    {
        $spam = $input->getOption('spam');
        $approved = $input->getOption('approved');
        $alias = $input->getOption('alias');
        $post = null;

        if (is_string($alias)) {
            $post = $this->postRepository->findOneBy([ 'alias' => $alias ]);

            if ($post === null) {
                throw new InvalidArgumentException('Unable to find post with alias "' . $alias . '"');
            }
        }

        return new FilterCommentsCriteria(
            post: $post,
            approved: ($approved !== null) ? boolval($approved) : null,
            spam: ($spam !== null) ? boolval($spam) : null,
            page: $page,
        );
    }
}
