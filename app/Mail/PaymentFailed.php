<?php

namespace App\Mail;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PaymentFailed extends Mailable
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
            ->subject('Problema con tu pago — NeuroCarta.ai®')
            ->view('emails.payment-failed');
    }
}
