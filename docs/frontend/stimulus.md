# Stimulus

For our javascript needs we use Symfony UX with Stimulus. See the Symfony site for
information https://symfony.com/bundles/StimulusBundle/current/index.html or the stimulus documentation for more
information: https://stimulus.hotwired.dev/handbook/introduction

We have a few default components that can be used in your project:

## Clipboard

This can be implemented two different ways:

1. With separate button

With `data-controller="clipboard"` you set up the clipboard controller.
With `data-clipboard-target="source"` you specify the text you want to be copied.
To use the button to copy the source, you add `data-action="clipboard#copy"` to the button.
With `data-clipboard-success-content-value` you can temporarily change the button text after copying.

```html

<div data-controller="clipboard" data-clipboard-success-content-value="{{ 'Text copied!'|trans }}">
  <input type="text" value="Click the button to copy me!" data-clipboard-target="source" disabled/>
  <button type="button" data-action="clipboard#copy" data-clipboard-target="button">Copy to clipboard</button>
</div>
```

2. Directly on text

The only difference is that you add the event `data-action="click->clipboard#copy"` directly on the text.

```html

<div data-controller="clipboard">
  <span data-clipboard-target="source" data-action="click->clipboard#copy">This text will be copied on click!</span>
</div>
```

Copying something will show a toast notification. To set this text you can use `data-clipboard-success-message-value`
on the element with your `data-controller="clipboard"`.

```html

<div data-controller="clipboard" data-clipboard-success-message-value="{{ 'Text copied!'|trans }}">
  <span data-clipboard-target="source" data-action="click->clipboard#copy">This text will be copied on click!</span>
</div>
```

## Toast

Toast notifications can be shown via flash messages directly from controllers. But you can also show a toast
through javascript:

```javascript
import addToast from 'sumocoders/addToast'

addToast('This is a toast message', 'info')
```

## Tooltip

To use tooltips, you can use `data-controller="tooltip"` with the default bootstrap behavior:
`data-bs-title="Text here"`. See https://getbootstrap.com/docs/5.3/components/tooltips/#overview for more information.

```html
  <span data-controller="tooltip" data-bs-title="Default tooltip">
    <i class="fa fa-exclamation-triangle"></i>
  </span>
<i class="fa fa-info-circle" data-controller="tooltip" data-bs-title="Little secret tip"></i>
```

## Popover

To use popovers, you can use `data-controller="popover"` with the default bootstrap behavior:
`data-bs-title="Title here"` and `data-bs-content="Content here"`. See
https://getbootstrap.com/docs/5.3/components/popovers/#overview for more information.

```html

<button type="button" class="btn btn-outline-primary" data-controller="popover" data-bs-trigger="hover"
        data-bs-title="Foo" data-bs-content="Content goes here.">Hover me
</button>
```

## Autocomplete

To use autocomplete, you can with Symfony autocomplete-ux, see
https://symfony.com/bundles/ux-autocomplete/current/index.html for more information.

To use this in your form type, you can set `autocomplete` to true.

```php
    'autocomplete' => true,
```

## Tabs

Tabs work almost entirely with the standard bootstrap tabs, see
https://getbootstrap.com/docs/5.3/components/navs-tabs/#javascript-behavior for more information.

If you add `data-controller="tabs" data-action="shown.bs.tab->tabs#addAnchorToUrl"` to your tabs container, there will
be a history push when a tab is changes. This will change the url with the anchor of the tab. When reloading the page,
the correct tab will be opened.

```html

<ul class="nav nav-tabs" id="myTab" role="tablist" data-controller="tabs"
    data-action="shown.bs.tab->tabs#addAnchorToUrl">
  <li class="nav-item" role="presentation">
    <button class="nav-link active" id="home-tab" data-bs-toggle="tab" data-bs-target="#home-tab-pane" type="button"
            role="tab" aria-controls="home-tab-pane" aria-selected="true">Home
    </button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile-tab-pane" type="button"
            role="tab" aria-controls="profile-tab-pane" aria-selected="false">Profile
    </button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="contact-tab" data-bs-toggle="tab" data-bs-target="#contact-tab-pane" type="button"
            role="tab" aria-controls="contact-tab-pane" aria-selected="false">Contact
    </button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="disabled-tab" data-bs-toggle="tab" data-bs-target="#disabled-tab-pane" type="button"
            role="tab" aria-controls="disabled-tab-pane" aria-selected="false" disabled>Disabled
    </button>
  </li>
</ul>
<div class="tab-content" id="myTabContent">
  <div class="tab-pane fade show active" id="home-tab-pane" role="tabpanel" aria-labelledby="home-tab" tabindex="0">
    HOME
  </div>
  <div class="tab-pane fade" id="profile-tab-pane" role="tabpanel" aria-labelledby="profile-tab" tabindex="0">PROFILE
  </div>
  <div class="tab-pane fade" id="contact-tab-pane" role="tabpanel" aria-labelledby="contact-tab" tabindex="0">CONTACT
  </div>
  <div class="tab-pane fade" id="disabled-tab-pane" role="tabpanel" aria-labelledby="disabled-tab" tabindex="0">
    DISABLED
  </div>
</div>
```

## Password Strength Checker

This works automatically when you use the `RepeatedPasswordStrengthType` in your form type.

```php
        $builder->add(
            'password',
            RepeatedPasswordStrengthType::class
        );
```

## Password Visibility Toggle

This works automatically when you use the `PasswordType` or `RepeatedPasswordStrengthType` in your form type.

It allows visitors to switch the type of password field to text and vice versa.

Options for customizing:

```php
    $builder->add('password', PasswordType::class, [
        // Turn off the visibility toggle
        'toggle' => false,

        // Disable custom form theme
        'use_toggle_form_theme' => false,

        // Customizing labels
        'hidden_label' => 'Masquer',
        'visible_label' => 'Afficher',
    ]);
```

## DateTime pickers

This works automatically when you use the `DateType`, `DateTimeType` or `TimeType` in your form type.

## Sidebar

This works automatically.

## Form collection

This works automatically.

## Scroll to top

This works automatically.

## Busy button spinner

After submitting a form, replace the submit button with a spinner.

Set the `data-controller='busy-submit'` on the form tag or in your form type:

```php
    $resolver->setDefaults([
        'attr' => ['data-controller' => 'busy-submit'],
    ]);
```


## Confirm modal

This controller allows you to show a confirmation modal before submitting a form or clicking a link.

For example when you have a form to delete an entity:

```twig
    <div
        {{ stimulus_controller(
            'confirm',
            {
                confirmationMessage: 'Are you sure you want to delete this {entity}?'|trans({entity: entity.title}),
                cancelButtonText: 'Cancel'|trans,
                confirmButtonText: 'Delete'|trans,
            }
        ) }}
    >
        {{ form(delete_form, { attr: { 'data-confirm-target': 'element' }}) }}
    </div>
```

Or an example with a link:

```twig
    <div
        {{ stimulus_controller(
            'confirm',
            {
                modalTitle: 'Open sumocoders.be?',
                confirmationMessage: 'Are you sure you want to open the SumoCoders website?',
                cancelButtonText: 'Cancel',
                confirmButtonText: 'Open SumoCoders website',
            }
        ) }}
    >
        <a
            href="https://www.sumocoders.be"
            {{ stimulus_target('confirm', 'element') }}
            class="btn btn-secondary"
        >
            Open sumocoders.be
        </a>
    </div>
```
