<?php

namespace App\Services\Bot;

use InvalidArgumentException;

final class BotGatewayCapabilities
{
    public const SOURCE_WHATSAPP = 'whatsapp_gateway';

    public const SOURCE_TELEGRAM = 'telegram_gateway';

    private function __construct(private readonly string $source)
    {
    }

    public static function forSource(?string $source): self
    {
        $source = trim((string) $source);

        if ($source === '') {
            $source = self::SOURCE_WHATSAPP;
        }

        if (! in_array($source, [self::SOURCE_WHATSAPP, self::SOURCE_TELEGRAM], true)) {
            throw new InvalidArgumentException('Unsupported bot gateway source.');
        }

        return new self($source);
    }

    public function source(): string
    {
        return $this->source;
    }

    public function supports(string $capability): bool
    {
        return match ($capability) {
            'leaderboard', 'order_history', 'menu', 'help', 'cancel' => true,
            'deposit' => $this->source === self::SOURCE_WHATSAPP
                || ($this->source === self::SOURCE_TELEGRAM
                    && (bool) config('services.telegram-bot-api.deposit_enabled', false)),
            default => false,
        };
    }

    public function menuPageSize(): int
    {
        return $this->source === self::SOURCE_WHATSAPP ? 15 : 8;
    }
}
