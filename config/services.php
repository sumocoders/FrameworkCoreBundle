<?php

declare(strict_types=1);

use SumoCoders\FrameworkCoreBundle\Command\TranslateCommand;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use SumoCoders\FrameworkCoreBundle\Service\Fallbacks;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use SumoCoders\FrameworkCoreBundle\Twig\PaginatorRuntime;
use SumoCoders\FrameworkCoreBundle\Twig\PaginatorExtension;
use SumoCoders\FrameworkCoreBundle\Twig\FrameworkExtension;
use SumoCoders\FrameworkCoreBundle\Service\Theme;
use SumoCoders\FrameworkCoreBundle\Service\JsData;
use SumoCoders\FrameworkCoreBundle\Service\BreadcrumbTrail;
use SumoCoders\FrameworkCoreBundle\EventListener\BreadcrumbListener;
use SumoCoders\FrameworkCoreBundle\Menu\MenuBuilder;
use SumoCoders\FrameworkCoreBundle\Form\Type\ImageType;
use SumoCoders\FrameworkCoreBundle\Form\Type\FileType;
use SumoCoders\FrameworkCoreBundle\Form\Extension\TimeTypeExtension;
use SumoCoders\FrameworkCoreBundle\Form\Extension\DateTypeExtension;
use SumoCoders\FrameworkCoreBundle\Form\Extension\DateTimeTypeExtension;
use SumoCoders\FrameworkCoreBundle\Form\Extension\CollectionTypeExtension;
use SumoCoders\FrameworkCoreBundle\Form\Extension\BirthdayTypeExtension;
use SumoCoders\FrameworkCoreBundle\EventListener\ResponseSecurer;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->bind('$projectDir', '%kernel.project_dir%')

        /*
         * Services
         */
        ->set('framework.fallbacks', Fallbacks::class)
            ->args([
                param('fallbacks')
            ])

        /*
         * Menu
         */
        ->set('framework.menu_builder', MenuBuilder::class)
            ->args([
                service('knp_menu.factory'),
                service('event_dispatcher')
            ])
            ->tag('knp_menu.menu_builder', ['method' => 'createMainMenu', 'alias' => 'side_menu'])

        /*
         * Forms
         */
        ->set('framework.date_type_extension', DateTypeExtension::class)
            ->tag('form.type_extension', ['extended_type' => DateType::class])

        ->set('framework.time_type_extension', TimeTypeExtension::class)
            ->tag('form.type_extension', ['extended_type' => TimeType::class])

        ->set('framework.date_time_type_extension', DateTimeTypeExtension::class)
            ->tag('form.type_extension', ['extended_type' => DateTimeType::class])

        ->set('framework.birthday_type_extension', BirthdayTypeExtension::class)
            ->tag('form.type_extension', ['extended_type' => BirthdayType::class])

        ->set('framework.collection_type_extension', CollectionTypeExtension::class)
            ->tag('form.type_extension', ['extended_type' => CollectionType::class])

        ->set('framework.image_type', ImageType::class)
            ->tag('form.type', ['alias' => 'image'])

        ->set('framework.file_type', FileType::class)
            ->tag('form.type', ['alias' => 'sumoFile'])

        /*
         * Secure headers
         */
        ->set('framework.response_securer', ResponseSecurer::class)
            ->args([
                param('kernel.debug')
            ])
            ->tag('kernel.event_listener', ['event' => 'kernel.response', 'method' => 'onKernelResponse'])

        /*
         * Twig extensions
         */
        ->set('framework.framework_extension', FrameworkExtension::class)
            ->tag('twig.extension')

        ->set('framework.paginator_extension', PaginatorExtension::class)
            ->tag('twig.extension')

        ->set('framework.paginator_runtime', PaginatorRuntime::class)
            ->tag('twig.runtime')

        /*
         * Breadcrumbs
         */
        ->set('framework.breadcrumb_trail', BreadcrumbTrail::class)
        ->alias(BreadcrumbTrail::class, 'framework.breadcrumb_trail')

        ->set('framework.breadcrumb_listener', BreadcrumbListener::class)
            ->tag('kernel.event_listener', ['event' => 'kernel.controller', 'method' => 'onKernelController', 'priority' => -1])

        /*
         * Commands
         */
        ->set(TranslateCommand::class)
            ->tag('console.command');
};
