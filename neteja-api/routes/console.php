<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('dev:links {type? : verify ou reset}', function (?string $type = null) {
    if (! app()->isLocal()) {
        $this->error('Commande réservée au développement.');

        return;
    }

    $log = @file_get_contents(storage_path('logs/laravel.log')) ?: '';

    // On retire seulement les coupures de ligne "quoted-printable" et "=3D" (voir l'étape précédente).
    $log = preg_replace('/=\r?\n/', '', $log);
    $log = str_replace('=3D', '=', $log);

    $pattern = match ($type) {
        'verify' => 'verify',
        'reset' => 'reset-password',
        default => 'verify|reset-password',
    };

    preg_match_all('#https?://[^\s"<>\])]+(?:'.$pattern.')[^\s"<>\])]*#', $log, $matches);

    collect($matches[0])
        ->map(fn (string $link) => html_entity_decode($link))
        ->unique()
        ->values()
        ->take(-3)
        ->each(fn (string $link) => $this->line($link));
})->purpose('Affiche les derniers liens (vérification, réinitialisation) du journal');
