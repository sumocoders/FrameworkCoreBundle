<?php

declare(strict_types=1);

namespace SumoCoders\FrameworkCoreBundle\Form\Type;

use SumoCoders\FrameworkCoreBundle\DataTransferObject\SeoDataTransferObject;
use SumoCoders\FrameworkCoreBundle\Enum\RobotsDirective;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class SeoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('overridePageTitle', CheckboxType::class, ['required' => false, 'label' => false])
            ->add('pageTitle', TextType::class, [
                'required' => false,
                'label' => 'seo.label.pageTitle',
                'help' => 'seo.help.pageTitle',
                'attr' => ['maxlength' => 100],
            ])
            ->add('overrideMetaDescription', CheckboxType::class, ['required' => false, 'label' => false])
            ->add('metaDescription', TextType::class, [
                'required' => false,
                'label' => 'seo.label.metaDescription',
                'help' => 'seo.help.metaDescription',
                'attr' => ['maxlength' => 255],
            ])
            ->add('robotsIndex', EnumType::class, [
                'class' => RobotsDirective::class,
                'choices' => [RobotsDirective::Index, RobotsDirective::NoIndex],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
                'placeholder' => 'seo.robots.none',
                'label' => 'seo.label.robotsIndex',
                'help' => 'seo.help.robotsIndex',
                'label_attr' => ['class' => 'radio-inline'],
            ])
            ->add('robotsFollow', EnumType::class, [
                'class' => RobotsDirective::class,
                'choices' => [RobotsDirective::Follow, RobotsDirective::NoFollow],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
                'placeholder' => 'seo.robots.none',
                'label' => 'seo.label.robotsFollow',
                'help' => 'seo.help.robotsFollow',
                'label_attr' => ['class' => 'radio-inline'],
            ])
            ->add('robotsAdditional', EnumType::class, [
                'class' => RobotsDirective::class,
                'choices' => [RobotsDirective::NoArchive, RobotsDirective::NoSnippet, RobotsDirective::NoImageIndex],
                'expanded' => true,
                'multiple' => true,
                'required' => false,
                'label' => 'seo.label.robotsAdditional',
                'help' => 'seo.help.robotsAdditional',
                'label_attr' => ['class' => 'checkbox-inline'],
            ]);
    }

    /** @param array<string, mixed> $options */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['page_title_source_selector'] = $options['page_title_source_selector'];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => SeoDataTransferObject::class, 'label' => false, 'required' => false]);
        $resolver
            ->define('page_title_source_selector')
            ->allowedTypes('string')
            ->default('');
    }

    public function getBlockPrefix(): string
    {
        return 'seo';
    }
}
