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

    #[DataProvider('searchResultsSummaryProvider')]
    public function testSearchResultsSummary(string $text, string $query, string $expected): void
    {
        $featureRepository = new FeatureRepository([]);
        $appExtension = new AppExtension($featureRepository);

        $this->assertSame($expected, $appExtension->searchResultsSummary($text, $query));
    }

    /**
     * @return array<string,array<mixed>>
     */
    public static function searchResultsSummaryProvider(): array
    {
        $bigText = str_replace([ "\n" ], [ '' ], <<<BIGTEXT
        Lorem ipsum dolor sit amet, consectetur adipiscing elit. Suspendisse imperdiet scelerisque tempor. Vivamus
        dignissim euismod dui ut blandit. Fusce vehicula sapien sed nulla molestie, id rhoncus erat hendrerit.
        Aliquam sem massa, consequat et libero consectetur, viverra tempus lacus. In hac habitasse platea dictumst.
        Curabitur convallis, enim at eleifend malesuada, risus arcu mollis felis, nec viverra enim ex ut risus.
        Donec ullamcorper tortor sed est auctor laoreet. Test Sed non urna libero. Nulla aliquam ornare mi, in
        consectetur dolor volutpat volutpat. Duis sit amet dui arcu. Fusce et mollis augue, eget malesuada nulla.
        Aliquam lobortis in erat vitae mollis. Curabitur bibendum, dolor scelerisque varius tempus, nibh leo
        porttitor erat, eget imperdiet leo lorem et magna. Donec ac lectus bibendum risus condimentum mattis ac sed
        felis. Vivamus neque risus, pellentesque in ante sodales, porta pharetra.
        BIGTEXT);

        return [
            'nothing' => [ '', '', '<mark></mark>' ],
            'not found query' => [ 'lorem ipsum', 'test', 'lorem ipsum' ],
            'empty not found query' => [ 'lorem ipsum', '', '<mark></mark>lorem ipsum' ],
            'empty text query' => [ '', 'test', '' ],
            'query found' => [ 'lorem test ipsum', 'test', 'lorem <mark>test</mark> ipsum' ],
            'query found case insensitive' => [ 'Lorem Test Ipsum', 'test', 'Lorem <mark>Test</mark> Ipsum' ],
            // phpcs:disable Generic.Files.LineLength.TooLong
            'big text query found start' => [ $bigText, 'lorem', '<mark>Lorem</mark> ipsum dolor sit amet, consectetur adipiscing elit. Suspendisse imperdiet scelerisque tempor. Vivamusdignissim euismod dui ut blandit. Fusce vehicula sapien sed nulla molestie, id rhoncus erat hendrerit.Aliquam sem massa, consequat et libero consectetur, v' ],
            'big text query found end' => [ $bigText, 'Pharetra.', 'magna. Donec ac lectus bibendum risus condimentum mattis ac sedfelis. Vivamus neque risus, pellentesque in ante sodales, porta <mark>pharetra.</mark>' ],
            'big text query found middle' => [ $bigText, 'test', 'm at eleifend malesuada, risus arcu mollis felis, nec viverra enim ex ut risus.Donec ullamcorper tortor sed est auctor laoreet. <mark>Test</mark> Sed non urna libero. Nulla aliquam ornare mi, inconsectetur dolor volutpat volutpat. Duis sit amet dui arcu. Fusce et mollis au' ],
            // phpcs:enable
        ];
    }
}
