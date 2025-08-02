<?php

declare(strict_types=1);

namespace App\Message;

use App\Entity\Post;
use App\Form\Model\CommentModel;

readonly final class PostComment
{
    public function __construct(
        public Post $post,
        public CommentModel $commentModel
    ) {
    }
}
