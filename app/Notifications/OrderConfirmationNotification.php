<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderConfirmationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Order $order;

    /**
     * Create a new notification instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Order Confirmation - Order #' . $this->order->id)
            ->greeting('Hello ' . $this->order->user->name . '!')
            ->line('Thank you for your order! Your order has been confirmed.')
            ->line('Order Details:')
            ->line('Order ID: #' . $this->order->id)
            ->line('Total Amount: $' . number_format($this->order->total_amount, 2))
            ->line('Status: ' . ucfirst($this->order->status))
            ->line('Order Date: ' . $this->order->created_at->format('M d, Y H:i'))
            ->action('View Order Details', url('/orders/' . $this->order->id))
            ->line('We will send you another email when your order ships.')
            ->line('Thank you for shopping with us!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'total_amount' => $this->order->total_amount,
            'status' => $this->order->status,
        ];
    }
}
