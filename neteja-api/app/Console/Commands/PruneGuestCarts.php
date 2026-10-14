<?php

namespace App\Console\Commands;

use App\Modules\Ecommerce\Models\Cart;
use Illuminate\Console\Command;

class PruneGuestCarts extends Command
{
    protected $signature = 'shop:prune-guest-carts {--days=30 : Jours d\'inactivité avant suppression}';

    protected $description = 'Supprime les paniers de visiteurs inactifs (jamais ceux des comptes)';

    public function handle(): int
    {
        $deleted = Cart::query()
            ->whereNull('user_id')
            ->where('last_activity_at', '<', now()->subDays((int) $this->option('days')))
            ->delete();

        $this->info("{$deleted} panier(s) de visiteur supprimé(s).");

        return self::SUCCESS;
    }
}
