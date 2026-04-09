<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserLocaleController extends Controller
{
    /**
     * Actualiza el idioma preferido del usuario del panel.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Request $request)
    {
        $allowed = config('app.admin_locales', ['es', 'en']);

        $validated = $request->validate([
            'locale' => 'required|string|in:' . implode(',', $allowed),
        ]);

        $request->user()->forceFill([
            'locale' => $validated['locale'],
        ])->save();

        return redirect()->back();
    }
}
