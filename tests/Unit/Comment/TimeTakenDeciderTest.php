<?php

declare(strict_types=1);

namespace App\Tests\Unit\Comment;

use App\Comment\TimeTakenDecider;
use App\Form\Model\CommentModel;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;

#[CoversClass(TimeTakenDecider::class)]
class TimeTakenDeciderTest extends TestCase
{
    use ClockSensitiveTrait;

    #[DataProvider('isSpamProvider')]
    public function testIsSpam(DateTimeImmutable $when, bool $expected): void
    {
        $clock = static::mockTime(new DateTimeImmutable('2025-08-20 00:01:00'));
        $decider = new TimeTakenDecider();
        $decider->setClock($clock);

        $model = new CommentModel(formRendered: $when);
        $this->assertSame($expected, $decider->isSpam($model));
    }

    /**
     * @return array<string,mixed>
     */
    public static function isSpamProvider(): array
    {
        return [
            'on render' => [ new DateTimeImmutable('2025-08-20 00:01:00'), true ],
            'nine seconds ago' => [ new DateTimeImmutable('2025-08-20 00:00:51'), true ],
            'ten seconds ago' => [ new DateTimeImmutable('2025-08-20 00:00:50'), false ],
            'eleven seconds ago' => [ new DateTimeImmutable('2025-08-20 00:00:49'), false ],
            'far in the past' => [ new DateTimeImmutable('2000-01-01 00:00:00'), false ],
            'far in the future' => [ new DateTimeImmutable('2099-01-01 00:00:00'), true ],
        ];
    }
}
