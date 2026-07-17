<?php

namespace SumoCoders\FrameworkCoreBundle\Form\Type;

use stdClass;
use SumoCoders\FrameworkCoreBundle\ValueObject\AbstractFile;
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

class FileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['show_remove_file']) {
            $builder->add(
                'remove',
                CheckboxType::class,
                [
                    'required' => false,
                    'label' => $options['remove_file_label'],
                    'property_path' => 'pendingDeletion',
                ],
            );
        }

        $builder
            ->addEventListener(
                FormEvents::PRE_SET_DATA,
                static function (FormEvent $event) use ($options): void {
                    // @mago-expect analysis:mixed-method-access
                    $fileIsEmpty = $event->getData() === null || $event->getData()->getFileName() === null;
                    $required = $fileIsEmpty && $options['required'] === true;
                    $fileFieldOptions = [
                        'label' => false,
                        'required' => $required,
                        'attr' => ['accept' => $options['accept']],
                    ];
                    if ($required) {
                        $fileFieldOptions['constraints'] = [
                            new NotBlank(
                                message: (string) $options['required_file_error'],
                            ),
                        ];
                    }
                    $event->getForm()->add('file', SymfonyFileType::class, $fileFieldOptions);
                },
            )
            ->addModelTransformer(
                new CallbackTransformer(
                    // @mago-expect analysis:missing-return-type
                    static fn (?AbstractFile $file = null) => $file,
                    // @mago-expect analysis:missing-parameter-type
                    static function ($file) use ($options): AbstractFile {
                        if (!$file instanceof AbstractFile && !$file instanceof stdClass) {
                            throw new TransformationFailedException('Invalid class for the file');
                        }

                        // @mago-expect analysis:mixed-assignment
                        $fileClass = $options['file_class'];

                        if (!$file instanceof AbstractFile) {
                            // @mago-expect analysis:mixed-assignment,non-existent-method
                            // @phpstan-ignore method.nonObject
                            $file = $fileClass::fromUploadedFile($file->getFile());
                        }

                        // return a clone to make sure that doctrine will do the lifecycle callbacks
                        // @mago-expect analysis:mixed-return-statement,mixed-clone
                        // @phpstan-ignore return.type
                        return clone $file;
                    },
                ),
            );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(
            [
                'file_class',
                'show_preview',
                'preview_label',
                'show_remove_file',
                'remove_file_label',
                'required_file_error',
                'help',
                'accept',
            ],
        );

        $resolver->setDefaults(
            [
                'data_class' => AbstractFile::class,
                'preview_label' => 'forms.labels.viewCurrentFile',
                'remove_file_label' => 'forms.labels.removeFile',
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
                'show_preview' => true,
                'show_remove_file' => true,
                'required_file_error' => 'forms.not_blank',
                'accept' => null,
                'constraints' => [new Valid()],
                'error_bubbling' => false,
            ],
        );
    }

    public function getBlockPrefix(): string
    {
        return 'sumoFile';
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['show_preview'] = $options['show_preview'];
        $view->vars['show_remove_file'] =
            // @mago-expect analysis:mixed-method-access,mixed-operand
            $options['show_remove_file'] && $form->getData() !== null && $form->getData()->getFileName() !== null;
        // if you need to have an file you shouldn't be allowed to remove it
        if ($options['required'] === true) {
            $view->vars['show_remove_file'] = false;
        }
        // @mago-expect analysis:mixed-method-access
        $imageIsEmpty = $form->getData() === null || $form->getData()->getFileName() === null;
        $view->vars['required'] = $imageIsEmpty && $options['required'] === true;

        $view->vars['preview_url'] = false;
        if ($form->getData() instanceof AbstractFile) {
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
                'preview_label',
            ],
        );
    }
}
