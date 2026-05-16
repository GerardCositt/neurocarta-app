<?php

namespace App\Mail;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PaymentSucceeded extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public Subscription $subscription;

    public function __construct(User $user, Subscription $subscription)
    {
        $this->user = $user;
        $this->subscription = $subscription;
    }

    public function build(): static
    {
        return $this
            ->subject('Renovación confirmada — NeuroCarta.ai®')
            ->view('emails.payment-succeeded');
    }
}
