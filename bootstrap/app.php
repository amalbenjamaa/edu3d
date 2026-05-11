<?php

// ================================================================
// INSTRUCTIONS D'INSTALLATION — NE PAS COPIER CE FICHIER TEL QUEL
// ================================================================
//
// Ce fichier montre les modifications à apporter à bootstrap/app.php
// qui existe déjà dans ton projet Laravel.
//
// ① Installer Sanctum :
//    composer require laravel/sanctum
//    php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
//
// ② Dans bootstrap/app.php, trouve la section ->withMiddleware() et ajoute :

/*
->withMiddleware(function (Middleware $middleware) {

    // Enregistrer le middleware CheckRole sous l'alias "role"
    $middleware->alias([
        'role' => \App\Http\Middleware\CheckRole::class,
    ]);

    // Ajouter Sanctum aux middlewares API (si pas déjà présent)
    $middleware->api(prepend: [
        \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
    ]);
})
*/

// ③ Dans config/sanctum.php (après php artisan vendor:publish), vérifier :
//    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', 'localhost')),

// ④ Dans le modèle User.php, vérifier que le trait HasApiTokens est bien présent :
//    use Laravel\Sanctum\HasApiTokens;
//    class User extends Authenticatable {
//        use HasApiTokens, HasFactory, Notifiable;
//    }

// ─── Exemple complet de bootstrap/app.php après modification ─────────────────

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {

        // ── Enregistrer "role" comme alias du middleware CheckRole ──────
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
        ]);

        // ── Sanctum pour les requêtes API stateful (SPA Vue.js) ─────────
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();