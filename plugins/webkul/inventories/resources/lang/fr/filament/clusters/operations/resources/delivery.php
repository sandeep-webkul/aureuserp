<?php

return [
    'navigation' => [
        'title' => 'Livraisons',
        'group' => 'Transferts',
    ],

    'global-search' => [
        'partner' => 'Partenaire',
        'origin'  => 'Origine',
    ],

    'table' => [
        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'Livraison mise à jour',
                    'body'  => 'La livraison a été mise à jour avec succès.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'success' => [
                        'title' => 'Livraison supprimée',
                        'body'  => 'La livraison a été supprimée avec succès.',
                    ],

                    'error' => [
                        'title' => 'La livraison n\'a pas pu être supprimée',
                        'body'  => 'La livraison ne peut pas être supprimée car elle est actuellement utilisée.',
                    ],
                ],
            ],
        ],

        'bulk-actions' => [
            'delete' => [
                'notification' => [
                    'success' => [
                        'title' => 'Livraisons supprimées',
                        'body'  => 'Les livraisons ont été supprimées avec succès.',
                    ],

                    'error' => [
                        'title' => 'Les livraisons n\'ont pas pu être supprimées',
                        'body'  => 'Les livraisons ne peuvent pas être supprimées car elles sont actuellement utilisées.',
                    ],
                ],
            ],
        ],
    ],
];
