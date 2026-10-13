<?php

return [
    'form' => [
        'fields' => [
            'web'     => 'Web',
            'sanctum' => 'Sanctum',
        ],
    ],

    'notification' => [
        'system-role-delete' => [
            'title' => 'No se puede eliminar el rol del sistema',
            'body'  => 'Este es un rol del sistema y no se puede eliminar.',
        ],
    ],

    'table' => [
        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'Rol actualizado',
                    'body'  => 'El rol se ha actualizado correctamente.',
                ],
            ],
            'delete' => [
                'notification' => [
                    'title' => 'Rol eliminado',
                    'body'  => 'El rol se ha eliminado correctamente.',
                ],
            ],
        ],

        'bulk-actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'Roles eliminados',
                    'body'  => 'Los roles seleccionados se han eliminado correctamente.',
                ],
            ],
        ],
    ],
];
