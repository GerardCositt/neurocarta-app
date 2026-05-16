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
        'stripe_price_id',
        'plan_code',
        'billing_interval',
        'status',
        'current_period_start_at',
        'current_period_end_at',
        'grace_period_ends_at',
        'canceled_at',
        'trial_warning_day5_sent_at',
        'trial_warning_day7_sent_at',
    ];

    protected $casts = [
        'current_period_start_at'     => 'datetime',
        'current_period_end_at'       => 'datetime',
        'grace_period_ends_at'        => 'datetime',
        'canceled_at'                 => 'datetime',
        'trial_warning_day5_sent_at'  => 'datetime',
        'trial_warning_day7_sent_at'  => 'datetime',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function isActive(): bool
    {
        return match($this->status) {
            'active'   => true,
            'trialing' => $this->current_period_end_at === null
                          || $this->current_period_end_at->isFuture(),
            'past_due' => $this->grace_period_ends_at !== null
                          && $this->grace_period_ends_at->isFuture(),
            default    => false,
        };
    }

    public function isExpiredTrial(): bool
    {
        return $this->status === 'trialing'
            && $this->current_period_end_at !== null
            && $this->current_period_end_at->isPast();
    }
}

