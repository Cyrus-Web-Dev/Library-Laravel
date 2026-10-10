<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Gagal berkomunikasi dengan Gmail API. $userMessage aman ditampilkan ke pengguna;
 * detail teknis (respons Google) hanya ditulis ke storage/logs/laravel.log.
 */
class GmailSendException extends RuntimeException
{
    public function __construct(private readonly string $userMessage, ?string $technical = null, ?Throwable $previous = null)
    {
        parent::__construct($technical ?? $userMessage, 0, $previous);
    }

    public function userMessage(): string
    {
        return $this->userMessage;
    }
}
