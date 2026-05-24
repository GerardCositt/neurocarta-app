<?php

namespace App\Mail;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PlanChanged extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public Subscription $subscription;
    public string $oldPlanCode;
    public string $oldBillingInterval;

    public function __construct(User $user, Subscription $subscription, string $oldPlanCode, string $oldBillingInterval)
    {
        $this->user               = $user;
        $this->subscription       = $subscription;
        $this->oldPlanCode        = $oldPlanCode;
        $this->oldBillingInterval = $oldBillingInterval;
    }

    public function build(): static
    {
        return $this
            ->subject('Tu plan en NeuroCarta.ai® ha cambiado')
            ->view('emails.plan-changed');
    }
}
