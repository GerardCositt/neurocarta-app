<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TrialEndingReminder extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public int $daysLeft;

    public function __construct(User $user, int $daysLeft)
    {
        $this->user = $user;
        $this->daysLeft = $daysLeft;
    }

    public function build(): static
    {
        $subject = $this->daysLeft > 0
            ? "Tu prueba gratuita termina en {$this->daysLeft} días"
            : 'Tu prueba gratuita termina hoy';

        return $this
            ->subject($subject)
            ->view('emails.trial-ending');
    }
}
