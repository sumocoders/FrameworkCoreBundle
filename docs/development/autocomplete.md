# Autocomplete

The framework core makes use of `symfony/ux-autocomplete` to provide autocomplete functionality.

## Usage

To enable autocomplete functionality, you need to add the `autocomplete` parameter to your form field. All other options are optional.

```php
class AnyForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('food', EntityType::class, [
                'class' => Food::class,
                'placeholder' => 'What should we eat?',
                'autocomplete' => true,
            ])

            ->add('portionSize', ChoiceType::class, [
                'choices' => [
                    'Choose a portion size' => '',
                    'small' => 's',
                    'medium' => 'm',
                    'large' => 'l',
                    'extra large' => 'xl',
                    'all you can eat' => '∞',
                ],
                'autocomplete' => true,
            ])
        ;
    }
}
```

## Using Ajax

If you want to use ajax to fetch the autocomplete options, you need to create a new form type with the `AsEntityAutocompleteFieldz` attribute.

```php
use Symfony\Component\Security\Core\Security;
use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;
use Symfony\UX\Autocomplete\Form\ParentEntityAutocompleteType;

#[AsEntityAutocompleteField]
class FoodAutocompleteField extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'class' => Food::class,
            'placeholder' => 'What should we eat?',

            // choose which fields to use in the search
            // if not passed, *all* fields are used
            //'searchable_fields' => ['name'],

            // if the autocomplete endpoint needs to be secured
            //'security' => 'ROLE_FOOD_ADMIN',

            // ... any other normal EntityType options
            // e.g. query_builder, choice_label
        ]);
    }

    public function getParent(): string
    {
        return ParentEntityAutocompleteType::class;
    }
}
```
