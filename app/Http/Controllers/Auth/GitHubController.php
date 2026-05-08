<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UsuarioResource;
use App\Mail\BienvenidaMail;
use App\Services\OAuthUserService;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class GitHubController extends Controller
{
    public function __construct(private readonly OAuthUserService $oauthUserService) {}

    public function redirect()
    {
        return Socialite::driver('github')
            ->setHttpClient(new Client(['verify' => ! app()->isLocal()]))
            ->stateless()
            ->scopes(['read:user', 'user:email'])
            ->redirect();
    }

    public function callback()
    {
        try {
            $githubUser = Socialite::driver('github')
                ->setHttpClient(new Client(['verify' => ! app()->isLocal()]))
                ->stateless()
                ->user();
            $existingUser = $githubUser->getEmail()
                ? \App\Models\Usuario::where('email', $githubUser->getEmail())->first()
                : \App\Models\Usuario::findByProvider('github', (string) $githubUser->getId());

            $user = $this->oauthUserService->resolveOrCreateUser(
                'github',
                (string) $githubUser->getId(),
                $githubUser->getEmail(),
                $githubUser->getName() ?? $githubUser->getNickname() ?? 'Usuario',
                '',
                $githubUser->getAvatar()
            );

            if (! $existingUser) {
                try {
                    Mail::to($user->email)->send(new BienvenidaMail($user));
                } catch (\Throwable $mailError) {
                    Log::warning('No se pudo enviar correo de bienvenida OAuth GitHub: '.$mailError->getMessage());
                }
            }

            $token = $this->oauthUserService->issueToken($user);

            return $this->callbackView('github-callback', [
                'token' => $token,
                'user' => (new UsuarioResource($user->fresh()))->resolve(request()),
            ]);
        } catch (\Exception $e) {
            Log::error('Error en callback OAuth GitHub: '.$e->getMessage(), ['exception' => $e]);

            return $this->callbackView('github-callback', ['error' => $e->getMessage()]);
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
