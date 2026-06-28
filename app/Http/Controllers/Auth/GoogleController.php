<?php

namespace App\Http\Controllers\Auth;

/*
|--------------------------------------------------------------------------
| Google SSO Controller
|--------------------------------------------------------------------------
|
| Handles redirecting users to Google and receiving their OAuth2 tokens
| and profile information back to authenticate or register clients.
|
*/

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    /**
     * Redirect the user to the Google authentication page.
     */
    public function redirectToGoogle(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Obtain the user information from Google and log them in.
     */
    public function handleGoogleCallback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Exception $e) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Hubo un problema al autenticar con Google. Inténtalo de nuevo.']);
        }

        // 1. Buscar usuario por google_id
        $user = User::where('google_id', $googleUser->getId())->first();

        if ($user) {
            // Usuario ya registrado con Google SSO, iniciar sesión
            Auth::login($user, true);
        } else {
            // 2. Si no existe por google_id, buscar por email
            $user = User::where('email', $googleUser->getEmail())->first();

            if ($user) {
                // Vincular cuenta existente actualizando el google_id
                $user->update([
                    'google_id' => $googleUser->getId(),
                ]);
                Auth::login($user, true);
            } else {
                // 3. Registrar un nuevo usuario (exclusivamente con rol cliente)
                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'password' => Hash::make(Str::random(24)), // Contraseña aleatoria y segura
                ]);

                // Asignar rol cliente
                $user->syncRoles(['cliente']);

                Auth::login($user, true);
            }
        }

        // 4. Redirigir al onboarding si el usuario es cliente y le faltan datos requeridos (CI o teléfono)
        if ($user->hasRole('cliente') && (empty($user->ci) || empty($user->phone))) {
            return redirect()->route('profile.complete');
        }

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Show the form to complete the user's profile details.
     */
    public function showCompleteProfileForm(): Response
    {
        return Inertia::render('Auth/CompleteProfile', [
            'user' => Auth::user(),
        ]);
    }

    /**
     * Store the user's completed profile details in storage.
     */
    public function storeCompleteProfile(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'ci' => ['required', 'string', 'max:20', 'unique:users,ci,'.$user->id],
            'phone' => ['required', 'string', 'max:20'],
        ]);

        $user->update([
            'name' => $request->name,
            'ci' => $request->ci,
            'phone' => $request->phone,
        ]);

        return redirect()->route('dashboard')->with('success', 'Perfil completado correctamente.');
    }
}
