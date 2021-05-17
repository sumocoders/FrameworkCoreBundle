<?php

namespace SumoCoders\FrameworkCoreBundle\Form\Extension;

use IntlDateFormatter;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class DateTimeTypeExtension extends AbstractTypeExtension
{
    public static function getExtendedTypes(): iterable
    {
        return [DateTimeType::class];
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'format' => 'dd/MM/yyyy HH:mm:ss',
                'datetimepicker' => true,
                'maximum_date' => null,
                'minimum_date' => null,
                'html5' => false,
            ]
        );

        $resolver->setDefined(['helper_text']);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['maximum_date'] = $options['maximum_date'] ?
            IntlDateFormatter::formatObject($options['maximum_date'], $options['format']) : null;
        $view->vars['minimum_date'] = $options['minimum_date'] ?
            IntlDateFormatter::formatObject($options['minimum_date'], $options['format']) : null;
        $view->vars['format'] = $options['format'];
        $view->vars['divider'] = (strpos($options['format'], '-') !== false) ? '-' : '/';
        $view->vars['datetimepicker'] = $options['datetimepicker'] ?? false;
        $view->vars['helper_text'] = $options['helper_text'] ?? null;
    }
}
