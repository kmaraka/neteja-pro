<?php

return [
    // Catalogue des permissions : « module.action ». Le seeder les crée à partir de ce tableau.
    'permissions' => [
        'users' => ['view', 'create', 'update', 'delete'],
        'roles' => ['view', 'manage'],
        'audit' => ['view'],
        'catalog' => ['view', 'create', 'update'],
        'promotions' => ['view', 'create', 'update'],
        'orders' => ['view', 'update'],
    ],

    // Textes affichés dans l'écran de gestion des rôles.
    'module_labels' => [
        'users' => 'Utilisateurs',
        'roles' => 'Rôles et permissions',
        'audit' => "Journal d'audit",
        'catalog' => 'Catalogue et boutique',
        'promotions' => 'Promotions et codes promo',
        'orders' => 'Commandes',
    ],

    'action_labels' => [
        'view' => 'Consulter',
        'create' => 'Créer',
        'update' => 'Modifier',
        'delete' => 'Anonymiser',
        'manage' => 'Gérer',
    ],
];
