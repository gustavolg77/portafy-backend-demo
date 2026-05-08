<?php

namespace App\Services;

use App\Models\Attended;
use App\Models\Suggestion;
use Illuminate\Database\Eloquent\Builder;

class SuggestionService
{
    /* ════════════════════════════════════════════════════
       LIST
    ════════════════════════════════════════════════════ */

    public function list(array $filters = []): array
    {
        $query = Suggestion::query()
        ->with(['profile.userRole.user'])
        ->whereDoesntHave('attended')          // ← solo sin registro en ATTENDED
        ->orderByDesc('created_at')
        ->orderByDesc('id_suggestion');

        $this->applyFilters($query, $filters);

        $suggestions = $query->get();

        return [
            'items' => $suggestions->map(fn(Suggestion $s) => $this->mapSuggestion($s))->values(),
            'meta'  => [
                'total'   => $suggestions->count(),
                'filters' => [
                    'search'    => $this->normalizeText($filters['search']    ?? ''),
                    'type'      => $this->normalizeText($filters['type']      ?? 'todos') ?: 'todos',
                    'date_from' => $this->normalizeText($filters['date_from'] ?? ''),
                    'date_to'   => $this->normalizeText($filters['date_to']   ?? ''),
                ],
            ],
        ];
    }

    /* ════════════════════════════════════════════════════
       CONTEXT
    ════════════════════════════════════════════════════ */

    public function context(Suggestion $suggestion): array
    {
        $profileId = (int) $suggestion->id_profile;
        $profile   = $suggestion->profile;

        /* ── Registro ATTENDED para esta sugerencia ── */
        $attended = Attended::where('id_suggestion', $suggestion->getKey())
            ->with(['profile'])
            ->latest('created_at')
            ->first();

        /* ── Historial del usuario ── */
        $totalActivas = Suggestion::where('id_profile', $profileId)->count();

        $linkedStats = Attended::whereNotNull('id_suggestion')
            ->whereHas('suggestion', fn($q) => $q->where('id_profile', $profileId))
            ->selectRaw('state, COUNT(*) as cnt')
            ->groupBy('state')
            ->pluck('cnt', 'state');

        return [
            'suggestion' => $this->mapSuggestion($suggestion),

            'attended' => $attended ? [
                'exists'      => true,
                'state'       => $attended->state,
                'state_label' => Attended::STATES[$attended->state] ?? $attended->state,
                'note'        => $attended->action_taken,           // ← usa action_taken
                'admin'       => $attended->profile ? [
                    'id'   => (int) $attended->profile->id_profile,
                    'name' => trim(
                        ($attended->profile->name ?? '') . ' ' .
                        ($attended->profile->last_name ?? '')
                    ),
                ] : null,
                'created_at'  => $attended->created_at?->format('d/m/Y H:i'),
                'updated_at'  => $attended->updated_at?->format('d/m/Y H:i'),
            ] : [
                'exists'      => false,
                'state'       => 'pending',
                'state_label' => 'Pendiente',
                'note'        => null,
                'admin'       => null,
                'created_at'  => null,
                'updated_at'  => null,
            ],

            'postulant_history' => [
                'total_activas' => $totalActivas,
                'in_discussion' => (int) ($linkedStats['in_discussion'] ?? 0),
                'escaladas'     => (int) ($linkedStats['higher']        ?? 0),
            ],

            'profile' => $profile ? [
                'id'        => (int) $profile->id_profile,
                'name'      => $profile->name,
                'last_name' => $profile->last_name,
                'avatar'    => $profile->profile_photo,
            ] : null,
        ];
    }

    /* ════════════════════════════════════════════════════
       ACCIONES — nunca tocan la tabla SUGGESTION
    ════════════════════════════════════════════════════ */

    public function accept(Suggestion $suggestion, ?int $administratorProfileId, ?string $note = null): array
    {
        $suggestionId = $suggestion->getKey();
        $attended = $this->createAttendedRecord($suggestionId, $administratorProfileId, 'accepted', $note);

        // ← SIN $suggestion->delete()
        return $this->actionResponse('La sugerencia fue aceptada y registrada correctamente.', $suggestionId, $administratorProfileId, 'accepted', $attended?->id_attended);
    }

    public function reject(Suggestion $suggestion, ?int $administratorProfileId, ?string $note = null): array
    {
        $suggestionId = $suggestion->getKey();
        $attended = $this->createAttendedRecord($suggestionId, $administratorProfileId, 'rejected', $note);

        // ← SIN $suggestion->delete()
        return $this->actionResponse('La sugerencia fue rechazada y registrada correctamente.', $suggestionId, $administratorProfileId, 'rejected', $attended?->id_attended);
    }

    public function discuss(Suggestion $suggestion, ?int $administratorProfileId, ?string $note = null): array
    {
        $suggestionId = $suggestion->getKey();
        $attended = $this->createAttendedRecord($suggestionId, $administratorProfileId, 'in_discussion', $note);

        return $this->actionResponse('La sugerencia fue marcada como en discusión.', $suggestionId, $administratorProfileId, 'in_discussion', $attended?->id_attended);
    }

    public function escalate(Suggestion $suggestion, ?int $administratorProfileId, ?string $note = null): array
    {
        $suggestionId = $suggestion->getKey();
        $attended = $this->createAttendedRecord($suggestionId, $administratorProfileId, 'higher', $note);

        return $this->actionResponse('La sugerencia fue escalada correctamente.', $suggestionId, $administratorProfileId, 'higher', $attended?->id_attended);
    }

    public function ignore(Suggestion $suggestion, ?int $administratorProfileId, ?string $note = null): array
    {
        $suggestionId = $suggestion->getKey();
        $attended = $this->createAttendedRecord($suggestionId, $administratorProfileId, 'ignored', $note);

        // ← SIN $suggestion->delete()
        return $this->actionResponse('La sugerencia fue ignorada y registrada correctamente.', $suggestionId, $administratorProfileId, 'ignored', $attended?->id_attended);
    }

    /* ════════════════════════════════════════════════════
       PRIVADOS
    ════════════════════════════════════════════════════ */

    private function createAttendedRecord(
        int     $suggestionId,
        ?int    $administratorProfileId,
        string  $state,
        ?string $note = null
    ): ?Attended {
        if (!$administratorProfileId) {
            return null;
        }

        return Attended::create([
            'id_suggestion'    => $suggestionId,
            'id_administrator' => $administratorProfileId,  // ← columna correcta
            'state'            => $state,
            'action_taken'     => $note,                    // ← columna correcta
        ]);
    }

    private function actionResponse(
        string $message,
        int    $suggestionId,
        ?int   $adminId,
        string $state,
        ?int   $attendedId = null
    ): array {
        return [
            'message' => $message,
            'data'    => [
                'id_attended'      => $attendedId,
                'id_suggestion'    => $suggestionId,
                'id_administrator' => $adminId,
                'state'            => $state,
            ],
        ];
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        $search   = $this->normalizeText($filters['search']    ?? '');
        $type     = $this->normalizeText($filters['type']      ?? 'todos');
        $dateFrom = $this->normalizeText($filters['date_from'] ?? '');
        $dateTo   = $this->normalizeText($filters['date_to']   ?? '');

        if ($search) {
            $query->where('description', 'like', "%{$search}%");
        }

        if ($type && $type !== 'todos') {
            $query->where('type', $type);
        }

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }
    }

    private function mapSuggestion(Suggestion $suggestion): array
    {
        $profile = $suggestion->profile;
        $meta    = $suggestion->getMeta();

        return [
            'id'          => (int) $suggestion->getKey(),
            'id_profile'  => (int) $suggestion->id_profile,
            'description' => $suggestion->description,
            'type'        => $suggestion->type,
            'meta'        => $meta,
            'postulant'   => [
                'id'       => $profile ? (int) $profile->id_profile : null,
                'name'     => $profile ? trim("{$profile->name} {$profile->last_name}") : 'Usuario desconocido',
                'initials' => $profile ? $this->getInitials($profile->name, $profile->last_name) : '??',
                'photo'    => $profile?->profile_photo ?? null,
            ],
            'formattedDate' => $suggestion->created_at?->format('d/m/Y H:i') ?? '',
            'state'         => 'pending',
        ];
    }

    private function getInitials(string $name, string $lastName): string
    {
        $first = $name     ? strtoupper(substr($name,     0, 1)) : '';
        $last  = $lastName ? strtoupper(substr($lastName, 0, 1)) : '';
        return $first . $last ?: '??';
    }

    private function normalizeText(string $value): string
    {
        return is_string($value) ? trim($value) : '';
    }
}