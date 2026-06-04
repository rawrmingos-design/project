<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;
use App\Models\Deposit;

class DepositStatusNotification extends Notification implements ShouldBroadcast, ShouldQueue
{
    use Queueable;

    public $deposit;
    public $message;

    /**
     * Create a new notification instance.
     */
    public function __construct(Deposit $deposit, $message)
    {
        $this->deposit = $deposit;
        $this->message = $message;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'deposit',
            'title' => 'Deposit ' . ucfirst($this->deposit->status),
            'message' => $this->message,
            'deposit_id' => $this->deposit->id,
            'amount' => $this->deposit->amount,
        ];
    }
    
    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'data' => $this->toArray($notifiable)
        ]);
    }
}
