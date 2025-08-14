<?php

namespace SumoCoders\FrameworkCoreBundle\Form\Type;

use SumoCoders\FrameworkCoreBundle\Intl\BelgiumPostCodes;
use SumoCoders\FrameworkCoreBundle\ValueObject\BelgiumPostCode;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\ChoiceList\ChoiceList;
use Symfony\Component\Form\ChoiceList\Loader\IntlCallbackChoiceLoader;
use Symfony\Component\Form\Exception\LogicException;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Intl\Intl;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BelgiumPostCodeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(
            new CallbackTransformer(
                function (?BelgiumPostCode $object): ?string {
                    return $object ? ($object->postcode . '|' . $object->municipality) : null;
                },
                function (?string $postcodeKey): ?BelgiumPostCode {
                    if ($postcodeKey === null || $postcodeKey === '') {
                        return null;
                    }

                    [$postcode, $municipality] = explode('|', $postcodeKey, 2);
                    return new BelgiumPostCode(
                        $postcode,
                        $municipality
                    );
                }
            )
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choice_loader' => function (Options $options) {
                if (!class_exists(Intl::class)) {
                    throw new LogicException(sprintf('The "symfony/intl" component is required to use "%s". Try running "composer require symfony/intl".', static::class)); // phpcs:ignore Generic.Files.LineLength
                }

                return ChoiceList::loader(
                    $this,
                    new IntlCallbackChoiceLoader(static fn() => array_flip(BelgiumPostCodes::getNames()))
                );
            },
            'choice_translation_domain' => false,
            'invalid_message' => 'Please select a valid postcode.',
            'placeholder' => '',
            'autocomplete' => true,
            'label' => 'Postcode and municipality',
        ]);
    }

    public function getParent(): ?string
    {
        return ChoiceType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'sumoBelgiumPostCode';
    }
}
