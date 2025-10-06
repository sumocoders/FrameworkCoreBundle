<?php

namespace SumoCoders\FrameworkCoreBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

final class TogglePasswordTypeExtension extends AbstractTypeExtension
{
    public function __construct(private readonly ?TranslatorInterface $translator)
    {
    }

    public static function getExtendedTypes(): iterable
    {
        return [PasswordType::class];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'toggle' => true,
            'hidden_label' => 'Hide password',
            'visible_label' => 'Show password',
            'button_classes' => ['toggle-password-button'],
            'toggle_container_classes' => ['toggle-password-container'],
            'use_toggle_form_theme' => true,
        ]);

        $resolver->setAllowedTypes('toggle', ['bool']);
        $resolver->setAllowedTypes('hidden_label', ['string', TranslatableMessage::class, 'null']);
        $resolver->setAllowedTypes('visible_label', ['string', TranslatableMessage::class, 'null']);
        $resolver->setAllowedTypes('button_classes', ['string[]']);
        $resolver->setAllowedTypes('toggle_container_classes', ['string[]']);
        $resolver->setAllowedTypes('use_toggle_form_theme', ['bool']);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['toggle'] = $options['toggle'];

        if (!$options['toggle']) {
            return;
        }

        if ($options['use_toggle_form_theme']) {
            array_splice($view->vars['block_prefixes'], -1, 0, 'toggle_password');
        }

        $controllerName = 'toggle-password';
        $view->vars['attr']['data-controller'] = trim(\sprintf('%s %s', $view->vars['attr']['data-controller'] ?? '', $controllerName));

        $controllerValues['hidden-label'] = $this->translateLabel($options['hidden_label'], $options['translation_domain']);
        $controllerValues['visible-label'] = $this->translateLabel($options['visible_label'], $options['translation_domain']);

        $controllerValues['button-classes'] = json_encode($options['button_classes'], \JSON_THROW_ON_ERROR);

        foreach ($controllerValues as $name => $value) {
            $view->vars['attr'][\sprintf('data-%s-%s-value', $controllerName, $name)] = $value;
        }

        $view->vars['toggle_container_classes'] = $options['toggle_container_classes'];
    }

    private function translateLabel(string|TranslatableMessage|null $label, ?string $translationDomain): ?string
    {
        if (null === $this->translator || null === $label) {
            return $label;
        }

        if ($label instanceof TranslatableMessage) {
            return $label->trans($this->translator);
        }

        return $this->translator->trans($label, domain: $translationDomain);
    }
}
