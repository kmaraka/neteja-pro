<?php

return [
    'permissions' => [
        'users' => ['view', 'create', 'update', 'delete'],
        'roles' => ['view', 'manage'],
        'audit' => ['view'],
        'catalog' => ['view', 'create', 'update'],
    ],
    // Textes affichés dans l'écran de gestion des rôles.
    'module_labels' => [
        'users' => 'Utilisateurs',
        'roles' => 'Rôles et permissions',
        'audit' => "Journal d'audit",
        'catalog' => 'Catalogue et boutique',
    ],
    'action_labels' => [
        'view' => 'Consulter',
        'create' => 'Créer',
        'update' => 'Modifier',
        'delete' => 'Anonymiser',
        'manage' => 'Gérer',
    ],
];
