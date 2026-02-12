<?php

namespace SumoCoders\FrameworkCoreBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;

final class CollectionTypeExtension extends AbstractTypeExtension
{
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

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
                'minimum_required_items' => 0,
                'maximum_required_items' => null,
                'error_bubbling' => false,
            ]
        );
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        if ($options['minimum_required_items'] < 0) {
            throw new \InvalidArgumentException('minimum_required_items cannot be lower than 0');
        }

        if ($options['maximum_required_items'] !== null && $options['maximum_required_items'] < $options['minimum_required_items']) {
            throw new \InvalidArgumentException('maximum_required_items cannot be lower than minimum_required_items');
        }

        $builder->addEventListener(FormEvents::POST_SUBMIT, function(FormEvent $event) {
            $form = $event->getForm();
            $min = $form->getConfig()->getOption('minimum_required_items');
            $max = $form->getConfig()->getOption('maximum_required_items');

            if ($form->count() < $min) {
                $error = new FormError(
                    message: $this->translator->trans('You must add at least %count% items', ['%count%' => $min], 'validators'),
                    messageTemplate: 'You must add at least %count% items',
                    messageParameters: ['%count%' => $min]
                );
                $error->setOrigin($form);
                $form->addError($error);
            }

            if ($max !== null && $form->count() > $max) {
                $error = new FormError(
                    message: $this->translator->trans('You can add a maximum of %count% items', ['%count%' => $max], 'validators'),
                    messageTemplate: 'You can add a maximum of %count% items',
                    messageParameters: ['%count%' => $max]
                );
                $error->setOrigin($form);
                $form->addError($error);
            }
        });
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['allow_drag_and_drop'] = $options['allow_drag_and_drop'];
        $view->vars['add_button_label'] = $options['add_button_label'];
        $view->vars['minimum_required_items'] = $options['minimum_required_items'];
        $view->vars['maximum_required_items'] = $options['maximum_required_items'];
    }
}
