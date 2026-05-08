<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ExperienceController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\FormacionAcademicaController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MeController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SuggestionController;
use App\Http\Controllers\SkillController;
use App\Http\Controllers\SocialController;
use App\Http\Controllers\DefinitionCatalogController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CvImportController;
use App\Http\Controllers\CvController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\PostulationController;
use App\Http\Controllers\AgregarAdminController;
use App\Http\Controllers\HistorialController;
use App\Http\Controllers\VisibilityController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);
Route::post('/forgot-password', [PasswordResetController::class, 'forgotPassword']);
Route::get('/user/search', [ProfileController::class, 'search']);
Route::post('/user/search/filters', [ProfileController::class, 'searchWithFilters']);
Route::get('/perfil/public/{usuario}/overview', [ProfileController::class, 'publicOverview']);
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/feed/posts', [FeedController::class, 'index']);
    Route::post('/formacion', [FormacionAcademicaController::class, 'store']);
    Route::get('/formacion', [FormacionAcademicaController::class, 'index']);

    Route::get('/skills', [SkillController::class, 'index']);
    Route::post('/skills', [SkillController::class, 'store']);
    Route::put('/skills/{id}', [SkillController::class, 'update']);
    Route::delete('/skills/{id}', [SkillController::class, 'destroy']);

    Route::get('/experience', [ExperienceController::class, 'index']);
    Route::post('/experience', [ExperienceController::class, 'store']);
    Route::put('/experience/{id}', [ExperienceController::class, 'update']);
    Route::delete('/experience/{id}', [ExperienceController::class, 'destroy']);

    Route::get('/projects', [ProjectController::class, 'index']);
    Route::post('/projects', [ProjectController::class, 'store']);
    Route::put('/projects/{id}', [ProjectController::class, 'update']);
    Route::delete('/projects/{id}', [ProjectController::class, 'destroy']);
    Route::post('/projects/{id}/publish', [FeedController::class, 'publishProject']);
    Route::post('/experience/{id}/publish', [FeedController::class, 'publishExperience']);

    Route::get('/feed/me', [FeedController::class, 'mine']);
    Route::get('/feed/saved', [FeedController::class, 'saved']);
    Route::get('/feed/posts/{id}', [FeedController::class, 'show']);
    Route::get('/reports', [ReportController::class, 'index']);
    Route::get('/reports/motivos', [ReportController::class, 'motivos']);
    Route::post('/reports/publications/{publication}', [ReportController::class, 'storePublication']);
    Route::post('/reports/{report}/attend', [ReportController::class, 'attend']);
    Route::post('/reports/{report}/reject', [ReportController::class, 'reject']);
    Route::get('/reports/{report}/context', [ReportController::class, 'context']);

    // Sugerencias
    Route::get('/suggestions', [SuggestionController::class, 'index']);
    Route::post('/suggestions/{suggestion}/accept', [SuggestionController::class, 'accept']);
    Route::post('/suggestions/{suggestion}/reject', [SuggestionController::class, 'reject']);
    Route::post('/suggestions/{suggestion}/discuss', [SuggestionController::class, 'discuss']);
    Route::post('/suggestions/{suggestion}/escalate', [SuggestionController::class, 'escalate']);
    Route::post('/suggestions/{suggestion}/ignore', [SuggestionController::class, 'ignore']);
    Route::get('/suggestions/{suggestion}/context', [SuggestionController::class, 'context']);
    Route::post('/feed/posts/{id}/like', [FeedController::class, 'toggleLike']);
    Route::post('/feed/posts/{id}/save', [FeedController::class, 'toggleSave']);
    Route::post('/feed/posts/{id}/comments', [FeedController::class, 'comment']);
    Route::post('/feed/posts/{id}/unshare', [FeedController::class, 'unshare']);

    Route::get('/socials', [SocialController::class, 'index']);
    Route::post('/socials', [SocialController::class, 'store']);
    Route::put('/socials/{id}', [SocialController::class, 'update']);
    Route::delete('/socials/{id}', [SocialController::class, 'destroy']);

    // Perfil
    Route::get('/perfil/me', [ProfileController::class, 'show']);       // ← única, usa ProfileService con caché
    Route::get('/perfil/overview', [ProfileController::class, 'overview']);
    Route::post('/perfil/completar', [ProfileController::class, 'completar']);
    Route::post('/perfil/actualizar', [ProfileController::class, 'storeOrUpdate']); // ← ahora con Cloudinary
    Route::post('/perfil/profesional', [ProfileController::class, 'crearPerfilProfesional']);

    // CV Import with groq
    Route::post('/cv/importar', [CvImportController::class, 'import']);
    Route::get('/cv', [CvController::class, 'index']);
    Route::post('/cv', [CvController::class, 'store']);
    Route::get('/cv/{id}', [CvController::class, 'show']);
    Route::put('/cv/{id}', [CvController::class, 'update']);
    Route::delete('/cv/{id}', [CvController::class, 'destroy']);
    Route::patch('/cv/{id}/visible', [CvController::class, 'toggleVisible']);
    Route::post('/cv/{id}/custom-entry', [CvController::class, 'storeCustomEntry']);
    Route::get('/cv/{id}/custom-entries', [CvController::class, 'getCustomEntries']);
    Route::delete('/cv/{id}/custom-entry/{entryId}', [CvController::class, 'deleteCustomEntry']);
    // Temporal — borrar después del sprint
    Route::post('/test-cloudinary', [MeController::class, 'testCloudinary']);

    // Actualizar perfil existente (POST)
    Route::post('/perfil/actualizar', [ProfileController::class, 'storeOrUpdate']);
    // Si tus compañeros usan esta otra para el perfil profesional:
    Route::post('/perfil/profesional', [ProfileController::class, 'crearPerfilProfesional']);

    // visibilidad 
    Route::get('profile/visibility',  [VisibilityController::class, 'show']);
    Route::put('profile/visibility',  [VisibilityController::class, 'update']);

    // Company routes
    Route::middleware('auth:sanctum')->group(function () {
    Route::post('/company', [CompanyController::class, 'store']);
    Route::put('/company', [CompanyController::class, 'update']);
    });

    Route::get('/offers/mine', [OfferController::class, 'mine']);
    Route::apiResource('offers', OfferController::class);

    Route::post('/postulations', [PostulationController::class, 'store']);
    Route::get('/offers/{id}/postulations', [PostulationController::class, 'index']);

    // Admin routes
    Route::prefix('admin')->group(function () {
        Route::post('users', [AgregarAdminController::class, 'store']);
        Route::get('historial/usuarios', [HistorialController::class, 'buscarUsuarios']);
        Route::get('historial/usuarios/{id}', [HistorialController::class, 'datosUsuario']);
        Route::get('historial/usuarios/{id}/logs', [HistorialController::class, 'logsUsuario']);
        Route::get('definition/areas', [DefinitionCatalogController::class, 'areas']);
        Route::get('definition/countries', [DefinitionCatalogController::class, 'countries']);
        Route::get('definition/{catalog}', [DefinitionCatalogController::class, 'index']);
        Route::post('definition/{catalog}', [DefinitionCatalogController::class, 'store']);
        });
    
});
Route::get('/feed/posts', [FeedController::class, 'index']);
Route::get('/company/{id}', [CompanyController::class, 'show']);
