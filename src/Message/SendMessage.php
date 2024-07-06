<?php
declare(strict_types=1);

namespace App\Message;

class SendMessage
{
    public function __construct(
        public string $text,
    )
    {
    }
}