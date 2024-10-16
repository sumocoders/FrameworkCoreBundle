# Stimulus

For out javascript needs we use Symfony UX with Stimulus. See the Symfony site for information https://symfony.com/bundles/StimulusBundle/current/index.html or the stimulus documentation for more information: https://stimulus.hotwired.dev/handbook/introduction

We have a few default components that can be used in your project:

## Clipboard

Je kan deze op twee manieren gebruiken:

1. Met een aparte knop

Via `data-controller="clipboard"` geef je aan dat dit een stimulus controller moet starten.
Via `data-clipboard-target="source"` geef je aan welke tekst je wil kopiëren.
Op de knop dat je wil gebruiken om de source te kopiëren zet je: `data-action="clipboard#copy"`.
Met `data-clipboard-success-content-value` kan je de tekst van de knop tijdelijk veranderd na het kopiëren.

```html
<div data-controller="clipboard" data-clipboard-success-content-value="{{ 'Text copied!'|trans }}">
    <input type="text" value="Click the button to copy me!" data-clipboard-target="source" disabled />
    <button type="button" data-action="clipboard#copy" data-clipboard-target="button">Copy to clipboard</button>
</div>
```

2. Rechtstreeks op tekst

Het enige verschil is dat je het event `data-action="click->clipboard#copy"` direct toevoegt op de tekst.

```html
<div data-controller="clipboard">
  <span data-clipboard-target="source" data-action="click->clipboard#copy">This text will be copied on click!</span>
</div>
```

Iets kopiëren naar het clipboard toont een toast notification. Om deze tekst in te stellen gebruik je
`data-clipboard-success-message-value` op het element met je `data-controller="clipboard"`.

```html
<div data-controller="clipboard" data-clipboard-success-message-value="{{ 'Text copied!'|trans }}">
<span data-clipboard-target="source" data-action="click->clipboard#copy">This text will be copied on click!</span>
</div>
```


## Toast

Toast notifications via flash messages direct uit controllers worden direct getoond. Maar je kan ook via javascript
een toast laten verschijnen:

```javascript
import addToast from 'sumocoders/addToast'

addToast('This is a toast message', 'info')
```

## Tooltip

Om tooltips toe te voegen gebruik je `data-controller="tooltip"` met de default werking van bootstrap:
`data-bs-title="Text here"`. Zie https://getbootstrap.com/docs/5.3/components/tooltips/#overview voor meer
informatie.

```html
  <span data-controller="tooltip" data-bs-title="Default tooltip">
    <i class="fa fa-exclamation-triangle"></i>
  </span>
  <i class="fa fa-info-circle" data-controller="tooltip" data-bs-title="Little secret tip"></i>
```

## Popover

Om popovers toe te voegen gebruik je `data-controller="popover"` met de default werking van bootstrap via
`data-bs-title="Title here"` en `data-bs-content="Content here"`. Zie
https://getbootstrap.com/docs/5.3/components/popovers/#overview voor meer informatie.

```html
<button type="button" class="btn btn-outline-primary" data-controller="popover" data-bs-trigger="hover" data-bs-title="Foo" data-bs-content="Content goes here.">Hover me</button>
```

## Autocomplete

Om autocomplete te gebruiken, kan je werken via Symfony autocomplete-ux, zie
https://symfony.com/bundles/ux-autocomplete/current/index.html voor meer informatie.

Om dit te gebruiken in uw form type, kan je `autocomplete` op true zetten.

```php
    'autocomplete' => true,
```

## Tabs

Tabs werken bijna volledig via de standaard bootstrap tabs, zie
https://getbootstrap.com/docs/5.3/components/navs-tabs/#javascript-behavior voor meer informatie.

Als je `data-controller="tabs" data-action="shown.bs.tab->tabs#addAnchorToUrl"` aan je tabs container toevoegd, zal
er history push gedaan worden en zal de url veranderen met de anchor van de tab. Bij het reloaden van de pagina zal de
juiste tab geopend worden.

```html
  <ul class="nav nav-tabs" id="myTab" role="tablist" data-controller="tabs" data-action="shown.bs.tab->tabs#addAnchorToUrl">
    <li class="nav-item" role="presentation">
      <button class="nav-link active" id="home-tab" data-bs-toggle="tab" data-bs-target="#home-tab-pane" type="button" role="tab" aria-controls="home-tab-pane" aria-selected="true">Home</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile-tab-pane" type="button" role="tab" aria-controls="profile-tab-pane" aria-selected="false">Profile</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="contact-tab" data-bs-toggle="tab" data-bs-target="#contact-tab-pane" type="button" role="tab" aria-controls="contact-tab-pane" aria-selected="false">Contact</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="disabled-tab" data-bs-toggle="tab" data-bs-target="#disabled-tab-pane" type="button" role="tab" aria-controls="disabled-tab-pane" aria-selected="false" disabled>Disabled</button>
    </li>
  </ul>
  <div class="tab-content" id="myTabContent">
    <div class="tab-pane fade show active" id="home-tab-pane" role="tabpanel" aria-labelledby="home-tab" tabindex="0">HOME</div>
    <div class="tab-pane fade" id="profile-tab-pane" role="tabpanel" aria-labelledby="profile-tab" tabindex="0">PROFILE</div>
    <div class="tab-pane fade" id="contact-tab-pane" role="tabpanel" aria-labelledby="contact-tab" tabindex="0">CONTACT</div>
    <div class="tab-pane fade" id="disabled-tab-pane" role="tabpanel" aria-labelledby="disabled-tab" tabindex="0">DISABLED</div>
  </div>
```

## Password Strength Checker

Dit werkt automatisch vanaf je `RepeatedPasswordStrengthType` gebruikt in je form type.

```php
        $builder->add(
            'password',
            RepeatedPasswordStrengthType::class
        );
```

## DateTime pickers

Dit werkt automatisch als je DateType, DateTimeType of TimeType gebruikt in je form type.

## Sidebar

Dit werkt automatisch.

## Form collection

Dit werkt automatisch.

## Scroll to top

Dit werkt automatisch.
