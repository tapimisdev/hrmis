<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PayrollBatchCompleted extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
     public function __construct(
        public $batch,
        public $status,
        public $exception = null
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    // 🧾 What gets stored in `notifications` table
    public function toDatabase($notifiable)
    {
        return [
            'batch_id' => $this->batch->id,
            'status' => $this->status,
            'error' => $this->exception?->getMessage(),
            'completed_at' => now(),
            'message' => $this->status === 'success'
                ? 'Payroll batch completed successfully.'
                : 'Payroll batch failed.',
        ];
    }
}
