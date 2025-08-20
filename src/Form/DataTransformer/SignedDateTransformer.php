<?php

declare(strict_types=1);

namespace App\Form\DataTransformer;

use DateTimeImmutable;
use DateTimeInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

/**
 * @implements DataTransformerInterface<DateTimeInterface,string>
 */
readonly final class SignedDateTransformer implements DataTransformerInterface
{
    private const string FORMAT = DateTimeInterface::RFC3339_EXTENDED;

    public function __construct(private string $key, private string $algo = 'sha256')
    {
    }

    public function transform(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!($value instanceof DateTimeImmutable)) {
            throw new UnexpectedTypeException($value, DateTimeImmutable::class);
        }

        $dateTime = $value->format(self::FORMAT);
        $signature = hash_hmac($this->algo, $dateTime, $this->key, true);

        return sprintf('%s|%s', $dateTime, base64_encode($signature));
    }

    public function reverseTransform(mixed $value): ?DateTimeImmutable
    {
        if ($value === null) {
            return null;
        }

        if (!is_string($value)) { /** @phpstan-ignore function.alreadyNarrowedType */
            throw new UnexpectedTypeException($value, 'string');
        }

        if (!str_contains($value, '|')) {
            throw new TransformationFailedException('$value is not in the correct format');
        }

        [ $rawDateTime, $rawSignature ] = explode('|', $value, 2);

        $dateTime = DateTimeImmutable::createFromFormat(self::FORMAT, $rawDateTime);
        $signature = base64_decode($rawSignature, strict: true);

        if ($dateTime === false || $signature === false) {
            throw new TransformationFailedException('$value does not contain correct data');
        }

        if (hash_hmac($this->algo, $rawDateTime, $this->key, true) !== $signature) {
            throw new TransformationFailedException('$value signature is not correct');
        }

        return $dateTime;
    }
}
