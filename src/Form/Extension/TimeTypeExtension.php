<?php

namespace SumoCoders\FrameworkCoreBundle\Form\Extension;

use IntlDateFormatter;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class TimeTypeExtension extends AbstractTypeExtension
{
    public static function getExtendedTypes(): iterable
    {
        return [TimeType::class];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'format' => 'HH:mm',
                'widget' => 'single_text',
            ]
        );

        $resolver->setAllowedValues(
            'widget',
            [
            'single_text',
            'choice',
            ]
        );

        $resolver->setDefined(['helper_text']);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['format'] = $options['format'];
        $view->vars['divider'] = (strpos($options['format'], '-') !== false) ? '-' : '/';
        $view->vars['timepicker'] = $options['timepicker'] ?? false;
        $view->vars['helper_text'] = $options['helper_text'] ?? null;
    }
}
