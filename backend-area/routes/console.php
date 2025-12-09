<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// AREA Scheduling : Exécute le moteur AREA TOUTES LES MINUTES
Schedule::command('areas:scheduled')
    ->everyMinute()
    ->withoutOverlapping() // Évite les exécutions parallèles
    ->onFailure(function () {
        \Log::error('[AREA Scheduler] Erreur lors de l\'exécution du scheduler');
    })
    ->onSuccess(function () {
        // Log optionnel du succès (décommentez si souhaité)
        // \Log::info('[AREA Scheduler] Exécution réussie');
    });

/**
 * OPTIONS DE SCHEDULING ALTERNATIVES :
 * 
 * Si vous voulez modifier la fréquence, remplacez ->everyMinute() par :
 * 
 * ->everyFiveMinutes()    // Toutes les 5 minutes
 * ->everyTenMinutes()     // Toutes les 10 minutes
 * ->everyFifteenMinutes() // Toutes les 15 minutes
 * ->everyThirtyMinutes()  // Toutes les 30 minutes
 * ->hourly()              // Chaque heure
 * ->daily()               // Chaque jour (minuit)
 * ->dailyAt('03:00')      // À une heure spécifique
 * ->weekly()              // Chaque semaine
 * ->monthly()             // Chaque mois
 * ->cron('0 3 * * *')     // Format cron personnalisé (3 AM quotidien)
 * 
 * EXEMPLE : Exécuter toutes les 5 minutes
 * Schedule::command('areas:scheduled')->everyFiveMinutes()->withoutOverlapping();
 * 
 * EXEMPLE : Exécuter à 3 heures du matin chaque jour
 * Schedule::command('areas:scheduled')->dailyAt('03:00')->withoutOverlapping();
 */
