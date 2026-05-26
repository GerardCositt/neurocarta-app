<?php

namespace App\Observers;

use App\Mail\SubscriptionActivated;
use App\Models\Subscription;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SubscriptionObserver
{
    public function updated(Subscription $subscription): void
    {
        // Skip — Stripe webhook handles emails for subscriptions with a Stripe ID
        if ($subscription->stripe_subscription_id) {
            return;
        }

        $newStatus = $subscription->status;
        $oldStatus = $subscription->getOriginal('status');

        $isNowActive = in_array($newStatus, ['active', 'trialing'], true);
        $wasInactive = ! in_array($oldStatus, ['active', 'trialing'], true);

        if ($isNowActive && $wasInactive) {
            $this->sendToAccountUsers($subscription);
        }
    }

    public function created(Subscription $subscription): void
    {
        if ($subscription->stripe_subscription_id) {
            return;
        }

        if (in_array($subscription->status, ['active', 'trialing'], true)) {
            $this->sendToAccountUsers($subscription);
        }
    }

    private function sendToAccountUsers(Subscription $subscription): void
    {
        try {
            $users = $subscription->account?->users ?? collect();
            foreach ($users as $user) {
                Mail::to($user->email)->send(new SubscriptionActivated($user, $subscription));
            }
        } catch (\Throwable $e) {
            Log::error('SubscriptionObserver: email send failed — ' . $e->getMessage(), [
                'subscription_id' => $subscription->id,
            ]);
        }
    }
}
