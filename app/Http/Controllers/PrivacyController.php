<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class PrivacyController extends Controller
{
    public function show()
    {
        return view('settings.privacy');
    }

    public function export(Request $request)
    {
        $user = $request->user();

        $accounts = $user->accounts()->with([
            'restaurants.products.allergens',
            'restaurants.categories',
            'subscriptions',
        ])->get();

        $data = [
            'exported_at' => now()->toIso8601String(),
            'user' => [
                'name'       => $user->name,
                'email'      => $user->email,
                'phone'      => $user->phone,
                'locale'     => $user->locale,
                'created_at' => $user->created_at?->toIso8601String(),
            ],
            'accounts' => $accounts->map(function ($account) {
                return [
                    'name'          => $account->name,
                    'created_at'    => $account->created_at?->toIso8601String(),
                    'subscriptions' => $account->subscriptions->map(fn ($s) => [
                        'plan_code'            => $s->plan_code,
                        'status'               => $s->status,
                        'billing_interval'     => $s->billing_interval,
                        'current_period_end'   => $s->current_period_end_at?->toIso8601String(),
                        'created_at'           => $s->created_at?->toIso8601String(),
                    ]),
                    'restaurants' => $account->restaurants->map(function ($r) {
                        return [
                            'name'       => $r->name,
                            'subdomain'  => $r->subdomain,
                            'ai_credits' => $r->ai_credits,
                            'categories' => $r->categories->map(fn ($c) => [
                                'name'   => $c->name,
                                'hidden' => (bool) $c->hidden,
                            ]),
                            'products' => $r->products->map(fn ($p) => [
                                'name'        => $p->name,
                                'description' => $p->description,
                                'price'       => $p->price,
                                'category'    => $p->category?->name,
                                'allergens'   => $p->allergens->pluck('name'),
                                'hidden'      => (bool) $p->hidden,
                                'created_at'  => $p->created_at?->toIso8601String(),
                            ]),
                        ];
                    }),
                ];
            }),
        ];

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $filename = 'neurocarta-datos-' . now()->format('Y-m-d') . '.json';

        return response($json, 200, [
            'Content-Type'        => 'application/json',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function destroy(Request $request)
    {
        $request->validate([
            'password' => ['required', 'string'],
        ], [
            'password.required' => 'Introduce tu contraseña para confirmar.',
        ]);

        $user = $request->user();

        if (! Hash::check($request->password, $user->password)) {
            return back()->withErrors(['password' => 'La contraseña no es correcta.']);
        }

        Log::info('AccountDeletion: user requested account deletion.', ['user_id' => $user->id]);

        DB::transaction(function () use ($user) {
            $accounts = $user->accounts()->with('users', 'restaurants')->get();

            foreach ($accounts as $account) {
                $isLastUser = $account->users->count() === 1;

                if ($isLastUser) {
                    // Delete all restaurant data (cascade via DB or manual)
                    foreach ($account->restaurants as $restaurant) {
                        $restaurant->products()->each(fn ($p) => $p->allergens()->detach());
                        $restaurant->products()->delete();
                        $restaurant->categories()->delete();
                        $restaurant->pairings()->delete();
                        $restaurant->advices()->delete();
                        $restaurant->settings()->delete();
                        $restaurant->delete();
                    }

                    $account->subscriptions()->delete();
                    $account->users()->detach();
                    $account->delete();
                } else {
                    // Other users remain — just detach this user from the account
                    $account->users()->detach($user->id);
                }
            }

            $user->delete();
        });

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('status', 'Tu cuenta ha sido eliminada.');
    }
}
