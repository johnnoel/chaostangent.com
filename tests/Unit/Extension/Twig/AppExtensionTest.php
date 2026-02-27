<?php

declare(strict_types=1);

namespace App\Tests\Unit\Extension\Twig;

use App\Extension\Twig\AppExtension;
use App\Repository\FeatureRepository;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AppExtensionTest extends TestCase
{
    #[DataProvider('gravatarProvider')]
    public function testGravatar(string $email, string $default, int $size, string $rating): void
    {
        $featureRepository = new FeatureRepository([]);
        $appExtension = new AppExtension($featureRepository);

        $md5 = hash('md5', $email);
        $this->assertSame(
            '//www.gravatar.com/avatar/' . $md5 . '?d=' . $default . '&s=' . $size . '&r=' . $rating,
            $appExtension->gravatar($email, $default, $size, $rating)
        );
    }

    /**
     * @return array<string,array<mixed>>
     */
    public static function gravatarProvider(): array
    {
        return [
            'empty email' => [ '', 'identicon', 64, 'r' ],
            'just email' => [ 'test@test.test', 'identicon', 64, 'r' ],
            'different default' => [ 'test@test.test', 'test', 64, 'r' ],
            'different size' => [ 'test@test.test', 'identicon', 512, 'r' ],
            'different rating' => [ 'test@test.test', 'identicon', 512, 's' ],
        ];
    }

    #[DataProvider('ageProvider')]
    public function testAge(string $birthday, DateTimeImmutable $now, int $expectedAge): void
    {
        $featureRepository = new FeatureRepository([]);
        $appExtension = new AppExtension($featureRepository);

        $this->assertSame($expectedAge, $appExtension->age($birthday, $now));
    }

    /**
     * @return array<string,array<mixed>>
     */
    public static function ageProvider(): array
    {
        /** @var DateTimeImmutable $now */
        $now = DateTimeImmutable::createFromFormat('Y-m-d', '2026-02-27');

        return [
            'past' => [ '1983-07-07', $now, 42 ],
            'today' => [ '2026-02-27', $now, 0 ],
            'future' => [ '2090-02-27', $now, 64 ],
            'invalid' => [ '!"£$%%^&*()', $now, 0 ],
            'empty' => [ '', $now, 0 ],
        ];
    }
}
