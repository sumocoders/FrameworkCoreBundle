<?php

namespace SumoCoders\FrameworkCoreBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

final class CollectionTypeExtension extends AbstractTypeExtension
{
    public static function getExtendedTypes(): iterable
    {
        return [CollectionType::class];
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'allow_drag_and_drop' => true,
                'add_button_label' => 'forms.buttons.addItem',
                'min' => 0,
                'max' => null,
            ]
        );
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['allow_drag_and_drop'] = $options['allow_drag_and_drop'];
        $view->vars['add_button_label'] = $options['add_button_label'];
        $view->vars['min'] = $options['min'];
        $view->vars['max'] = $options['max'];
    }
}
