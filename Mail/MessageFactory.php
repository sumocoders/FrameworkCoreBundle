<?php

namespace SumoCoders\FrameworkCoreBundle\Mail;

use Symfony\Component\Asset\Package;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;
use Twig\Environment;

final class MessageFactory
{
    private ?Address $sender;

    private ?Address $replyTo;

    private ?Address $to;

    private Environment $template;

    private Package $package;

    private string $publicFolderPath;

    private string $templatePath;

    private string $cssPath;

    public function __construct(
        Environment $template,
        Package $package,
        string $publicFolderPath,
        string $templatePath,
        string $cssPath
    ) {
        $this->template = $template;
        $this->package = $package;
        $this->publicFolderPath = $publicFolderPath;
        $this->templatePath = $templatePath;
        $this->cssPath = $cssPath;
    }

    public function setDefaultSender(string $email, string $name = null): void
    {
        if ($name !== null) {
            $this->sender = new Address($email, $name);

            return;
        }

        $this->sender = new Address($email);
    }

    public function setDefaultReplyTo(string $email, string $name = null): void
    {
        if ($name !== null) {
            $this->replyTo = new Address($email, $name);

            return;
        }

        $this->replyTo = new Address($email);
    }

    public function setDefaultTo(string $email, string $name = null): void
    {
        if ($name !== null) {
            $this->to = new Address($email, $name);

            return;
        }

        $this->to = new Address($email);
    }

    private function createMessage(string $subject = null, string $html = null, string $alternative = null): Email
    {
        $message = $this->createDefaultMessage();

        if ($subject !== '') {
            $message->subject($subject);
        }

        // only plain text
        if ((!is_string($html) || $html === '') && (is_string($alternative) && $alternative !== '')) {
            $message->text($alternative);

            return $message;
        }

        if (!is_string($alternative) || $alternative === '') {
            $alternative = $this->convertToPlainText($html);
        }

        $message->html($this->wrapInTemplate($html));
        $message->text($alternative);

        return $message;
    }

    public function createHtmlMessage(string $subject = null, string $html = null, string $plainText = null): Email
    {
        return $this->createMessage($subject, $html, $plainText);
    }

    public function createPlainTextMessage(string $subject = null, string $body = null): Email
    {
        return $this->createMessage($subject, null, $body);
    }

    public function createDefaultMessage(): Email
    {
        $message = new Email();

        if (!empty($this->sender)) {
            $message->from($this->sender);
        }

        if (!empty($this->replyTo)) {
            $message->replyTo($this->replyTo);
        }

        if (!empty($this->to)) {
            $message->to($this->to);
        }

        return $message;
    }

    public function wrapInTemplate(string $content): string
    {
        $css = file_get_contents(
            $this->publicFolderPath . $this->package->getUrl($this->cssPath)
        );
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

    public function convertToPlainText(string $content): string
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
