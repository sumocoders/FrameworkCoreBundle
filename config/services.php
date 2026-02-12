<?php

declare(strict_types=1);

use SumoCoders\FrameworkCoreBundle\Command\Maintenance\CreatePrForOutdatedDependenciesCommand;
use SumoCoders\FrameworkCoreBundle\Command\TranslateCommand;
use SumoCoders\FrameworkCoreBundle\DoctrineListener\DoctrineAuditListener;
use SumoCoders\FrameworkCoreBundle\EventListener\BreadcrumbListener;
use SumoCoders\FrameworkCoreBundle\EventListener\ResponseSecurer;
use SumoCoders\FrameworkCoreBundle\EventListener\TitleListener;
use SumoCoders\FrameworkCoreBundle\Form\Extension\TogglePasswordTypeExtension;
use SumoCoders\FrameworkCoreBundle\Form\Type\BelgiumPostCodeType;
use SumoCoders\FrameworkCoreBundle\Form\Type\ImageType;
use SumoCoders\FrameworkCoreBundle\Form\Type\FileType;
use SumoCoders\FrameworkCoreBundle\Form\Extension\BirthdayTypeExtension;
use SumoCoders\FrameworkCoreBundle\Form\Extension\CollectionTypeExtension;
use SumoCoders\FrameworkCoreBundle\Form\Extension\DateTimeTypeExtension;
use SumoCoders\FrameworkCoreBundle\Form\Extension\DateTypeExtension;
use SumoCoders\FrameworkCoreBundle\Form\Extension\TimeTypeExtension;
use SumoCoders\FrameworkCoreBundle\Logger\AuditLogger;
use SumoCoders\FrameworkCoreBundle\Menu\MenuBuilder;
use SumoCoders\FrameworkCoreBundle\Service\BreadcrumbTrail;
use SumoCoders\FrameworkCoreBundle\Service\Fallbacks;
use SumoCoders\FrameworkCoreBundle\Service\PageTitle;
use SumoCoders\FrameworkCoreBundle\Service\Security\NonceGenerator;
use SumoCoders\FrameworkCoreBundle\Twig\ContentExtension;
use SumoCoders\FrameworkCoreBundle\Twig\FrameworkExtension;
use SumoCoders\FrameworkCoreBundle\Twig\PaginatorExtension;
use SumoCoders\FrameworkCoreBundle\Twig\PaginatorRuntime;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container->parameters()
        ->set('fallbacks', []);

    $container->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()

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
        ->args([
            service('translator')
        ])
        ->tag('form.type_extension', ['extended_type' => CollectionType::class])

        ->set('framework.toggle_password_type_extension', TogglePasswordTypeExtension::class)
        ->args([
            service('translator')
        ])
        ->tag('form.type_extension', ['extended_type' => PasswordType::class])

        ->set('framework.image_type', ImageType::class)
        ->tag('form.type', ['alias' => 'image'])

        ->set('framework.file_type', FileType::class)
        ->tag('form.type', ['alias' => 'sumoFile'])

        ->set('framework.file_type', BelgiumPostCodeType::class)
        ->tag('form.type', ['alias' => 'sumoBelgiumPostCode'])

        /*
         * Twig extensions
         */
        ->set('framework.framework_extension', FrameworkExtension::class)
        ->tag('twig.attribute_extension')

        ->set('framework.paginator_extension', PaginatorExtension::class)
        ->tag('twig.attribute_extension')

        ->set('framework.content_extension', ContentExtension::class)
        ->tag('twig.attribute_extension')

        /*
         * Breadcrumbs
         */
        ->set('framework.breadcrumb_trail', BreadcrumbTrail::class)
        ->alias(BreadcrumbTrail::class, 'framework.breadcrumb_trail')

        ->set('framework.breadcrumb_listener', BreadcrumbListener::class)
        ->tag(
            'kernel.event_listener',
            [
                'event' => 'kernel.controller',
                'method' => 'onKernelController',
                'priority' => -1
            ]
        )

        ->set('framework.title_listener', TitleListener::class)
        ->args([
            service('framework.page_title'),
            service('framework.fallbacks'),
            service('router')
        ])
        ->tag(
            'kernel.event_listener',
            [
                'event' => 'kernel.controller',
                'method' => 'onKernelController',
                'priority' => -1
            ]
        )

        /*
         * Page title
         */
        ->set('framework.page_title', PageTitle::class)
        ->args([
            service('framework.breadcrumb_trail'),
            service('framework.fallbacks'),
            service('translator')
        ])
        ->alias(PageTitle::class, 'framework.page_title')

        /*
         * Nelmio Nonce Generator
         */
        ->set(NonceGenerator::class)
        ->decorate('nelmio_security.nonce_generator')
        ->args([
            service('.inner')
        ])

        /*
         * Commands
         */
        ->set(TranslateCommand::class)
        ->tag('console.command')
        ->set(CreatePrForOutdatedDependenciesCommand::class)
        ->tag('console.command')

        ->set(DoctrineAuditListener::class)
        ->set(AuditLogger::class);
};
