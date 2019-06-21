<?php

namespace SumoCoders\FrameworkCoreBundle\Mail;

use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;
use Twig\Environment;

final class MessageFactory
{
    /**
     * @var array|string
     */
    private $sender = [];

    /**
     * @var array|string
     */
    private $replyTo = [];

    /**
     * @var array|string
     */
    private $to = [];

    /**
     * @var Environment
     */
    private $template;

    /**
     * @var string
     */
    private $templatePath;

    /**
     * @var string
     */
    private $cssPath;

    /**
     * MessageFactory constructor.
     *
     * @param Environment $template
     * @param string          $templatePath
     * @param string          $cssPath
     */
    public function __construct(Environment $template, $templatePath, $cssPath)
    {
        $this->template = $template;
        $this->templatePath = $templatePath;
        $this->cssPath = $cssPath;
    }

    /**
     * Set the default sender
     *
     * @param string      $email
     * @param string|null $name
     */
    public function setDefaultSender($email, $name = null)
    {
        if ($name !== null) {
            $this->sender = [$email => $name];
        } else {
            $this->sender = $email;
        }
    }

    /**
     * Set the default reply-to
     *
     * @param string      $email
     * @param string|null $name
     */
    public function setDefaultReplyTo($email, $name = null)
    {
        if ($name !== null) {
            $this->replyTo = [$email => $name];
        } else {
            $this->replyTo = $email;
        }
    }

    /**
     * Set the default to
     *
     * @param string      $email
     * @param string|null $name
     */
    public function setDefaultTo($email, $name = null)
    {
        if ($name !== null) {
            $this->to = [$email => $name];
        } else {
            $this->to = $email;
        }
    }

    /**
     * Create a message
     *
     * @param string|null $subject
     * @param string|null $html
     * @param string|null $alternative
     * @return \Swift_Message
     */
    private function createMessage($subject = null, $html = null, $alternative = null)
    {
        $message = $this->createDefaultMessage();

        if ($subject != '') {
            $message->setSubject($subject);
        }

        // only plain text
        if ($html == '' && $alternative != '') {
            $message->setBody($alternative, 'text/plain');

            return $message;
        }

        // html mail
        if ($alternative === null) {
            $alternative = $this->convertToPlainText($html);
        }
        $message->setBody(
            $this->wrapInTemplate($html),
            'text/html'
        );
        $message->addPart($alternative, 'text/plain');

        return $message;
    }

    /**
     * Create a HTML message
     *
     * If no alternative is provided it will be generated automatically.
     * This is just an alias for createMessage
     *
     * @param string|null $subject
     * @param string|null $html
     * @param string|null $plainText
     * @return \Swift_Message
     */
    public function createHtmlMessage($subject = null, $html = null, $plainText = null)
    {
        return $this->createMessage($subject, $html, $plainText);
    }

    /**
     * Create a plain text message
     *
     * @param string|null $subject
     * @param string|null $body
     * @return \Swift_Message
     */
    public function createPlainTextMessage($subject = null, $body = null)
    {
        return $this->createMessage($subject, null, $body);
    }

    /**
     * @return \Swift_Message
     */
    public function createDefaultMessage()
    {
        $message = \Swift_Message::newInstance();

        if (!empty($this->sender)) {
            $message->setFrom($this->sender);
        }

        if (!empty($this->replyTo)) {
            $message->setReplyTo($this->replyTo);
        }

        if (!empty($this->to)) {
            $message->setTo($this->to);
        }

        return $message;
    }

    /**
     * Wrap the given content in a nice default email template
     *
     * @param string $content
     * @return string
     */
    public function wrapInTemplate($content)
    {
        $css = file_get_contents($this->cssPath);
        $html = $this->template->render(
            $this->templatePath,
            [
                'content' => $content,
                'css' => $css,
            ]
        );

        $cssToInlineStyles = new CssToInlineStyles();

        return $cssToInlineStyles->convert(
            $html,
            $css
        );
    }

    /**
     * Convert the given content from HTML to Plain text
     *
     * @param string $content
     * @return string
     */
    public function convertToPlainText($content)
    {
        $content = preg_replace('/\r\n/', PHP_EOL, $content);
        $content = preg_replace('/\r/', PHP_EOL, $content);
        $content = preg_replace("/\t/", '', $content);

        // remove the style- and head-tags and all their contents
        $content = preg_replace('|\<style.*\>(.*\n*)\</style\>|isU', '', $content);
        $content = preg_replace('|\<head.*\>(.*\n*)\</head\>|isU', '', $content);

        // replace images with their alternative content
        $content = preg_replace('|\<img[^>]*alt="(.*)".*/\>|isU', '$1', $content);

        // replace links with the inner html of the link with the url between ()
        $content = preg_replace('|<a.*href="(.*)".*>(.*)</a>|isU', '$2 ($1)', $content);

        // strip HTML tags and preserve paragraphs
        $content = strip_tags($content, '<p><div>');

        // remove multiple spaced with a single one
        $content = preg_replace('/\n\s/', PHP_EOL, $content);
        $content = preg_replace('/\n{2,}/', PHP_EOL, $content);

        // for each div, paragraph end we want an additional linebreak at the end
        $content = preg_replace('|<div>|', '', $content);
        $content = preg_replace('|</div>|', PHP_EOL, $content);
        $content = preg_replace('|<p>|', '', $content);
        $content = preg_replace('|</p>|', PHP_EOL, $content);

        $content = trim($content);
        $content = strip_tags($content);
        $content = html_entity_decode($content);

        return $content;
    }
}
