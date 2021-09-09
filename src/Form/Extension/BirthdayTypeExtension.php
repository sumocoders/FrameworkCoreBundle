<?php

namespace SumoCoders\FrameworkCoreBundle\Form\Extension;

use DateTime;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class BirthdayTypeExtension extends AbstractTypeExtension
{
    public static function getExtendedTypes(): iterable
    {
        return [BirthdayType::class];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'maximum_date' => new DateTime(),
            ]
        );
    }
}
