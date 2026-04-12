<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class WelcomeSetPassword extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public string $setPasswordUrl;

    public function __construct(User $user, string $setPasswordUrl)
    {
        $this->user = $user;
        $this->setPasswordUrl = $setPasswordUrl;
    }

    public function build(): static
    {
        return $this
            ->subject('Activa tu cuenta en NeuroCarta.ai®')
            ->view('emails.welcome-set-password');
    }
}
