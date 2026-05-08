<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UsuarioResource;
use App\Mail\BienvenidaMail;
use App\Services\OAuthUserService;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    public function __construct(private readonly OAuthUserService $oauthUserService) {}

    public function redirect()
    {
        return Socialite::driver('google')
            ->setHttpClient(new Client(['verify' => ! app()->isLocal()]))
            ->stateless()
            ->scopes(['openid', 'profile', 'email'])
            ->redirect();
    }

    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')
                ->setHttpClient(new Client(['verify' => ! app()->isLocal()]))
                ->stateless()
                ->user();
            $existingUser = $googleUser->getEmail()
                ? \App\Models\Usuario::where('email', $googleUser->getEmail())->first()
                : \App\Models\Usuario::findByProvider('google', (string) $googleUser->getId());

            $user = $this->oauthUserService->resolveOrCreateUser(
                'google',
                (string) $googleUser->getId(),
                $googleUser->getEmail(),
                $googleUser->user['given_name'] ?? $googleUser->getName() ?? 'Usuario',
                $googleUser->user['family_name'] ?? '',
                $googleUser->getAvatar()
            );

            if (! $existingUser) {
                try {
                    Mail::to($user->email)->send(new BienvenidaMail($user));
                } catch (\Throwable $mailError) {
                    Log::warning('No se pudo enviar correo de bienvenida OAuth Google: '.$mailError->getMessage());
                }
            }

            $token = $this->oauthUserService->issueToken($user);

            return $this->callbackView('google-callback', [
                'token' => $token,
                'user'  => (new UsuarioResource($user->fresh()))->resolve(request()),
            ]);

        } catch (\Exception $e) {
            Log::error('Error en callback OAuth Google: '.$e->getMessage(), ['exception' => $e]);

            return $this->callbackView('google-callback', ['error' => $e->getMessage()]);
        }
    }

    private function callbackView(string $view, array $data)
    {
        return response()
            ->view($view, $data)
            ->header('Cross-Origin-Opener-Policy', 'same-origin-allow-popups')
            ->header('Cross-Origin-Embedder-Policy', 'unsafe-none');
    }
}
