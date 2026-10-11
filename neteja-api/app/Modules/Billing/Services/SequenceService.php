<?php

namespace App\Modules\Billing\Services;

use Illuminate\Support\Facades\DB;

class SequenceService
{
    /** next('invoice', 'NETEJA') donne NETEJA-2026-000001, puis NETEJA-2026-000002... */
    public function next(string $name, string $prefix): string
    {
        return DB::transaction(function () use ($name, $prefix) {
            $year = now()->year;

            // Crée la ligne de l'année si elle n'existe pas (sans erreur si déjà là).
            DB::table('sequences')->insertOrIgnore([
                'name' => $name, 'year' => $year, 'last_number' => 0,
            ]);

            // Verrouille la ligne : les autres requêtes attendent ici.
            $seq = DB::table('sequences')
                ->where('name', $name)->where('year', $year)
                ->lockForUpdate()->first();

            $number = $seq->last_number + 1;

            DB::table('sequences')->where('id', $seq->id)->update(['last_number' => $number]);

            return sprintf('%s-%d-%06d', $prefix, $year, $number);
        });
    }
}
