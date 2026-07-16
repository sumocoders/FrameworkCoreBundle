<?php

namespace SumoCoders\FrameworkCoreBundle\Form\Type;

use stdClass;
use SumoCoders\FrameworkCoreBundle\ValueObject\AbstractImage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType as SymfonyFileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Valid;

class ImageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['show_remove_image']) {
            $builder->add(
                'remove',
                CheckboxType::class,
                [
                    'required' => false,
                    'label' => $options['remove_image_label'],
                    'property_path' => 'pendingDeletion',
                ],
            );
        }

        $builder
            ->addEventListener(
                FormEvents::PRE_SET_DATA,
                static function (FormEvent $event) use ($options): void {
                    // @mago-expect analysis:mixed-method-access
                    $imageIsEmpty = $event->getData() === null || $event->getData()->getFileName() === null;
                    $required = $imageIsEmpty && $options['required'] === true;
                    $fileFieldOptions = [
                        'label' => false,
                        'required' => $required,
                        'attr' => ['accept' => $options['accept']],
                    ];
                    if ($required) {
                        $fileFieldOptions['constraints'] = [
                            new NotBlank(
                                message: $options['required_image_error']
                            ),
                        ];
                    }
                    $event->getForm()->add('file', SymfonyFileType::class, $fileFieldOptions);
                },
            )
            ->addModelTransformer(
                new CallbackTransformer(
                    // @mago-expect analysis:missing-return-type
                    static fn (?AbstractImage $image = null) => $image,
                    // @mago-expect analysis:missing-parameter-type
                    static function ($image) use ($options): AbstractImage {
                        if (!$image instanceof AbstractImage && !$image instanceof stdClass) {
                            throw new TransformationFailedException('Invalid class for the image');
                        }

                        // @mago-expect analysis:mixed-assignment
                        $imageClass = $options['image_class'];

                        if (!$image instanceof AbstractImage) {
                            // @mago-expect analysis:mixed-assignment,non-existent-method
                            // @phpstan-ignore method.nonObject
                            $image = $imageClass::fromUploadedFile($image->getFile());
                        }

                        // return a clone to make sure that doctrine will do the lifecycle callbacks
                        // @mago-expect analysis:mixed-return-statement,mixed-clone
                        // @phpstan-ignore return.type
                        return clone $image;
                    },
                ),
            );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(
            [
                'image_class',
                'show_preview',
                'show_remove_image',
                'remove_image_label',
                'required_image_error',
                'accept',
                'help',
            ],
        );

        $resolver->setDefaults(
            [
                'data_class' => AbstractImage::class,
                // @mago-expect lint:prefer-arrow-function
                // @mago-expect analysis:missing-return-type
                'empty_data' => static function () {
                    return new class extends StdClass {
                        protected ?UploadedFile $file;
                        protected bool $pendingDeletion = false;

                        public function setFile(?UploadedFile $file = null): void
                        {
                            $this->file = $file;
                        }

                        public function getFile(): ?UploadedFile
                        {
                            return $this->file;
                        }

                        public function getPendingDeletion(): bool
                        {
                            return $this->pendingDeletion;
                        }

                        public function setPendingDeletion(bool $pendingDeletion): void
                        {
                            $this->pendingDeletion = $pendingDeletion;
                        }
                    };
                },
                'preview_class' => 'img-thumbnail img-responsive',
                'show_preview' => true,
                'show_remove_image' => true,
                'remove_image_label' => 'forms.labels.removeImage',
                'required_image_error' => 'forms.not_blank',
                'accept' => 'image/*',
                'constraints' => [new Valid()],
                'error_bubbling' => false,
            ],
        );
    }

    public function getBlockPrefix(): string
    {
        return 'image';
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['show_preview'] = $options['show_preview'];
        $view->vars['show_remove_image'] =
            // @mago-expect analysis:mixed-method-access,mixed-operand
            $options['show_remove_image'] && $form->getData() !== null && $form->getData()->getFileName() !== null;
        // if you need to have an image you shouldn't be allowed to remove it
        if ($options['required'] === true) {
            $view->vars['show_remove_image'] = false;
        }
        // @mago-expect analysis:mixed-method-access
        $filesIsEmpty = $form->getData() === null || $form->getData()->getFileName() === null;
        $view->vars['required'] = $filesIsEmpty && $options['required'] === true;

        $view->vars['preview_url'] = false;
        if ($form->getData() instanceof AbstractImage) {
            // @mago-expect analysis:mixed-method-access
            $view->vars['preview_url'] = $form->getData()->getWebPath();
        }

        array_map(
            static function (string $optionName) use ($options, &$view): void {
                if (array_key_exists($optionName, $options) && $options[$optionName] !== null) {
                    $view->vars[$optionName] = $options[$optionName];
                }
            },
            [
                'preview_class',
            ],
        );
    }
}
