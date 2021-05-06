<?php

namespace SumoCoders\FrameworkCoreBundle\Tests\Mail;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SumoCoders\FrameworkCoreBundle\Mail\MessageFactory;
use Swift_Message;
use Twig\Environment;

class MessageFactoryTest extends TestCase
{
    /**
     * @var MessageFactory
     */
    protected $messageFactory;

    protected function setUp(): void
    {
        $temp = tempnam(sys_get_temp_dir(), 'message_factory_test');
        $this->messageFactory = new MessageFactory(
            $this->getTemplatingMock(),
            'mails/default_email.html.twig',
            $temp
        );
    }

    /**
     * @inherit
     */
    protected function tearDown(): void
    {
        $this->messageFactory = null;
    }

    /**
     * @return MockObject
     */
    protected function getTemplatingMock()
    {
        $templating = $this->createMock(Environment::class);
        $templating
            ->method('render')
            ->willReturn('<html><head></head><body><p>And I, le content</p></body></html>');

        return $templating;
    }

    public function testTheDefaultsShouldBeEmptyWhenNotSet()
    {
        $message = $this->messageFactory->createDefaultMessage();

        $this->assertInstanceOf(Swift_Message::class, $message);

        $this->assertEmpty($message->getFrom());
        $this->assertNull($message->getReplyTo());
        $this->assertEmpty($message->getTo());
    }

    public function testTheDefaultsShouldBeCorrectWhenSet()
    {
        $this->messageFactory->setDefaultSender('from@example.com', 'John From');
        $this->messageFactory->setDefaultReplyTo('reply-to@example.com', 'John Reply To');
        $this->messageFactory->setDefaultTo('to@example.com', 'John To');

        $message = $this->messageFactory->createDefaultMessage();

        $this->assertInstanceOf('\Swift_Message', $message);

        $this->assertEquals(['from@example.com' => 'John From'], $message->getFrom());
        $this->assertEquals(['reply-to@example.com' => 'John Reply To'], $message->getReplyTo());
        $this->assertEquals(['to@example.com' => 'John To'], $message->getTo());
    }

    public function testCreationOfPlainTextMessage()
    {
        $subject = 'I am le subject';
        $content = 'And I, le content';

        $message = $this->messageFactory->createPlainTextMessage(
            $subject,
            $content
        );

        $this->assertInstanceOf('\Swift_Message', $message);
        $this->assertEquals($subject, $message->getSubject());
        $this->assertEquals($content, $message->getBody());
        $this->assertEmpty($message->getChildren());
    }

    public function testCreationOfHtmlMessageWithAutomaticPlainText()
    {
        $subject = 'I am le subject';
        $content = '<p>And I, le content</p>';
        $expectedResult = <<<EOF
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/REC-html40/loose.dtd">
<html>
<head></head>
<body><p>And I, le content</p></body>
</html>
EOF;

        $message = $this->messageFactory->createHtmlMessage(
            $subject,
            $content
        );

        $this->assertInstanceOf('\Swift_Message', $message);
        $this->assertEquals($subject, $message->getSubject());
        $this->assertEquals($expectedResult, $message->getBody());

        $children = $message->getChildren();
        $this->assertCount(1, $children);
        $this->assertEquals('text/plain', $children[0]->getContentType());
        $this->assertEquals(
            $this->messageFactory->convertToPlainText($content),
            $children[0]->getBody()
        );
    }

    public function testTagsShouldBeStripped()
    {
        $this->assertEquals(
            'a paragraph',
            $this->messageFactory->convertToPlainText(
                '<p>a paragraph</p>'
            )
        );
        $this->assertEquals(
            'a paragraph',
            $this->messageFactory->convertToPlainText(
                '<p>
                  a paragraph
                 </p>'
            )
        );
    }
}
