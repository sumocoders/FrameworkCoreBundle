<?php

namespace SumoCoders\FrameworkCoreBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FieldsetType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults(
                [
                    'fieldset_class' => '',
                    'legend_class' => '',
                    'legend' => '',
                    'virtual' => true,
                    'options' => [],
                    'fields' => [],
                    'open_row' => false,
                    'close_row' => false,
                ]
            )
            ->addAllowedTypes('fields', ['array', 'callable']);
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (empty($options['fields'])) {
            return;
        }

        if (is_callable($options['fields'])) {
            $options['fields']($builder);

            return;
        }

        foreach ($options['fields'] as $field) {
            $builder->add($field['name'], $field['type'], $field['attr']);
        }
    }

    /**
     * @param FormView $view
     * @param FormInterface $form
     * @param array $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if (false !== $options['legend']) {
            $view->vars['legend'] = $options['legend'];
        }
        if (false !== $options['open_row']) {
            $view->vars['open_row'] = $options['open_row'];
        }
        if (false !== $options['close_row']) {
            $view->vars['close_row'] = $options['close_row'];
        }
        if (!empty($options['fieldset_class'])) {
            $view->vars['fieldset_class'] = $options['fieldset_class'];
        }
        if (!empty($options['legend_class'])) {
            $view->vars['legend_class'] = $options['legend_class'];
        }
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'fieldset';
    }
}
