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
        'is_admin',
    ];

    // Verification is handled by the WelcomeSetPassword email + SetPasswordController
    public function sendEmailVerificationNotification(): void {}

    // Accede al panel Filament si es admin por BD o por variable de entorno
    public function canAccessFilament(): bool
    {
        return (bool) $this->is_admin
            || $this->email === config('services.filament.admin_email');
    }

    /**
     * Cuenta demo/admin (test@test.com): acceso al panel sin pantalla de verificación de email.
     */
    public function hasVerifiedEmail(): bool
    {
        if ((bool) $this->is_admin) {
            return true;
        }

        $adminEmail = config('services.filament.admin_email');
        if ($adminEmail && $this->email === $adminEmail) {
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
