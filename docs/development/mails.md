# Sending mails

The framework provides a base template with proper CSS that you can use to quickly send good-looking emails to most email clients.

Simply extend your email template from the `@SumoCodersFrameworkCore/Mail/base.html.twig` template and place your own content inside the `{%block content %}` block.

The base template will place your content in a table-based layout, load and inline styles and return the end result.

To send the mail, you can use the default Symfony package: `symfony/mailer`. See the example below.

## Basic example

template.html.twig
```twig
{% extends '@SumoCodersFrameworkCore/Mail/base.html.twig' %}

{% block content %}
    <p>Hello {{ customer_name }},</p>
    
    <p>Some hardcoded content</p>
    
    <p>{{ 'Some translated content'|trans }}</p>
{% endblock %}
```

```php
<?php

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

public function __invoke(MailerInterface $mailer): void
{
    $email = (new TemplatedEmail())
        ->from(Address::create('Your application <no-reply@your.app>'))
        ->to(Address::create('John Doe <johndoe@gmail.com>'))
        ->subject('Your subject')
        ->htmlTemplate('template.html.twig')
        ->context([
            'customer_name' => 'John Doe'
        ]);

    $mailer->send($email);
}

```
