<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'locale',
    ];

    // Verification is handled by the WelcomeSetPassword email + SetPasswordController
    public function sendEmailVerificationNotification(): void {}

    /**
     * Admin de panel: flag en BD o email en FILAMENT_ADMIN_EMAIL / NEUROCARTA_DEMO_ADMIN_EMAILS.
     */
    public function hasPanelAdminAccess(): bool
    {
        if ((bool) $this->is_admin) {
            return true;
        }

        $emails = array_filter(array_unique(array_merge(
            array_filter([config('services.filament.admin_email')]),
            config('neurocarta.demo_admin_emails', [])
        )));

        return $this->email && in_array($this->email, $emails, true);
    }

    public function canAccessFilament(): bool
    {
        return $this->hasPanelAdminAccess();
    }

    /**
     * Cuenta demo/admin: acceso al panel sin pantalla de verificación de email.
     */
    public function hasVerifiedEmail(): bool
    {
        if ($this->hasPanelAdminAccess()) {
            return true;
        }

        return parent::hasVerifiedEmail();
    }

    public function accounts()
    {
        return $this->belongsToMany(Account::class, 'account_user')->withTimestamps();
    }

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_admin'          => 'boolean',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'profile_photo_url',
    ];
}
