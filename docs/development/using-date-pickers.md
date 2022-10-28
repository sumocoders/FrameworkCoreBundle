# Using date pickers

While the native date and time pickers have improved over the years, we still had some edge cases to cover, so we chose [Flatpickr](https://flatpickr.js.org/) to provide a beautiful, no-dependency datetime picker in the framework.

You don't have to pass any option or attribute, the picker will always show when you use any of the following types:

* DateType
* TimeType
* DateTimeType
* BirthdayType

```php
<?php

$builder
    ->add(
        'date',
        DateType::class,
        [
            'data' => new DateTime(),
            'minimum_date' => (new \DateTimeImmutable('last week'))->format('d/m/Y'),
            'maximum_date' => (new \DateTimeImmutable('next week'))->format('d/m/Y'),
        ]
    );
```

For the picker to work, the `widget` option has to be set to `single_text`. This is the default, so as long as you leave it, you should be fine.

## Options

There are two option helpers to set date ranges:
* `minimum_date`: takes a formatted string. Will be set as the `min` value on the Flatpickr instance.
* `maximum_date`: takes a formatted string. Will be set as the `max` value on the Flatpickr instance.

All other options you'll have to set yourself. You can find the full list of options in the [Flatpickr documentation](https://flatpickr.js.org/options/).

The easiest way to pass options is to set them as data attributes on the form field. To do this, you'll have to transform the option name from camel to snake case. 

Example:
* `minDate` becomes `min-date`
* `showMonths` becomes `show-months`

All Flatpickr data atributes must be prefixed with `date`. A full, working example would be:

```php
<?php

$builder
    ->add(
        'date',
        DateType::class,
        [
            'data' => new DateTime(),
            'minimum_date' => (new \DateTimeImmutable('last week'))->format('d/m/Y'),
            'maximum_date' => (new \DateTimeImmutable('next week'))->format('d/m/Y'),
            'attr' [
                'data-date-min-date' => '01/01/1993',
                'data-date-show-months' => false,
            ]
        ]
    );
```