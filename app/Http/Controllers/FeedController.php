<?php

namespace App\Http\Controllers;

use App\Models\Emoji;
use App\Models\Experience;
use App\Models\Profile;
use App\Models\Publication;
use App\Models\PublicationComment;
use App\Models\PublicationDetail;
use App\Models\Reaction;
use App\Models\SavedPublication;
use App\Models\Proyecto;
use App\Services\PublicAssetUrlService;
use App\Support\OfficialSchema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class FeedController extends Controller
{
    private const COMMENT_MAX_LENGTH = 280;

    public function __construct(private readonly PublicAssetUrlService $assetUrlService) {}

    public function index(Request $request): JsonResponse
    {
        $limit = $this->boundedLimit($request);
        $viewerProfile = $request->user() ? OfficialSchema::ensureProfile($request->user()) : null;

        $publications = $this->baseFeedQuery($viewerProfile, includeComments: false)
            ->published()
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn(Publication $publication) => $this->toPost($publication, $viewerProfile))
            ->toArray();

        $offers = \App\Models\Offer::with('skills', 'profile')
            ->whereIn('state', ['open', 'visible'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn($offer) => $this->toOfferPost($offer))
            ->toArray();

        $feed = collect([...$publications, ...$offers])
            ->sortByDesc('createdAt')
            ->values()
            ->take($limit);

        return response()->json(['data' => $feed]);
    }

    private function toOfferPost(\App\Models\Offer $offer): array
    {
        $profile = $offer->profile;

        return [
            'id'            => 'offer-' . $offer->id_offer,
            'publicationId' => 'offer-' . $offer->id_offer,
            'type'          => 'oferta',
            'sourceType'    => 'offer',
            'offerId'       => $offer->id_offer,
            'author'        => [
                'id'     => $profile?->id_profile,
                'name'   => $profile?->company?->name ?? 'Empresa',
                'title'  => $profile?->company?->industry ?? 'Empresa',
                'avatar' => $profile?->company?->logo_url ?? '',
            ],
            'content'       => $offer->description ?? '',
            'title'         => $offer->title,
            'type_contrato' => $offer->type,
            'modalidad'     => $offer->modalidad,
            'ubicacion'     => $offer->ubicacion,
            'nivel'         => $offer->nivel,
            'area'          => $offer->area,
            'salary_min'    => $offer->salary_min,
            'salary_max'    => $offer->salary_max,
            'currency'      => $offer->currency,
            'show_salary'   => $offer->show_salary,
            'closed_at'     => $offer->closed_at?->toDateString(),
            'banner_url'    => $offer->banner_url,
            'tags'          => $offer->skills->pluck('name')->values()->all(),
            'likes'         => 0,
            'commentsCount' => 0,
            'saves'         => 0,
            'likedByMe'     => false,
            'savedByMe'     => false,
            'posted'        => $offer->created_at?->diffForHumans() ?? '',
            'createdAt'     => $offer->created_at?->toISOString(),
            'comments'      => [],
        ];
    }

    public function mine(Request $request): JsonResponse
    {
        $limit = $this->boundedLimit($request);
        $profile = OfficialSchema::ensureProfile($request->user());

        $publications = $this->baseFeedQuery($profile, includeComments: false)
            ->where('id_profile', $profile->getKey())
            ->published()
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        return response()->json([
            'data' => $publications->map(fn (Publication $publication) => $this->toPost($publication, $profile))->values(),
        ]);
    }

    public function saved(Request $request): JsonResponse
    {
        $limit = $this->boundedLimit($request);
        $profile = OfficialSchema::ensureProfile($request->user());

        $savedRows = SavedPublication::query()
            ->where('id_profile', $profile->getKey())
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        $publicationIds = $savedRows
            ->pluck('id_publication')
            ->filter()
            ->values();

        $publications = $this->baseFeedQuery($profile, includeComments: false)
            ->published()
            ->whereIn('id_publication', $publicationIds)
            ->get()
            ->keyBy(fn (Publication $publication) => (int) $publication->getKey());

        $posts = $savedRows
            ->map(function (SavedPublication $saved) use ($publications, $profile) {
                $publication = $publications->get((int) $saved->id_publication);

                if (! $publication) {
                    return null;
                }

                return [
                    ...$this->toPost($publication, $profile),
                    'savedAt' => $saved->created_at?->toISOString(),
                    'savedLabel' => $saved->created_at?->diffForHumans() ?? '',
                ];
            })
            ->filter()
            ->values();

        return response()->json(['data' => $posts]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $profile = OfficialSchema::ensureProfile($request->user());
        $publication = Publication::query()
            ->where('id_publication', $id)
            ->where(function (Builder $query) use ($profile) {
                $query->where(function (Builder $publishedQuery) {
                    $publishedQuery->where('state', 'published')->where('visibility', true);
                })->orWhere('id_profile', $profile->getKey());
            })
            ->firstOrFail();

        return response()->json([
            'post' => $this->toPost($this->loadPublication($publication, $profile, includeComments: true), $profile),
        ]);
    }

    public function publishProject(Request $request, int $id): JsonResponse
    {
        $project = Proyecto::forUser($request->user()->id)->findOrFail($id);
        $profile = $project->profile ?: OfficialSchema::ensureProfile($request->user());
        $content = $request->input('content') ?: $this->defaultProjectContent($project);

        $publication = DB::transaction(function () use ($project, $profile, $content) {
            $publication = $this->findPublicationByDetail('id_project', $project->getKey());

            if ($publication) {
                $publication->update([
                    'description' => Str::limit($content, 255, ''),
                    'visibility' => true,
                    'state' => 'published',
                    'id_profile' => $profile->getKey(),
                ]);

                $publication->detail()->update([
                    'id_publicized' => $profile->getKey(),
                    'id_project' => $project->getKey(),
                    'id_experience' => null,
                ]);

                return $publication;
            }

            $publication = Publication::create([
                'description' => Str::limit($content, 255, ''),
                'outstanding' => false,
                'visibility' => true,
                'state' => 'published',
                'id_profile' => $profile->getKey(),
            ]);

            PublicationDetail::create([
                'id_publicized' => $profile->getKey(),
                'id_project' => $project->getKey(),
                'id_publication' => $publication->getKey(),
            ]);

            return $publication;
        });

        return response()->json([
            'message' => 'Proyecto compartido en el feed.',
            'post' => $this->toPost($this->loadPublication($publication, $profile, includeComments: false), $profile),
        ], $publication->wasRecentlyCreated ? 201 : 200);
    }

    public function publishExperience(Request $request, int $id): JsonResponse
    {
        $experience = Experience::forUser($request->user()->id)->findOrFail($id);
        $profile = $experience->profile ?: OfficialSchema::ensureProfile($request->user());
        $content = $request->input('content') ?: $this->defaultExperienceContent($experience);

        $publication = DB::transaction(function () use ($experience, $profile, $content) {
            $publication = $this->findPublicationByDetail('id_experience', $experience->getKey());

            if ($publication) {
                $publication->update([
                    'description' => Str::limit($content, 255, ''),
                    'visibility' => true,
                    'state' => 'published',
                    'id_profile' => $profile->getKey(),
                ]);

                $publication->detail()->update([
                    'id_publicized' => $profile->getKey(),
                    'id_project' => null,
                    'id_experience' => $experience->getKey(),
                ]);

                return $publication;
            }

            $publication = Publication::create([
                'description' => Str::limit($content, 255, ''),
                'outstanding' => false,
                'visibility' => true,
                'state' => 'published',
                'id_profile' => $profile->getKey(),
            ]);

            PublicationDetail::create([
                'id_publicized' => $profile->getKey(),
                'id_experience' => $experience->getKey(),
                'id_publication' => $publication->getKey(),
            ]);

            return $publication;
        });

        return response()->json([
            'message' => 'Experiencia compartida en el feed.',
            'post' => $this->toPost($this->loadPublication($publication, $profile, includeComments: false), $profile),
        ], $publication->wasRecentlyCreated ? 201 : 200);
    }

    public function unshare(Request $request, int $id): JsonResponse
    {
        $profile = OfficialSchema::ensureProfile($request->user());

        $publication = Publication::query()
            ->with('detail')
            ->where('id_publication', $id)
            ->where('id_profile', $profile->getKey())
            ->firstOrFail();

        $publication->update([
            'visibility' => false,
        ]);

        return response()->json([
            'message' => 'Elemento retirado del feed.',
            'publicationId' => (int) $publication->getKey(),
            'projectId' => $publication->detail?->id_project,
            'experienceId' => $publication->detail?->id_experience,
        ]);
    }

    public function toggleLike(Request $request, int $id): JsonResponse
    {
        $profile = OfficialSchema::ensureProfile($request->user());
        $publication = Publication::published()->findOrFail($id);
        $emoji = $this->likeEmoji();

        $reaction = Reaction::query()
            ->where('id_publication', $publication->getKey())
            ->where('id_reactor', $profile->getKey())
            ->first();

        $liked = false;

        if ($reaction) {
            $reaction->delete();
        } else {
            Reaction::create([
                'id_publication' => $publication->getKey(),
                'id_reactor' => $profile->getKey(),
                'id_emoji' => $emoji->getKey(),
            ]);
            $liked = true;
        }

        return response()->json([
            'liked' => $liked,
            'post' => $this->toPost($this->loadPublication($publication->fresh(), $profile, includeComments: false), $profile),
        ]);
    }

    public function toggleSave(Request $request, int $id): JsonResponse
    {
        $profile = OfficialSchema::ensureProfile($request->user());
        $publication = Publication::published()->with('detail')->findOrFail($id);

        $saved = SavedPublication::query()
            ->where('id_publication', $publication->getKey())
            ->where('id_profile', $profile->getKey())
            ->first();

        $isSaved = false;

        if ($saved) {
            $saved->delete();
        } else {
            SavedPublication::create([
                'id_publication' => $publication->getKey(),
                'id_profile' => $profile->getKey(),
                'id_project' => $publication->detail?->id_project,
            ]);
            $isSaved = true;
        }

        return response()->json([
            'saved' => $isSaved,
            'post' => $this->toPost($this->loadPublication($publication->fresh(), $profile, includeComments: false), $profile),
        ]);
    }

    public function comment(Request $request, int $id): JsonResponse
    {
        $profile = OfficialSchema::ensureProfile($request->user());
        $publication = Publication::published()->findOrFail($id);
        $comment = $this->normalizeComment((string) $request->input('comment', ''));

        $validator = Validator::make(
            ['comment' => $comment],
            [
                'comment' => [
                    'required',
                    'string',
                    'max:'.self::COMMENT_MAX_LENGTH,
                    "regex:/\\A[\\pL\\pN\\s.,;:!?¡¿'\"()@#%&+\\-_\\/]+\\z/u",
                ],
            ],
            [
                'comment.required' => 'Escribe un comentario antes de enviarlo.',
                'comment.max' => 'El comentario no puede superar '.self::COMMENT_MAX_LENGTH.' caracteres.',
                'comment.regex' => 'Usa solo letras, numeros, espacios y puntuacion comun.',
            ]
        );

        $data = $validator->validate();

        PublicationComment::create([
            'id_publication' => $publication->getKey(),
            'id_commentator' => $profile->getKey(),
            'comment' => $data['comment'],
        ]);

        return response()->json([
            'message' => 'Comentario publicado.',
            'post' => $this->toPost($this->loadPublication($publication->fresh(), $profile, includeComments: true), $profile),
        ], 201);
    }

    private function normalizeComment(string $comment): string
    {
        $comment = preg_replace('/[\\x00-\\x1F\\x7F]/u', ' ', $comment) ?? '';
        $comment = preg_replace('/\\s+/u', ' ', $comment) ?? '';

        return trim($comment);
    }

    private function boundedLimit(Request $request): int
    {
        return max(1, min((int) $request->integer('limit', 20), 50));
    }

    private function baseFeedQuery(?Profile $viewerProfile = null, bool $includeComments = false): Builder
    {
        $query = Publication::query()
            ->with([
                'profile.userRole.user',
                'profile.jobTitle',
                'detail.project.skills',
                'detail.experience',
                'latestComment.commentator.userRole.user',
            ])
            ->withCount([
                'comments as comments_count',
                'reactions as likes_count',
                'saves as saves_count',
            ]);

        if ($includeComments) {
            $query->with('comments.commentator.userRole.user');
        }

        if ($viewerProfile) {
            $query->withExists([
                'reactions as liked_by_me' => fn (Builder $builder) => $builder->where('id_reactor', $viewerProfile->getKey()),
                'saves as saved_by_me' => fn (Builder $builder) => $builder->where('id_profile', $viewerProfile->getKey()),
            ]);
        }

        return $query;
    }

    private function findPublicationByDetail(string $column, int $id): ?Publication
    {
        return Publication::query()
            ->whereHas('detail', fn (Builder $builder) => $builder->where($column, $id))
            ->first();
    }

    private function loadPublication(Publication $publication, ?Profile $viewerProfile = null, bool $includeComments = true): Publication
    {
        return $this->baseFeedQuery($viewerProfile, $includeComments)->findOrFail($publication->getKey());
    }

    private function likeEmoji(): Emoji
    {
        return Emoji::firstOrCreate(
            ['name' => 'like'],
            ['state' => 'activate']
        );
    }

    private function toPost(Publication $publication, ?Profile $viewerProfile = null): array
    {
        $detail = $publication->detail;
        $project = $detail?->project;
        $experience = $detail?->experience;
        $profile = $publication->profile ?: $project?->profile ?: $experience?->profile;
        $user = $profile?->userRole?->user;
        $authorName = trim(($profile?->name ?? $user?->nombre ?? '') . ' ' . ($profile?->last_name ?? $user?->apellido ?? ''));
        $authorName = $authorName !== '' ? $authorName : 'Usuario Portafy';
        $sourceType = $project ? 'project' : ($experience ? 'experience' : 'profile');
        $likedByMe = (bool) ($publication->liked_by_me ?? false);
        $savedByMe = (bool) ($publication->saved_by_me ?? false);

        if ($viewerProfile && ! array_key_exists('liked_by_me', $publication->getAttributes()) && $publication->relationLoaded('reactions')) {
            $likedByMe = $publication->reactions->contains('id_reactor', $viewerProfile->getKey());
            $savedByMe = $publication->saves->contains('id_profile', $viewerProfile->getKey());
        }

        $comments = $publication->relationLoaded('comments')
            ? $publication->comments
            : ($publication->relationLoaded('latestComment') && $publication->latestComment
                ? collect([$publication->latestComment])
                : collect());

        return [
            'id' => 'publication-' . $publication->getKey(),
            'publicationId' => (int) $publication->getKey(),
            'type' => 'portfolio',
            'sourceType' => $sourceType,
            'projectId' => $project?->id,
            'experienceId' => $experience?->id,
            'author' => [
                'id' => $user?->id,
                'name' => $authorName,
                'title' => $profile?->jobTitle?->name ?: 'Profesional Portafy',
                'avatar' => $this->assetUrlService->fromStoragePath($profile?->profile_photo),
            ],
            'content' => $publication->description ?: $this->defaultContent($project, $experience),
            'visibility' => (bool) $publication->visibility,
            'ownedByMe' => $viewerProfile && $profile && (int) $viewerProfile->getKey() === (int) $profile->getKey(),
            'project' => $project ? [
                'title' => $project->titulo,
                'description' => $project->descripcion,
                'status' => $project->estado,
                'repoUrl' => $project->url_repositorio,
                'demoUrl' => $project->url_demo,
            ] : null,
            'experience' => $experience ? [
                'title' => $experience->title,
                'company' => $experience->company,
                'type' => $experience->type,
                'typeLabel' => $experience->type_label,
                'description' => $experience->descripcion,
                'startDate' => $experience->fecha_inicio,
                'endDate' => $experience->fecha_fin,
                'isCurrent' => $experience->actualmente,
            ] : null,
            'image' => $this->assetUrlService->fromStoragePath($project?->imagen),
            'likes' => (int) ($publication->likes_count ?? 0),
            'commentsCount' => (int) ($publication->comments_count ?? 0),
            'saves' => (int) ($publication->saves_count ?? 0),
            'likedByMe' => $likedByMe,
            'savedByMe' => $savedByMe,
            'tags' => $project?->skills?->pluck('name')->values()->all() ?? [],
            'posted' => $publication->created_at?->diffForHumans() ?? '',
            'createdAt' => $publication->created_at?->toISOString(),
            'comments' => $comments->map(fn (PublicationComment $comment) => [
                'id' => (int) $comment->getKey(),
                'text' => $comment->comment,
                'author' => trim(($comment->commentator?->name ?? '') . ' ' . ($comment->commentator?->last_name ?? '')) ?: 'Usuario Portafy',
                'authorAvatar' => $this->assetUrlService->fromStoragePath($comment->commentator?->profile_photo),
                'posted' => $comment->created_at?->diffForHumans() ?? '',
            ])->values(),
        ];
    }

    private function defaultContent(?Proyecto $project, ?Experience $experience): string
    {
        if ($project) {
            return $this->defaultProjectContent($project);
        }

        if ($experience) {
            return $this->defaultExperienceContent($experience);
        }

        return 'Comparti una actualizacion de mi perfil profesional.';
    }

    private function defaultProjectContent(?Proyecto $project): string
    {
        if (! $project) {
            return 'Comparti un proyecto de mi portafolio.';
        }

        $description = trim((string) $project->descripcion);

        if ($description === '') {
            return 'Comparti mi proyecto "' . $project->titulo . '" desde mi portafolio.';
        }

        return 'Proyecto publicado: ' . Str::limit($description, 210);
    }

    private function defaultExperienceContent(?Experience $experience): string
    {
        if (! $experience) {
            return 'Comparti una experiencia profesional de mi perfil.';
        }

        $headline = trim(implode(' en ', array_filter([$experience->title, $experience->company])));
        $description = trim((string) $experience->descripcion);

        if ($description === '') {
            return 'Experiencia publicada: ' . ($headline ?: 'Trayectoria profesional') . '.';
        }

        return 'Experiencia publicada: ' . ($headline ?: 'Trayectoria profesional') . '. ' . Str::limit($description, 170);
    }
}
