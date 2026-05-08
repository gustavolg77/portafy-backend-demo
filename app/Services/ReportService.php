<?php

namespace App\Services;

use App\Models\Attended;
use App\Models\Publication;
use App\Models\Report;
use App\Models\Usuario;
use App\Support\OfficialSchema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportService
{
    private const MOTIVO_VALUES = [
        'hate_incitement' => 'Incitacion al odio',
        'impersonation' => 'Suplantacion',
        'prohibited_content' => 'Contenido Prohibido',
        'violence' => 'Violencia',
        'terrorism' => 'Terrorismo',
        'spam' => 'Spam',
    ];

    public function __construct(private readonly PublicAssetUrlService $assetUrlService) {}

    public function reportPublication(Usuario $authenticatedUser, Publication $publication, array $payload): array
    {
        $reporterProfile = OfficialSchema::ensureProfile($authenticatedUser);
        $publication->loadMissing('profile', 'detail.project.profile', 'detail.experience.profile');

        if ((string) $publication->state !== 'published' || ! (bool) $publication->visibility) {
            throw new \RuntimeException('Esta publicacion no esta disponible para reportes.');
        }

        $reportedProfile = $publication->profile
            ?: $publication->detail?->project?->profile
            ?: $publication->detail?->experience?->profile;

        if (! $reportedProfile) {
            throw new \RuntimeException('No se pudo identificar al usuario reportado.');
        }

        if ((int) $reportedProfile->getKey() === (int) $reporterProfile->getKey()) {
            throw new \RuntimeException('No puedes reportar tu propia publicacion.');
        }

        $motivo = $this->resolveMotivo((string) ($payload['motivo'] ?? ''));
        $description = trim((string) ($payload['description'] ?? ''));
        $testsUrl = trim((string) ($payload['tests_url'] ?? ''));

        $duplicateOpenReport = Report::query()
            ->where('id_profile', $reporterProfile->getKey())
            ->where('id_publication', $publication->getKey())
            ->whereDoesntHave('attendeds')
            ->exists();

        if ($duplicateOpenReport) {
            throw new \RuntimeException('Ya enviaste un reporte abierto sobre esta publicacion.');
        }

        $report = Report::create([
            'id_comment' => null,
            'id_response' => null,
            'id_profile' => $reporterProfile->getKey(),
            'id_publication' => $publication->getKey(),
            'id_project' => $publication->detail?->id_project,
            'id_message' => null,
            'id_group' => null,
            'id_reported_user' => $reportedProfile->getKey(),
            'id_portfolio' => null,
            'description' => $description !== '' ? mb_substr($description, 0, 255) : 'Reporte de publicacion.',
            'tests_url' => $testsUrl !== '' ? mb_substr($testsUrl, 0, 255) : null,
            'created_at' => now(),
            'motivo' => $motivo,
        ]);

        $report->load([
            'reporterProfile.userRole.user',
            'reportedProfile.userRole.user',
            'project',
            'publication',
        ]);

        return [
            'message' => 'Reporte enviado al equipo de administracion.',
            'report' => $this->mapReport($report),
        ];
    }

    public function reportMotivos(): array
    {
        return collect(self::MOTIVO_VALUES)
            ->map(fn (string $label, string $key) => ['key' => $key, 'label' => $label])
            ->values()
            ->all();
    }

    public function list(array $filters = []): array
    {
        $query = Report::query()
            ->with([
                'reporterProfile.userRole.user',
                'reportedProfile.userRole.user',
                'project',
                'publication',
            ])
            ->whereDoesntHave('attendeds');

        $this->applyFilters($query, $filters);

        $reports = $query
            ->orderByDesc('REPORT.created_at')
            ->orderByDesc('REPORT.id_report')
            ->get();

        return [
            'items' => $reports->map(fn(Report $report) => $this->mapReport($report))->values(),
            'meta' => [
                'total' => $reports->count(),
                'filters' => [
                    'search' => $this->normalizeText($filters['search'] ?? ''),
                    'motivo' => $this->normalizeText($filters['motivo'] ?? 'todos') ?: 'todos',
                    'date_from' => $this->normalizeText($filters['date_from'] ?? ''),
                    'date_to' => $this->normalizeText($filters['date_to'] ?? ''),
                ],
            ],
        ];
    }

    public function rejectAndAttend(Report $report, ?int $administratorProfileId, ?Usuario $authenticatedUser): array
    {
        $attended = $this->createAttendedRecord(
            $report,
            [
                'state' => 'rejected',
                'action_taken' => 'Reporte eliminado por administrador.',
                'test_url' => '',
            ],
            $administratorProfileId,
            $authenticatedUser
        );

        return [
            'message' => 'El reporte fue marcado como eliminado y atendido correctamente.',
            'data' => [
                'id_attended' => (int) $attended->getKey(),
                'id_report' => (int) $report->getKey(),
                'id_administrator' => (int) $administratorProfileId,
                'state' => 'rejected',
            ],
        ];
    }

    public function attend(Report $report, array $payload, ?int $administratorProfileId, ?Usuario $authenticatedUser): array
    {
        $state = trim((string) ($payload['state'] ?? ''));

        if ($state === '') {
            throw new \RuntimeException('El estado de atencion es obligatorio.');
        }

        $attended = $this->createAttendedRecord(
            $report,
            [
                'state' => $state,
                'action_taken' => (string) ($payload['action_taken'] ?? ''),
                'test_url' => (string) ($payload['test_url'] ?? ''),
            ],
            $administratorProfileId,
            $authenticatedUser
        );

        return [
            'message' => 'La atencion del reporte se registro correctamente.',
            'data' => [
                'id_attended' => (int) $attended->getKey(),
                'id_report' => (int) $report->getKey(),
                'id_administrator' => (int) $attended->id_administrator,
                'state' => (string) $attended->state,
            ],
        ];
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        $motivo = $this->normalizeText($filters['motivo'] ?? '');
        if ($motivo !== '' && $motivo !== 'todos') {
            $query->where('REPORT.motivo', $this->resolveMotivo($motivo));
        }

        $dateFrom = $this->normalizeText($filters['date_from'] ?? '');
        if ($dateFrom !== '') {
            $query->whereDate('REPORT.created_at', '>=', $dateFrom);
        }

        $dateTo = $this->normalizeText($filters['date_to'] ?? '');
        if ($dateTo !== '') {
            $query->whereDate('REPORT.created_at', '<=', $dateTo);
        }

        $search = $this->normalizeText($filters['search'] ?? '');
        if ($search === '') {
            return;
        }

        $query->where(function (Builder $builder) use ($search) {
            $builder
                ->where('REPORT.description', 'like', '%' . $search . '%')
                ->orWhere('REPORT.motivo', 'like', '%' . $search . '%')
                ->orWhereHas('reporterProfile', function (Builder $profileQuery) use ($search) {
                    $this->applyProfileNameFilter($profileQuery, $search);
                })
                ->orWhereHas('reportedProfile', function (Builder $profileQuery) use ($search) {
                    $this->applyProfileNameFilter($profileQuery, $search);
                })
                ->orWhereHas('project', fn(Builder $projectQuery) => $projectQuery->where('title', 'like', '%' . $search . '%'))
                ->orWhereHas('publication', fn(Builder $publicationQuery) => $publicationQuery->where('description', 'like', '%' . $search . '%'));
        });
    }

    private function applyProfileNameFilter(Builder $query, string $search): void
    {
        $query->where(function (Builder $profileQuery) use ($search) {
            $profileQuery
                ->where('name', 'like', '%' . $search . '%')
                ->orWhere('last_name', 'like', '%' . $search . '%')
                ->orWhereRaw("CONCAT(COALESCE(name, ''), ' ', COALESCE(last_name, '')) like ?", ['%' . $search . '%']);
        });
    }

    private function mapReport(Report $report): array
    {
        $reportedUser = $this->mapProfileIdentity($report->reportedProfile);
        $reporterUser = $this->mapProfileIdentity($report->reporterProfile);

        return [
            'id' => (int) $report->getKey(),
            'id_comment' => $report->id_comment,
            'id_response' => $report->id_response,
            'id_profile' => $report->id_profile,
            'id_publication' => $report->id_publication,
            'id_project' => $report->id_project,
            'id_message' => $report->id_message,
            'id_group' => $report->id_group,
            'id_reported_user' => $report->id_reported_user,
            'id_portfolio' => $report->id_portfolio,
            'description' => (string) ($report->description ?? ''),
            'tests_url' => (string) ($report->tests_url ?? ''),
            'created_at' => optional($report->created_at)?->toISOString() ?? '',
            'motivo' => (string) ($report->motivo ?? ''),
            'ref_type' => $this->resolveRefType($report),
            'reference_label' => $this->resolveReferenceLabel($report),
            'reported_user' => $reportedUser,
            'reporter_user' => $reporterUser,
        ];
    }

    private function mapProfileIdentity($profile): array
    {
        if (! $profile) {
            return [
                'id' => null,
                'name' => 'Usuario',
                'initials' => 'US',
                'photo' => '',
            ];
        }

        $fullName = trim(collect([$profile->name, $profile->last_name])->filter()->implode(' '));

        return [
            'id' => $profile->id_profile,
            'name' => $fullName !== '' ? $fullName : 'Usuario',
            'initials' => $this->initialsFromName($fullName),
            'photo' => $this->assetUrlService->fromStoragePath($profile->profile_photo),
        ];
    }

    private function initialsFromName(string $fullName): string
    {
        $parts = Collection::make(preg_split('/\s+/', trim($fullName) ?: 'Usuario'))
            ->filter()
            ->take(2)
            ->map(fn(string $part) => mb_strtoupper(mb_substr($part, 0, 1)));

        return $parts->implode('') ?: 'US';
    }

    private function resolveRefType(Report $report): string
    {
        if ($report->id_publication) {
            return 'Publicacion';
        }

        if ($report->id_project) {
            return 'Proyecto';
        }

        if ($report->id_comment) {
            return 'Comentario';
        }

        if ($report->id_message) {
            return 'Mensaje';
        }

        if ($report->id_group) {
            return 'Grupo';
        }

        if ($report->id_portfolio) {
            return 'Portafolio';
        }

        if ($report->id_profile) {
            return 'Perfil';
        }

        return 'General';
    }

    private function resolveReferenceLabel(Report $report): string
    {
        if ($report->id_project && $report->project) {
            return (string) ($report->project->titulo ?: $report->project->getRawOriginal('title'));
        }

        if ($report->id_publication && $report->publication) {
            return (string) ($report->publication->description ?? 'Publicacion reportada');
        }

        return '';
    }

    private function normalizeText(mixed $value): string
    {
        return is_string($value) ? trim($value) : '';
    }

    private function resolveMotivo(string $value): string
    {
        $normalized = trim($value);

        if (isset(self::MOTIVO_VALUES[$normalized])) {
            return self::MOTIVO_VALUES[$normalized];
        }

        if (in_array($normalized, self::MOTIVO_VALUES, true)) {
            return $normalized;
        }

        throw new \RuntimeException('Selecciona un motivo de reporte valido.');
    }

    private function resolveAdministratorProfileId(?int $administratorProfileId, ?Usuario $authenticatedUser): ?int
    {
        if ($administratorProfileId) {
            return $administratorProfileId;
        }

        $profileId = $authenticatedUser?->profileRecord()?->getKey();

        return $profileId ? (int) $profileId : null;
    }

    private function createAttendedRecord(
        Report $report,
        array $payload,
        ?int $administratorProfileId,
        ?Usuario $authenticatedUser
    ): Attended {
        $resolvedAdministratorId = $this->resolveAdministratorProfileId($administratorProfileId, $authenticatedUser);

        if (! $resolvedAdministratorId) {
            throw new \RuntimeException('No se pudo resolver el perfil del administrador.');
        }

        if ($report->attendeds()->exists()) {
            throw new \RuntimeException('Este reporte ya fue atendido anteriormente.');
        }

        $state = trim((string) ($payload['state'] ?? ''));
        $actionTaken = trim((string) ($payload['action_taken'] ?? ''));
        $testUrl = trim((string) ($payload['test_url'] ?? ''));

        return Attended::create([
            'id_suggestion' => null,
            'id_report' => $report->getKey(),
            'id_administrator' => $resolvedAdministratorId,
            'id_preference' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'action_taken' => $actionTaken !== '' ? $actionTaken : 'Sin detalle adicional.',
            'test_url' => $testUrl,
            'state' => $state,
        ]);
    }
    public function context(Report $report): array
    {
        // ¿Tiene instancia en ATTENDED?
        $hasAttended = Attended::where('id_report', $report->id_report)->exists();

        // Estadísticas para un perfil dado
        $statsFor = function (int $profileId): array {
            // Total de reportes realizados por este perfil
            $total = DB::table('REPORT')
                ->where('id_reported_user', $profileId)
                ->count();

            // Reportes que este perfil hizo y fueron aceptados
            $accepted = DB::table('REPORT as r')
                ->join('ATTENDED as a', 'a.id_report', '=', 'r.id_report')
                ->where('r.id_profile', $profileId)
                ->where('a.state', 'accepted')
                ->count();

            // Reportes que este perfil hizo y fueron rechazados
            $rejected = DB::table('REPORT as r')
                ->join('ATTENDED as a', 'a.id_report', '=', 'r.id_report')
                ->where('r.id_reported_user', $profileId)
                ->where('a.state', 'rejected')
                ->count();

            // Reportes aceptados EN CONTRA de este perfil
            // (alguien reportó a este perfil y fue aceptado)
            $acceptedAgainst = DB::table('REPORT as r')
                ->join('ATTENDED as a', 'a.id_report', '=', 'r.id_report')
                ->where('r.id_reported_user', $profileId)
                ->where('a.state', 'accepted')
                ->count();

            return [
                'total'            => $total,
                'accepted'         => $accepted,
                'rejected'         => $rejected,
                'accepted_against' => $acceptedAgainst,
            ];
        };

        return [
            'is_open'        => !$hasAttended,
            'reporter_stats' => $statsFor((int) $report->id_profile),
            'reported_stats' => $statsFor((int) $report->id_reported_user),
        ];
    }
}
