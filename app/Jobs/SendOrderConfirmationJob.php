<?php

namespace App\Jobs;

use App\Models\Order;
use App\Notifications\OrderConfirmationNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendOrderConfirmationJob implements ShouldQueue
{
    use Queueable;

    protected Order $order;

    /**
     * Create a new job instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->order->user->notify(new OrderConfirmationNotification($this->order));
    }
}
