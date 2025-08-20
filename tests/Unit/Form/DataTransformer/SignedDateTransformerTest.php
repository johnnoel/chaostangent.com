<?php

declare(strict_types=1);

namespace App\Tests\Unit\Form\DataTransformer;

use App\Form\DataTransformer\SignedDateTransformer;
use DateTimeImmutable;
use DateTimeInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

#[CoversClass(SignedDateTransformer::class)]
class SignedDateTransformerTest extends TestCase
{
    public function testTransformNull(): void
    {
        $transformer = new SignedDateTransformer('test123');
        $this->assertNull($transformer->transform(null));
    }

    public function testTransformNotDateTimeImmutable(): void
    {
        $transformer = new SignedDateTransformer('test123');
        $this->expectException(UnexpectedTypeException::class);

        $transformer->transform('test123'); /** @phpstan-ignore argument.type */
    }

    public function testTransform(): void
    {
        $dateTime = new DateTimeImmutable('now');
        $formattedDateTime = $dateTime->format(DateTimeInterface::RFC3339_EXTENDED);
        $expectedSignature = base64_encode(hash_hmac('sha256', $formattedDateTime, 'test123', true));

        $transformer = new SignedDateTransformer('test123', 'sha256');
        $this->assertSame($formattedDateTime . '|' . $expectedSignature, $transformer->transform($dateTime));
    }

    public function testReverseTransformNull(): void
    {
        $transformer = new SignedDateTransformer('test123');
        $this->assertNull($transformer->reverseTransform(null));
    }

    public function testReverseTransformNotString(): void
    {
        $transformer = new SignedDateTransformer('test123');
        $this->expectException(UnexpectedTypeException::class);
        $transformer->reverseTransform(12345); /** @phpstan-ignore argument.type */
    }

    public function testReverseTransformIncorrectFormat(): void
    {
        $transformer = new SignedDateTransformer('test123');
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('$value is not in the correct format');
        $transformer->reverseTransform('test123');
    }

    public function testReverseTransformBadDate(): void
    {
        $transformer = new SignedDateTransformer('test123');
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('$value does not contain correct data');
        $transformer->reverseTransform('test123|' . base64_encode('test123'));
    }

    public function testReverseTransformBadSignature(): void
    {
        $transformer = new SignedDateTransformer('test123');
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('$value does not contain correct data');
        $dateTime = (new DateTimeImmutable('now'))->format(DateTimeInterface::RFC3339_EXTENDED);
        $transformer->reverseTransform($dateTime . '|!"£$%');
    }

    public function testReverseTransformIncorrectSignature(): void
    {
        $transformer = new SignedDateTransformer('test123');
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('$value signature is not correct');
        $dateTime = (new DateTimeImmutable('now'))->format(DateTimeInterface::RFC3339_EXTENDED);
        $transformer->reverseTransform($dateTime . '|' . base64_encode('test123'));
    }

    public function testReverseTransform(): void
    {
        $transformer = new SignedDateTransformer('test123', 'sha256');
        $dateTime = new DateTimeImmutable('now');
        $formattedDateTime = $dateTime->format(DateTimeInterface::RFC3339_EXTENDED);
        $signature = base64_encode(hash_hmac('sha256', $formattedDateTime, 'test123', true));

        $this->assertSame($formattedDateTime . '|' . $signature, $transformer->transform($dateTime));
    }
}
