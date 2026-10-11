<?php

namespace App\Modules\Auth\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    // Le modèle de Spatie autorise déjà le remplissage de tous les champs ($guarded = []).

    protected function casts(): array
    {
        return ['hierarchy_level' => 'integer', 'is_system' => 'boolean'];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_role_id');
    }

    protected static function booted(): void
    {
        static::deleting(function (Role $role) {
            // Un rôle système (admin, client...) ne se supprime jamais : le code le référence.
            if ($role->is_system) {
                return false; // false = l'événement annule la suppression
            }

            // Pas de clé étrangère en base : on détache les rôles enfants.
            static::where('parent_role_id', $role->id)->update(['parent_role_id' => null]);
        });
    }
}
