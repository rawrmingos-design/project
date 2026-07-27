<?php

namespace App\Services\Bot;

class BotCommandParser
{
    /**
     * Parse raw text message into a structured command and arguments.
     *
     * @param string $message
     * @return array{command: string|null, args: array<int, string>}
     */
    public function parse(string $message): array
    {
        $message = trim($message);
        if ($message === '') {
            return ['command' => null, 'args' => []];
        }

        // Split by spaces, treating multiple spaces as single space
        $parts = preg_split('/\s+/', $message);

        if (empty($parts)) {
            return ['command' => null, 'args' => []];
        }

        // The first part is the command, lowercased, and without leading slashes (e.g. /start -> start)
        $command = strtolower(ltrim(array_shift($parts), '/'));

        return [
            'command' => $command !== '' ? $command : null,
            'args' => $parts,
        ];
    }
}
