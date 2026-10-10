<?php

namespace App\Mail\Transport;

use App\Services\GmailApiService;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\MessageConverter;

/**
 * Transport Laravel Mail -> Gmail API, dipakai via MAIL_MAILER=gmail_api.
 * Kegagalan dilempar sebagai GmailSendException, tidak ditelan diam-diam.
 */
class GmailApiTransport extends AbstractTransport
{
    public function __construct(private readonly GmailApiService $gmail)
    {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        $mime = MessageConverter::toEmail($message->getOriginalMessage())->toString();

        $this->gmail->sendRawMime($mime);
    }

    public function __toString(): string
    {
        return 'gmail_api';
    }
}
