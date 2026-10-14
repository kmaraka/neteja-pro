<?php

return [
    // Délai pour régler une commande payable en ligne, avant annulation automatique.
    'unpaid_order_ttl_minutes' => (int) env('SHOP_UNPAID_ORDER_TTL', 30),
];
