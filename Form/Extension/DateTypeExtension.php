<?php

namespace SumoCoders\FrameworkCoreBundle\Form\Extension;

use IntlDateFormatter;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class DateTypeExtension extends AbstractTypeExtension
{
    public function getExtendedType()
    {
        return DateType::class;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['widget'] === 'single_text' && $options['datepicker'] === true) {
            $builder->addEventListener(
                FormEvents::PRE_SUBMIT,
                function (FormEvent $event) {
                    $data = $event->getData();

                    if ($data === null || $data === '') {
                        return;
                    }

                    $date = \DateTime::createFromFormat('d/m/Y', $data);

                    if ($date === false) {
                        return;
                    }

                    $event->setData($date->format('Y-m-d'));
                }
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'format' => DateType::HTML5_FORMAT,
                'datepicker' => true,
                'widget' => 'single_text',
                'maximum_date' => null,
                'minimum_date' => null,
            ]
        );

        $resolver->setAllowedValues(
            'widget',
            [
            'single_text',
            'choice',
            ]
        );
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
        $view->vars['datepicker'] = $options['datepicker'] ?? false;
    }
}
