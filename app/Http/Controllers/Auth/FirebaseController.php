<?php

namespace App\Http\Controllers\Auth;

/*
|--------------------------------------------------------------------------
| Firebase Auth Controller
|--------------------------------------------------------------------------
|
| Handles validating Firebase ID Tokens, logging users in, and completing
| client profiles for onboarding.
|
*/

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class FirebaseController extends Controller
{
    /**
     * Handle the Firebase ID Token login/registration.
     */
    public function handleFirebaseLogin(Request $request): RedirectResponse
    {
        $request->validate([
            'idToken' => 'required|string',
        ]);

        $idToken = $request->input('idToken');
        $apiKey = config('services.firebase.api_key');

        if (empty($apiKey)) {
            return redirect()->route('login')
                ->withErrors(['email' => 'La configuración de Firebase no está completa en el servidor.']);
        }

        try {
            // Verificar el token con la API REST oficial de Firebase Auth
            $response = Http::post("https://identitytoolkit.googleapis.com/v1/accounts:lookup?key={$apiKey}", [
                'idToken' => $idToken,
            ]);

            if ($response->failed() || empty($response->json()['users'])) {
                return redirect()->route('login')
                    ->withErrors(['email' => 'Token de Firebase no válido o expirado.']);
            }

            $firebaseUser = $response->json()['users'][0];
            $firebaseUid = $firebaseUser['localId'];
            $email = $firebaseUser['email'];
            $name = $firebaseUser['displayName'] ?? explode('@', $email)[0];

            // 1. Buscar usuario por firebase_uid
            $user = User::where('firebase_uid', $firebaseUid)->first();

            if ($user) {
                // Usuario ya registrado con Firebase, iniciar sesión
                Auth::login($user, true);
            } else {
                // 2. Si no existe por firebase_uid, buscar por email para vincular cuentas
                $user = User::where('email', $email)->first();

                if ($user) {
                    $user->update([
                        'firebase_uid' => $firebaseUid,
                    ]);
                    Auth::login($user, true);
                } else {
                    // 3. Registrar un nuevo usuario (exclusivamente con rol cliente)
                    $user = User::create([
                        'name' => $name,
                        'email' => $email,
                        'firebase_uid' => $firebaseUid,
                        'password' => Hash::make(Str::random(24)),
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

        } catch (Exception $e) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Hubo un problema al autenticar con Firebase. Inténtalo de nuevo.']);
        }
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
