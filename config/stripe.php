<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Stripe Secret Key
    |--------------------------------------------------------------------------
    | Used for server-side API calls (creating Checkout Sessions, retrieving
    | Subscriptions, verifying webhooks). Never expose this key client-side.
    */
    'secret' => env('STRIPE_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Stripe Webhook Signing Secret
    |--------------------------------------------------------------------------
    | Used to verify that webhook events were sent by Stripe and not a third
    | party. Obtain from the Stripe Dashboard > Webhooks > signing secret.
    */
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Stripe Price IDs
    |--------------------------------------------------------------------------
    | One Price ID per plan/interval combination. Create these in the Stripe
    | Dashboard (Products > Add product) and copy the price_xxx IDs here.
    | Use test IDs (price_test_...) in staging and live IDs in production.
    */
    'prices' => [
        'basico' => [
            'monthly' => env('STRIPE_PRICE_BASICO_MONTHLY'),
            'annual'  => env('STRIPE_PRICE_BASICO_ANNUAL'),
        ],
        'pro' => [
            'monthly' => env('STRIPE_PRICE_PRO_MONTHLY'),
            'annual'  => env('STRIPE_PRICE_PRO_ANNUAL'),
        ],
        'premium' => [
            'monthly' => env('STRIPE_PRICE_PREMIUM_MONTHLY'),
            'annual'  => env('STRIPE_PRICE_PREMIUM_ANNUAL'),
        ],
    ],

];
