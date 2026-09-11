# CRUD

A standard CRUD in this bundle uses four invokable controllers, a shared DataTransferObject, a single form type, and
Symfony Messenger for mutations.

## Prerequisites

- Pagination in the repository (see [pagination.md](pagination.md))
- Button placement conventions (see [button-locations.md](button-locations.md))
- Ask whether audit logging is needed before adding `#[AuditTrail]` to the entity (see [audit-trail.md](audit-trail.md))
- Ask whether a menu item should be added after the CRUD is in place (see [menu.md](menu.md))

## File structure

```
src/
  Controller/
    Item/
      Admin/
        OverviewController.php
        CreateController.php
        UpdateController.php
        DeleteController.php
  DataTransferObject/
    Item/
      ItemDataTransferObject.php
  Exception/
    Item/
      ItemNotFoundException.php
  Message/
    Item/
      CreateItemMessage.php
      UpdateItemMessage.php
      DeleteItemMessage.php
  MessageHandler/
    Item/
      CreateItemMessageHandler.php
      UpdateItemMessageHandler.php
      DeleteItemMessageHandler.php
  Form/
    Item/
      ItemType.php
  Entity/
    Item.php
  Repository/
    ItemRepository.php
templates/
  item/
    index.html.twig
    create.html.twig
    update.html.twig
translations/
  messages+intl-icu.en.yaml
  messages+intl-icu.nl.yaml
tests/
  MessageHandler/
    Item/
      CreateItemMessageHandlerTest.php
      UpdateItemMessageHandlerTest.php
      DeleteItemMessageHandlerTest.php
  Controller/
    Item/
      Admin/
        OverviewControllerTest.php
        CreateControllerTest.php
        UpdateControllerTest.php
        DeleteControllerTest.php
```

## Entity

The constructor is the only way to create an entity. The `update()` method takes explicit arguments, not a
DataTransferObject.

```php
<?php

namespace App\Entity;

use App\Repository\ItemRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ItemRepository::class)]
class Item
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    public function __construct(
        #[ORM\Column]
        private string $name,
    ) {
    }

    public function update(string $name): void
    {
        $this->name = $name;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
```

## DataTransferObject

`ItemDataTransferObject` holds the form fields for both create and update. It is abstract: it is never
instantiated directly, only through `CreateItemMessage` and `UpdateItemMessage`. Declare fields as public
properties with their default values directly on the class, no constructor.

```php
<?php

namespace App\DataTransferObject;

use Symfony\Component\Validator\Constraints\NotBlank;

abstract class ItemDataTransferObject
{
    #[NotBlank]
    public string $name = '';
}
```

## Messages

Messages extend `ItemDataTransferObject`. `CreateItemMessage` extends it with no changes. `UpdateItemMessage`
accepts the entity in its constructor and assigns the inherited properties directly to pre-fill the form. The
form is initialised with the message, so `$form->getData()` returns the message directly and can be dispatched
without conversion.

```php
<?php

namespace App\Message\Item;

use App\DataTransferObject\ItemDataTransferObject;

final class CreateItemMessage extends ItemDataTransferObject {}
```

```php
<?php

namespace App\Message\Item;

use App\DataTransferObject\ItemDataTransferObject;
use App\Entity\Item;

final class UpdateItemMessage extends ItemDataTransferObject
{
    public readonly int $id;

    public function __construct(
        Item $item,
    ) {
        $this->id = $item->getId();
        $this->name = $item->getName();
    }
}
```

`DeleteItemMessage` accepts the entity but stores only the id.

```php
<?php

namespace App\Message\Item;

use App\Entity\Item;

final class DeleteItemMessage
{
    public readonly int $id;

    public function __construct(
        Item $item,
    ) {
        $this->id = $item->getId();
    }
}
```

## Exception

```php
<?php

namespace App\Exception\Item;

final class ItemNotFoundException extends \RuntimeException {}
```

## Form type

A single `ItemType` is used for both create and update.

```php
<?php

namespace App\Form\Item;

use App\DataTransferObject\ItemDataTransferObject;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @extends AbstractType<ItemDataTransferObject> */
final class ItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('name', TextType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => ItemDataTransferObject::class]);
    }
}
```

## Repository

`add()` and `remove()` accept an optional `$flush` parameter, defaulting to `true`. For an entity
that is already managed (e.g. fetched via `find()`), call `flush()` directly instead.

```php
<?php

namespace App\Repository;

use App\Entity\Item;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use SumoCoders\FrameworkCoreBundle\Pagination\Paginator;

class ItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Item::class);
    }

    public function getPaginated(): Paginator
    {
        return new Paginator(
            $this->createQueryBuilder('i')->orderBy('i.name', 'ASC')
        );
    }

    public function add(Item $item, bool $flush = true): void
    {
        $this->getEntityManager()->persist($item);
        if ($flush) {
            $this->flush();
        }
    }

    public function remove(Item $item, bool $flush = true): void
    {
        $this->getEntityManager()->remove($item);
        if ($flush) {
            $this->flush();
        }
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }
}
```

## Message handlers

```php
<?php

namespace App\MessageHandler\Item;

use App\Entity\Item;
use App\Message\Item\CreateItemMessage;
use App\Repository\ItemRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class CreateItemMessageHandler
{
    public function __construct(
        private readonly ItemRepository $repository,
    ) {
    }

    public function __invoke(CreateItemMessage $message): void
    {
        $this->repository->add(new Item($message->name));
    }
}
```

```php
<?php

namespace App\MessageHandler\Item;

use App\Entity\Item;
use App\Exception\Item\ItemNotFoundException;
use App\Message\Item\UpdateItemMessage;
use App\Repository\ItemRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class UpdateItemMessageHandler
{
    public function __construct(
        private readonly ItemRepository $repository,
    ) {
    }

    public function __invoke(UpdateItemMessage $message): void
    {
        $item = $this->repository->find($message->id);
        if (!$item instanceof Item) {
            throw new ItemNotFoundException();
        }

        $item->update($message->name);
        $this->repository->flush();
    }
}
```

```php
<?php

namespace App\MessageHandler\Item;

use App\Entity\Item;
use App\Exception\Item\ItemNotFoundException;
use App\Message\Item\DeleteItemMessage;
use App\Repository\ItemRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class DeleteItemMessageHandler
{
    public function __construct(
        private readonly ItemRepository $repository,
    ) {
    }

    public function __invoke(DeleteItemMessage $message): void
    {
        $item = $this->repository->find($message->id);
        if (!$item instanceof Item) {
            throw new ItemNotFoundException();
        }

        $this->repository->remove($item);
    }
}
```

## Controllers

Inject dependencies via the constructor. Use `$messageBus` as the variable name for `MessageBusInterface`. Place
`#[Route]` and `#[Breadcrumb]` attributes on the class, not on `__invoke`.

### OverviewController

```php
<?php

declare(strict_types=1);

namespace App\Controller\Item\Admin;

use App\Repository\ItemRepository;
use SumoCoders\FrameworkCoreBundle\Attribute\Breadcrumb;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/items', name: 'item_index')]
#[Breadcrumb('item.breadcrumb.index')]
final class OverviewController extends AbstractController
{
    public function __construct(
        private readonly ItemRepository $repository,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $items = $this->repository->getPaginated()
            ->paginate($request->query->getInt('page', 1));

        return $this->render('item/index.html.twig', ['items' => $items]);
    }
}
```

### CreateController

```php
<?php

declare(strict_types=1);

namespace App\Controller\Item\Admin;

use App\Form\Item\ItemType;
use App\Message\Item\CreateItemMessage;
use SumoCoders\FrameworkCoreBundle\Attribute\Breadcrumb;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/admin/items/create', name: 'item_create')]
#[Breadcrumb('item.breadcrumb.index', route: ['name' => 'item_index'])]
#[Breadcrumb('item.breadcrumb.create')]
final class CreateController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $form = $this->createForm(ItemType::class, new CreateItemMessage());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // @mago-expect analysis:mixed-argument
            $this->messageBus->dispatch($form->getData());

            $this->addFlash('success', $this->translator->trans('item.flash.created'));

            return $this->redirectToRoute('item_index');
        }

        return $this->render('item/create.html.twig', ['form' => $form]);
    }
}
```

### UpdateController

```php
<?php

declare(strict_types=1);

namespace App\Controller\Item\Admin;

use App\Entity\Item;
use App\Form\Item\ItemType;
use App\Message\Item\UpdateItemMessage;
use SumoCoders\FrameworkCoreBundle\Attribute\Breadcrumb;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/admin/items/{item}/update', name: 'item_update')]
#[Breadcrumb('item.breadcrumb.index', route: ['name' => 'item_index'])]
#[Breadcrumb('{item.name}')]
final class UpdateController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function __invoke(Request $request, Item $item): Response
    {
        $form = $this->createForm(ItemType::class, new UpdateItemMessage($item));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // @mago-expect analysis:mixed-argument
            $this->messageBus->dispatch($form->getData());

            $this->addFlash('success', $this->translator->trans('item.flash.updated'));

            return $this->redirectToRoute('item_index');
        }

        $deleteForm = $this->createFormBuilder()
            ->setAction($this->generateUrl('item_delete', ['item' => $item->getId()]))
            ->getForm();

        return $this->render('item/update.html.twig', [
            'form' => $form,
            'item' => $item,
            'delete_form' => $deleteForm,
        ]);
    }
}
```

### DeleteController

The delete form is validated to ensure the CSRF token is checked before dispatching.

```php
<?php

declare(strict_types=1);

namespace App\Controller\Item\Admin;

use App\Entity\Item;
use App\Message\Item\DeleteItemMessage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/admin/items/{item}/delete', name: 'item_delete', methods: ['POST'])]
final class DeleteController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function __invoke(Request $request, Item $item): Response
    {
        $deleteForm = $this->createFormBuilder()
            ->getForm();
        $deleteForm->handleRequest($request);

        if ($deleteForm->isSubmitted() && $deleteForm->isValid()) {
            $this->messageBus->dispatch(new DeleteItemMessage($item));

            $this->addFlash('success', $this->translator->trans('item.flash.deleted'));
        }

        return $this->redirectToRoute('item_index');
    }
}
```

## Templates

Page templates extend your application's `templates/base.html.twig`, which in turn extends
`@SumoCodersFrameworkCore/base.html.twig`. Page content goes in `{% block main %}`; the bundle
base defines no `content` block. The surrounding slots (`header_title`, `header_navigation`,
`header_actions_left`, `header_actions_right`) are described in
[button-locations.md](button-locations.md).

### index.html.twig

Use a table when the entity has multiple columns worth showing, otherwise one card per item.
Either way the content sits in a card. Tables keep the `card-body`: a list section usually
carries a title or intro text alongside the table, and `card-body` gives that room.

```twig
{% extends 'base.html.twig' %}

{% block header_navigation %}
    <a href="{{ path('item_create') }}" class="btn btn-primary">
        <i class="bi bi-plus"></i>
        {{ 'item.actions.create'|trans }}
    </a>
{% endblock %}

{% block main %}
    <div class="card mb-3">
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>{{ 'item.label.name'|trans }}</th>
                        <th class="text-end"></th>
                    </tr>
                </thead>
                <tbody>
                    {% for item in items %}
                        <tr>
                            <td>{{ item.name }}</td>
                            <td class="text-end">
                                <a href="{{ path('item_update', {item: item.id}) }}" class="btn btn-sm btn-secondary">
                                    <i class="bi bi-pencil"></i>
                                    {{ 'item.actions.update'|trans }}
                                </a>
                            </td>
                        </tr>
                    {% else %}
                        <tr>
                            <td colspan="2">
                                <div class="data-no-results">
                                    <img src="{{ asset('images/no-results.svg') }}" alt="">
                                    {{ 'item.no_results'|trans }}
                                </div>
                            </td>
                        </tr>
                    {% endfor %}
                </tbody>
            </table>

            {% if items.hasToPaginate %}
                <div class="d-flex justify-content-center">
                    {{ pagination(items) }}
                </div>
            {% endif %}
        </div>
    </div>
{% endblock %}
```

### create.html.twig

Add each form field separately with `form_row`.

```twig
{% extends 'base.html.twig' %}

{% block main %}
    <div class="card mb-3">
        <div class="card-body">
            {{ form_start(form) }}
                {{ form_row(form.name) }}
            {{ form_end(form) }}
        </div>
    </div>
{% endblock %}

{% block header_actions_right %}
    <button class="btn btn-primary" type="submit" form="{{ form.vars.id }}">
        <i class="bi bi-floppy-fill"></i>
        {{ 'item.actions.save'|trans }}
    </button>
{% endblock %}
```

### update.html.twig

The delete button uses the `confirm` Stimulus controller (see [stimulus.md](stimulus.md)) and is placed in
`header_actions_left` because it is a destructive action.

```twig
{% extends 'base.html.twig' %}

{% block main %}
    <div class="card mb-3">
        <div class="card-body">
            {{ form_start(form) }}
                {{ form_row(form.name) }}
            {{ form_end(form) }}
        </div>
    </div>
{% endblock %}

{% block header_actions_left %}
    <div
        {{ stimulus_controller(
            'confirm',
            {
                confirmationMessage: 'item.delete.confirm'|trans({item: item.name}),
                cancelButtonText: 'dialogs.buttons.cancel'|trans,
                confirmButtonText: 'item.actions.delete'|trans,
            },
        ) }}
    >
        {{ form_start(delete_form, {attr: {'data-confirm-target': 'element'}}) }}
            <button class="btn btn-danger" type="submit">
                <i class="bi bi-trash"></i>
                {{ 'item.actions.delete'|trans }}
            </button>
        {{ form_end(delete_form) }}
    </div>
{% endblock %}

{% block header_actions_right %}
    <button class="btn btn-primary" type="submit" form="{{ form.vars.id }}">
        <i class="bi bi-floppy-fill"></i>
        {{ 'item.actions.save'|trans }}
    </button>
{% endblock %}
```

## Testing

### Message handler tests

Handler tests are unit tests. Mock the repository and assert the correct method is called.

```php
<?php

namespace App\Tests\MessageHandler\Item;

use App\Entity\Item;
use App\Message\Item\CreateItemMessage;
use App\MessageHandler\Item\CreateItemMessageHandler;
use App\Repository\ItemRepository;
use PHPUnit\Framework\TestCase;

final class CreateItemMessageHandlerTest extends TestCase
{
    public function testItCreatesAnItem(): void
    {
        $repository = $this->createMock(ItemRepository::class);
        $repository->expects($this->once())
            ->method('add')
            ->with($this->isInstanceOf(Item::class));

        $message = new CreateItemMessage();
        $message->name = 'Test item';

        $handler = new CreateItemMessageHandler($repository);
        $handler($message);
    }
}
```

```php
<?php

namespace App\Tests\MessageHandler\Item;

use App\Entity\Item;
use App\Message\Item\UpdateItemMessage;
use App\MessageHandler\Item\UpdateItemMessageHandler;
use App\Repository\ItemRepository;
use PHPUnit\Framework\TestCase;

final class UpdateItemMessageHandlerTest extends TestCase
{
    public function testItUpdatesAnItem(): void
    {
        $item = new Item('Old name');

        $repository = $this->createMock(ItemRepository::class);
        $repository->method('find')->willReturn($item);
        $repository->expects($this->once())
            ->method('flush');

        $message = new UpdateItemMessage($item);
        $message->name = 'New name';

        $handler = new UpdateItemMessageHandler($repository);
        $handler($message);

        static::assertSame('New name', $item->getName());
    }
}
```

```php
<?php

namespace App\Tests\MessageHandler\Item;

use App\Entity\Item;
use App\Exception\Item\ItemNotFoundException;
use App\Message\Item\DeleteItemMessage;
use App\MessageHandler\Item\DeleteItemMessageHandler;
use App\Repository\ItemRepository;
use PHPUnit\Framework\TestCase;

final class DeleteItemMessageHandlerTest extends TestCase
{
    public function testItDeletesAnItem(): void
    {
        $item = new Item('Test item');

        $repository = $this->createMock(ItemRepository::class);
        $repository->method('find')->willReturn($item);
        $repository->expects($this->once())
            ->method('remove')
            ->with($item);

        $message = new DeleteItemMessage($item);

        $handler = new DeleteItemMessageHandler($repository);
        $handler($message);
    }

    public function testItThrowsWhenItemNotFound(): void
    {
        $repository = $this->createMock(ItemRepository::class);
        $repository->method('find')->willReturn(null);

        $item = new Item('Test item');
        $message = new DeleteItemMessage($item);

        $this->expectException(ItemNotFoundException::class);

        $handler = new DeleteItemMessageHandler($repository);
        $handler($message);
    }
}
```

### Controller tests

Controller tests are functional tests extending `WebTestCase`. They verify HTTP responses and redirects.

```php
<?php

namespace App\Tests\Controller\Item\Admin;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class OverviewControllerTest extends WebTestCase
{
    public function testItRendersTheIndex(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/items');

        static::assertResponseIsSuccessful();
    }
}
```

```php
<?php

namespace App\Tests\Controller\Item\Admin;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CreateControllerTest extends WebTestCase
{
    public function testItRendersTheCreateForm(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/items/create');

        static::assertResponseIsSuccessful();
    }

    public function testItCreatesAnItemAndRedirects(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/items/create');

        $client->submitForm('item.actions.save', [
            'item[name]' => 'Test item',
        ]);

        static::assertResponseRedirects('/admin/items');
    }
}
```

```php
<?php

namespace App\Tests\Controller\Item\Admin;

use App\Entity\Item;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class UpdateControllerTest extends WebTestCase
{
    public function testItRendersTheUpdateForm(): void
    {
        $client = static::createClient();

        $item = new Item('Original name');
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->persist($item);
        $em->flush();

        $client->request('GET', '/admin/items/' . $item->getId() . '/update');

        static::assertResponseIsSuccessful();
    }

    public function testItUpdatesAnItemAndRedirects(): void
    {
        $client = static::createClient();

        $item = new Item('Original name');
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->persist($item);
        $em->flush();

        $client->request('GET', '/admin/items/' . $item->getId() . '/update');

        $client->submitForm('item.actions.save', [
            'item[name]' => 'Updated name',
        ]);

        static::assertResponseRedirects('/admin/items');
    }
}
```

```php
<?php

namespace App\Tests\Controller\Item\Admin;

use App\Entity\Item;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class DeleteControllerTest extends WebTestCase
{
    public function testItDeletesAnItemAndRedirects(): void
    {
        $client = static::createClient();

        $item = new Item('To be deleted');
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->persist($item);
        $em->flush();

        $client->request('GET', '/admin/items/' . $item->getId() . '/update');
        $client->submitForm('item.actions.delete');

        static::assertResponseRedirects('/admin/items');
    }
}
```

## Translations

```yaml
# translations/messages+intl-icu.en.yaml
item:
  breadcrumb:
    index: 'Items'
    create: 'Add item'
  label:
    name: 'Name'
  flash:
    created: 'The item has been created.'
    updated: 'The item has been saved.'
    deleted: 'The item has been deleted.'
  actions:
    create: 'Add item'
    update: 'Update'
    save: 'Save'
    delete: 'Delete'
  delete:
    confirm: 'Are you sure you want to delete "{item}"?'
  no_results: 'No items found.'
```

```yaml
# translations/messages+intl-icu.nl.yaml
item:
  breadcrumb:
    index: 'Items'
    create: 'Item toevoegen'
  label:
    name: 'Naam'
  flash:
    created: 'Het item werd aangemaakt.'
    updated: 'Het item werd opgeslagen.'
    deleted: 'Het item werd verwijderd.'
  actions:
    create: 'Item toevoegen'
    update: 'Bewerken'
    save: 'Opslaan'
    delete: 'Verwijderen'
  delete:
    confirm: 'Ben je zeker dat je "{item}" wil verwijderen?'
  no_results: 'Geen items gevonden.'
```
