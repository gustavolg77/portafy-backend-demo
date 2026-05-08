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

class LinkedInController extends Controller
{
    public function __construct(private readonly OAuthUserService $oauthUserService) {}

    public function redirect()
    {
        return Socialite::driver('linkedin-openid')
            ->setHttpClient(new Client(['verify' => ! app()->isLocal()]))
            ->stateless()
            ->scopes(['openid', 'profile', 'email'])
            ->redirect();
    }

    public function callback()
    {
        try {
            $linkedinUser = Socialite::driver('linkedin-openid')
                ->setHttpClient(new Client(['verify' => ! app()->isLocal()]))
                ->stateless()
                ->user();
            $existingUser = $linkedinUser->getEmail()
                ? \App\Models\Usuario::where('email', $linkedinUser->getEmail())->first()
                : \App\Models\Usuario::findByProvider('linkedin', (string) $linkedinUser->getId());

            $user = $this->oauthUserService->resolveOrCreateUser(
                'linkedin',
                (string) $linkedinUser->getId(),
                $linkedinUser->getEmail(),
                $linkedinUser->user['given_name'] ?? $linkedinUser->getName() ?? 'Usuario',
                $linkedinUser->user['family_name'] ?? '',
                $linkedinUser->getAvatar()
            );

            if (! $existingUser) {
                try {
                    Mail::to($user->email)->send(new BienvenidaMail($user));
                } catch (\Throwable $mailError) {
                    Log::warning('No se pudo enviar correo de bienvenida OAuth LinkedIn: '.$mailError->getMessage());
                }
            }

            $token = $this->oauthUserService->issueToken($user);

            return $this->callbackView('linkedin-callback', [
                'token' => $token,
                'user' => (new UsuarioResource($user->fresh()))->resolve(request()),
            ]);
        } catch (\Exception $e) {
            Log::error('Error en callback OAuth LinkedIn: '.$e->getMessage(), ['exception' => $e]);

            return $this->callbackView('linkedin-callback', ['error' => $e->getMessage()]);
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
