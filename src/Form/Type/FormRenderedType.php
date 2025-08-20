<?php

declare(strict_types=1);

namespace App\Form\Type;

use App\Form\DataTransformer\SignedDateTransformer;
use DateTimeImmutable;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @extends AbstractType<DateTimeImmutable>
 */
class FormRenderedType extends AbstractType
{
    public function __construct(private readonly SignedDateTransformer $signedDateTransformer)
    {
    }

    /** @inheritdoc */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer($this->signedDateTransformer);
    }

    /**
     * @return class-string
     */
    public function getParent(): string
    {
        return HiddenType::class;
    }
}
