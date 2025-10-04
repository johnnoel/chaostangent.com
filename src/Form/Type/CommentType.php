<?php

declare(strict_types=1);

namespace App\Form\Type;

use App\Form\DataTransformer\HtmlPurifierTransformer;
use App\Form\Model\CommentModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<CommentModel>
 */
class CommentType extends AbstractType
{
    /** @inheritdoc */
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('authorName', TextType::class, [
            'required' => true,
            'trim' => true,
            'attr' => [ 'minlength' => 2 ],
        ])->add('authorEmail', EmailType::class, [
            'required' => true,
            'trim' => true,
        ])->add('authorUrl', UrlType::class, [
            'required' => false,
        ])->add('comment', TextareaType::class, [
            'required' => true,
            'attr' => [ 'minlength' => 10 ],
        ])->add('honeypot', CheckboxType::class, [
            'required' => false,
        ])->add('formRendered', FormRenderedType::class, [
            'required' => true,
        ]);

        $builder->get('comment')->addModelTransformer(new HtmlPurifierTransformer());
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CommentModel::class,
            'csrf_protection' => false,
        ]);
    }
}
