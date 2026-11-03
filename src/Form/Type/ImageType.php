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
                                message: (string) $options['required_image_error'],
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
                        protected ?UploadedFile $file = null;
                        protected bool $pendingDeletion = false;

                        // image_widget reads fileName to decide on a preview; an empty field has none
                        public function getFileName(): null
                        {
                            return null;
                        }

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
        // @mago-expect analysis:mixed-assignment
        $image = $form->getData();
        $hasFile = $image instanceof AbstractImage && $image->getFileName() !== null;
        // if you need to have an image you shouldn't be allowed to remove it
        // @mago-expect analysis:mixed-operand
        $view->vars['show_remove_image'] = $options['show_remove_image'] && $hasFile && $options['required'] !== true;
        $view->vars['required'] = !$hasFile && $options['required'] === true;

        // getWebPath() throws without a file and fallback; an image without a file has nothing to preview anyway
        $view->vars['preview_url'] = $hasFile ? $image->getWebPath() : false;

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
