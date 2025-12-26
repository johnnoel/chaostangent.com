<?php

declare(strict_types=1);

namespace App\Form\Type;

use App\Form\Model\WebmentionModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<WebmentionModel>
 */
final class WebmentionType extends AbstractType
{
    /** @inheritdoc */
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('source', UrlType::class, [
            'required' => true,
            'trim' => true,
        ])->add('target', UrlType::class, [
            'required' => true,
            'trim' => true,
        ]);
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return '';
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => WebmentionModel::class,
            'csrf_protection' => false,
        ]);
    }
}
