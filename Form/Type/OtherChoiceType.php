<?php

namespace SumoCoders\FrameworkCoreBundle\Form\Type;

use Doctrine\ORM\EntityRepository;
use SumoCoders\FrameworkCoreBundle\Entity\OtherChoiceOption;
use SumoCoders\FrameworkCoreBundle\Repository\OtherChoiceOptionRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class OtherChoiceType extends AbstractType
{
    /** @var OtherChoiceOptionRepository */
    private $repository;

    /** @var OtherChoiceOption */
    private $otherChoiceOption;

    /** @var TranslatorInterface */
    private $translator;

    public function __construct(EntityRepository $repository, TranslatorInterface $translator)
    {
        $this->repository = $repository;
        $this->translator = $translator;
        $this->otherChoiceOption = OtherChoiceOption::getOtherOption(
            $translator->trans('forms.labels.other'),
            $translator->getLocale()
        );
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $category = $options['choices_category'];
        $builder->add(
            'choices',
            ChoiceType::class,
            [
                'label' => false,
                'choice_loader' => new CallbackChoiceLoader(
                    function () use ($category) : array {
                        $choices = (array) $this->repository->findBy(
                            ['category' => $category, 'locale' => $this->translator->getLocale()],
                            ['label' => 'ASC']
                        );
                        $choices[] = $this->otherChoiceOption->getWithNewChoiceOptionCategory($category);

                        return $choices;
                    }
                ),
                'choice_value' => function (OtherChoiceOption $choiceOption = null) : string {
                    if ($choiceOption === null) {
                        return '';
                    }

                    return $choiceOption->getValue();
                },
                'choice_label' => function (OtherChoiceOption $choiceOption) : string {
                    return (string) $choiceOption;
                },
                'placeholder' => $options['placeholder'],
                'attr' => [
                    'data-role' => 'other-choice-list',
                ],
            ]
        )->add(
            'other',
            TextType::class,
            [
                'label' => false,
                'attr' => [
                    'data-role' => 'other-choice-text-input',
                ],
            ]
        )->addModelTransformer(
            new CallbackTransformer(
                function (?OtherChoiceOption $selectedOption) : array {
                    return [
                        'choices' => $selectedOption,
                        'other' => null,
                    ];
                },
                function (array $data) use ($options) : ?OtherChoiceOption {
                    if ($data['choices'] === null) {
                        return null;
                    }

                    if ($data['choices']->getCategory() === 'other') {
                        if (empty($data['other'])) {
                            throw new TransformationFailedException('other value must be filled');
                        }

                        // check that they didn't retype a value that is already in there
                        $existingChoice = $this->repository->findOneBy(
                            ['category' => $options['choices_category'], 'label' => $data['other']]
                        );
                        if ($existingChoice instanceof OtherChoiceOption) {
                            return $existingChoice;
                        }

                        $data['choices']->transformToActualChoiceOption($data['other']);
                        $this->repository->create($data['choices']);
                    }

                    return $data['choices'];
                }
            )
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(
            [
                'placeholder' => '',
                'error_bubbling' => false,
            ]
        );

        $resolver->setRequired(
            [
                'choices_category',
            ]
        );
    }
}
