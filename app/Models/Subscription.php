<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'stripe_customer_id',
        'stripe_subscription_id',
        'plan_code',
        'status',
        'current_period_end_at',
        'trial_warning_day5_sent_at',
        'trial_warning_day7_sent_at',
    ];

    protected $casts = [
        'current_period_end_at'       => 'datetime',
        'trial_warning_day5_sent_at'  => 'datetime',
        'trial_warning_day7_sent_at'  => 'datetime',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function isActive(): bool
    {
        if (! in_array($this->status, ['active', 'trialing'], true)) {
            return false;
        }

        // Para trials, comprobar también que no haya vencido
        if ($this->status === 'trialing' && $this->current_period_end_at !== null) {
            return $this->current_period_end_at->isFuture();
        }

        return true;
    }

    public function isExpiredTrial(): bool
    {
        return $this->status === 'trialing'
            && $this->current_period_end_at !== null
            && $this->current_period_end_at->isPast();
    }
}

