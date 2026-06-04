<?php

namespace App\Observers;

use App\Models\Deposit;
use App\Models\User;
use App\Notifications\DepositStatusNotification;

class DepositObserver
{
    /**
     * Handle the Deposit "created" event.
     */
    public function created(Deposit $deposit): void
    {
        $user = User::where('username', $deposit->username)->first();
        if ($user) {
            $user->notify(new DepositStatusNotification($deposit, 'New deposit request created for ' . number_format((float)$deposit->jumlah, 0, ',', '.')));
        }
    }

    /**
     * Handle the Deposit "updated" event.
     */
    public function updated(Deposit $deposit): void
    {
        if ($deposit->isDirty('status')) {
            $user = User::where('username', $deposit->username)->first();
            if ($user) {
                $statusMsg = $deposit->status === 'Success' ? 'has been successful' : 'is now ' . strtolower($deposit->status);
                $user->notify(new DepositStatusNotification($deposit, 'Your deposit of ' . number_format((float)$deposit->jumlah, 0, ',', '.') . ' ' . $statusMsg));
            }
        }
    }

    /**
     * Handle the Deposit "deleted" event.
     */
    public function deleted(Deposit $deposit): void
    {
        //
    }

    /**
     * Handle the Deposit "restored" event.
     */
    public function restored(Deposit $deposit): void
    {
        //
    }

    /**
     * Handle the Deposit "force deleted" event.
     */
    public function forceDeleted(Deposit $deposit): void
    {
        //
    }
}
