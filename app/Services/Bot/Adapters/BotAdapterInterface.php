<?php

namespace App\Services\Bot\Adapters;

use Illuminate\Http\Request;

interface BotAdapterInterface
{
    /**
     * Handle incoming webhook request, parse it, execute command, and reply.
     *
     * @param Request $request
     * @return mixed
     */
    public function handle(Request $request): mixed;
}
