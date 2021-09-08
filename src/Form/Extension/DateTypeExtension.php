<?php

namespace SumoCoders\FrameworkCoreBundle\Form\Extension;

use DateTime;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class DateTypeExtension extends AbstractTypeExtension
{
    public static function getExtendedTypes(): iterable
    {
        return [DateType::class];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'format' => 'dd/MM/yyyy',
                'widget' => 'single_text',
                'maximum_date' => null,
                'minimum_date' => null,
                'html5' => false,
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
        $view->vars['maximum_date'] = $options['maximum_date'] ?
            DateTime::createFromFormat($options['format'], $options['maximum_date']) : null;
        $view->vars['minimum_date'] = $options['minimum_date'] ?
            DateTime::createFromFormat($options['format'], $options['minimum_date']) : null;
        $view->vars['format'] = $options['format'];
        $view->vars['divider'] = (strpos($options['format'], '-') !== false) ? '-' : '/';
        $view->vars['datepicker'] = $options['datepicker'] ?? false;
        $view->vars['helper_text'] = $options['helper_text'] ?? null;
    }
}
