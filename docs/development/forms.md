# Forms

## Translations

The label for a field will be automatically translated. 

```php
    ...
        ->add(
            'username',
            TextType::class,
        )
    ...
```

Will result in

```html

<div class="form-group">
  <label for="xxx_form_username" class="form-label">
    Username<abbr title="this field is required">*</abbr>
  </label>
  <input type="text" id="xxx_form_username" name="xxx_form[username]" required="required" class="form-control">
</div>
```
Where `Username` is a translation. So if you want to translate the label, you can do it like this:

```yaml
Username: 'Enter your username'
```
