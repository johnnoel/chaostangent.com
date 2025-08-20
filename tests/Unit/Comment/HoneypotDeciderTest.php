<?php

declare(strict_types=1);

namespace App\Tests\Unit\Comment;

use App\Comment\HoneypotDecider;
use App\Form\Model\CommentModel;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(HoneypotDecider::class)]
class HoneypotDeciderTest extends TestCase
{
    public function testIsSpam(): void
    {
        $decider = new HoneypotDecider();

        $model = new CommentModel();
        $model->honeypot = true;

        $this->assertTrue($decider->isSpam($model));

        $model->honeypot = false;
        $this->assertFalse($decider->isSpam($model));
    }
}
