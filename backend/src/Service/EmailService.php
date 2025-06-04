<?php
namespace Camagru\Service;

use Camagru\Core\Logger;
use Camagru\Exception\ApiException;

class EmailService
{
    private string $fromEmail;
    private string $fromName;
    private string $replyTo;

    public function __construct(string $fromEmail, string $fromName, string $replyTo = '')
    {
        $this->fromEmail = $fromEmail;
        $this->fromName = $fromName;
        $this->replyTo = $replyTo ?: $fromEmail;
    }

    /**
     * Send email method.
     *
     * @param string $to      — receiver's e-mail
     * @param string $subject — mail subject
     * @param string $body    — mail body, plain-text
     *
     * @throws ApiException failed to send email
     */
    public function send(string $to, string $subject, string $body): void
    {
        $headers = [];
        $headers[] = sprintf('From: %s <%s>', $this->fromName, $this->fromEmail);
        $headers[] = sprintf('Reply-To: %s', $this->replyTo);
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: text/plain; charset=UTF-8';
        $headersString = implode("\r\n", $headers);

        Logger::error($to ."\n"  . $subject . "\n" . $body . "\n" . $headersString);

        $sent = @mail($to, $subject, $body, $headersString);
        if ($sent === false) {
            throw new ApiException(
                'Failed to send email to address ' . $to,
                500
            );
        }
    }

    /**
     * Send email with register confirmation link.
     *
     * @param string $to         — receiver e-mail
     * @param string $username
     * @param string $confirmUrl — confirmation link (example: http://site/confirm?token=...)
     *
     * @throws ApiException sending error
     */

    public function sendConfirmation(string $to, string $username, string $confirmUrl): void
    {
        $subject = 'Verify your account on Camagru';
        $body    = "Hello, {$username}!\r\n\r\n"
        . "Thank you for registering on our site.\r\n"
        . "To confirm your account, please follow the link:\r\n\r\n"
        . "{$confirmUrl}\r\n\r\n"
        . "If you have not registered on Camagru, simply ignore this letter.\r\n\r\n"
        . "Best regards,\r\nCamagru Team";
        $this->send($to, $subject, $body);
    }
}