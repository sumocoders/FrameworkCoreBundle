# Sending mails

Sending mails is a trivial part of an application, but is something that in 
most cases costing a big amount of time.

We use the standard way of sending mails in Symfony, which uses SwiftMailer.

In addition a `MessageFactory` has been added, which will enable you to create 
messages that can be send with the `mailer`-service.

There a some public methods you can use:

* `createHtmlMessage` which enables you to create an message that will contain HTML
    If you don't provide a plainText alternative it will be generated from the provided HTML.
    The content will be wrapped in the default template.
    
* `createPlainTextMessage`, which enables you to send just plain-text emails.

There are some other public methods which are exposed and can help you:

* `wrapInTemplate`, which will wrap the provided content in a nice looking mail-template.

## Basic example

```php
// ...
/** @var /SumoCoders\FrameworkCoreBundle\Mail\MessageFactory/MessageFactory $messageFactory */
$messageFactory = $this->get('framework.message_factory');

// create a simple message
$message = $messageFactory->createHtmlMessage(
    'the subject',
    '<p>foo bar</p>'
);

// set some extra properties, just like you would do with a normal \Swift_Message
$message->setTo(
    $this->getParameter('mailer.default_to_email')
);

// send it
$this->get('mailer')->send($message);
```
