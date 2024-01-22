The `Title` attribute is a custom attribute used in the framework. It is used to set the title of a page dynamically based on the controller method that is being executed. Here's a step-by-step guide on how to use it:

1. Import the `Title` attribute at the top of your controller file:

```php
use SumoCoders\FrameworkCoreBundle\Attribute\Title;
```

2. Apply the `Title` attribute to a controller method. The `Title` attribute takes a string as its first argument, which is the title you want to set for the page when this method is executed.

```php
#[Title('My Page Title')]
public function myMethod()
{
    // Your code here
}
```

3. If you want the title to be extended with the parent's title, you can pass a second argument to the `Title` attribute. This argument should be an array with a `name` key that corresponds to the route name of the parent.

```php
#[Title('My Page Title', ['name' => 'parent_route'])]
public function myMethod()
{
    // Your code here
}
```

4. If you want to prevent the title from being extended with the parent's title, you can pass a third argument to the `Title` attribute. This argument should be a boolean that indicates whether the title should be extended (`true`) or not (`false`).

```php
#[Title('My Page Title', ['name' => 'parent_route'], false)]
public function myMethod()
{
    // Your code here
}
```

5. The `Title` attribute can also handle dynamic titles. If you want to include a parameter in the title, you can do so by including it in curly braces `{}` in the title string. The parameter should be available in the request attributes.

```php
#[Title('My Page Title for {id}')]
public function myMethod($id)
{
    // Your code here
}
```

6. The `TitleListener` class will automatically handle the `Title` attribute. It listens to the kernel controller event, fetches the `Title` attribute from the controller method being executed, and sets the page title accordingly.

Remember to clear the Symfony cache after adding or changing attributes, as Symfony compiles and caches the attributes when the cache is built. You can clear the cache by running `bin/console cache:clear` in your terminal.
