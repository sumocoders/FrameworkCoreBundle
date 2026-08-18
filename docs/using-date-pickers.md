# Date and time pickers

The bundle uses [Flatpickr](https://flatpickr.js.org/) for date and time inputs. Flatpickr activates automatically on
any field using these Symfony form types — no extra configuration required:

- `DateType`
- `TimeType`
- `DateTimeType`
- `BirthdayType`

## Prerequisites

The `widget` option must be `single_text` (the bundle default). Do not set `html5: true`.

## Usage

```php
<?php

use Symfony\Component\Form\Extension\Core\Type\DateType;

$builder->add('date', DateType::class, [
    'data' => new \DateTime(),
]);
```

## Options

The bundle adds two convenience options to the date/time types:

| Option         | Type           | Default        | Description                                    |
|----------------|----------------|----------------|------------------------------------------------|
| `minimum_date` | `string\|null` | `null`         | Earliest selectable date, formatted as `d/m/Y` |
| `maximum_date` | `string\|null` | `null`         | Latest selectable date, formatted as `d/m/Y`   |
| `format`       | `string`       | `'dd/MM/yyyy'` | Display format (ICU date format, same as Symfony's `format` option). Only the bundle's default patterns (`dd/MM/yyyy`, `HH:mm`, `dd/MM/yyyy HH:mm`) are mapped to a Flatpickr `data-date-format` |

```php
$builder->add('date', DateType::class, [
    'data'         => new \DateTime(),
    'minimum_date' => (new \DateTimeImmutable('last week'))->format('d/m/Y'),
    'maximum_date' => (new \DateTimeImmutable('next week'))->format('d/m/Y'),
]);
```

## Passing additional Flatpickr options

Pass any [Flatpickr option](https://flatpickr.js.org/options/) as a `data-*` attribute, converting camelCase to
kebab-case:

- `minDate` → `data-min-date`
- `showMonths` → `data-show-months`
```php
$builder->add('date', DateType::class, [
    'data'         => new \DateTime(),
    'minimum_date' => (new \DateTimeImmutable('last week'))->format('d/m/Y'),
    'maximum_date' => (new \DateTimeImmutable('next week'))->format('d/m/Y'),
    'attr'         => [
        'data-min-date'   => '01/01/1993',
        'data-show-months' => 2,
    ],
]);
```

## Troubleshooting

- **Picker does not open**: verify `widget` is not set to `choice` and `html5` is not `true`
- **Date format mismatch on submit**: the `format` option controls the display format; the form submits the underlying
  ISO value regardless of display format
- **`minimum_date`/`maximum_date` ignored**: these options must be a string in `d/m/Y` format matching the `format`
  option
