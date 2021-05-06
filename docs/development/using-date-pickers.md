# Using date pickers

Although Symfony provides a nice integration of selecting dates, we have 
extended the functionality a bit.

When passing the `datepicker`-option as true, a nice date picker will be shown 
in the form.

We can choose between following extensions

* DateType
* TimeType
* DateTimeType
* BirthdayType

```php
<?php
// ...

$form = $this->createFormBuilder()
            ->add(
                'date',
                DateType::class,
                [
                    'data' => new DateTime(),
                    'widget' => 'single_text',
                    'datepicker' => true,
                    'minimum_date' => DateTime::createFromFormat('Y/m/d', '2017/08/14'),
                    'maximum_date' => DateTime::createFromFormat('Y/m/d', '2017/08/30'),
                ]
            );
// ...
```

As you will see this will only work when the `widget`-option has the value 
`single_text` or `choice`.

## Date range

In most case you will have some constraints to the date, and this should be reflected in the UI. 
Passing the `minimum_date` and / or `maximum_date` limits the selectable dates.

## More examples

A more detailed example is available in the 
[FrameworkExampleBundle](https://github.com/sumocoders/FrameworkExampleBundle).
