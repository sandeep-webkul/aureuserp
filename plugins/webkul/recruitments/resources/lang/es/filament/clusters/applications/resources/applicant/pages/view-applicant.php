<?php

return [
    'header-actions' => [
        'delete' => [
            'notification' => [
                'title' => 'Candidato eliminado',
                'body'  => 'El candidato se ha eliminado correctamente.',
            ],
        ],

        'refuse' => [
            'notification' => [
                'title' => 'Candidato rechazado',
                'body'  => 'El candidato se ha rechazado correctamente.',
            ],
        ],

        'reopen' => [
            'notification' => [
                'title' => 'Candidato reabierto',
                'body'  => 'El candidato se ha reabierto correctamente.',
            ],
        ],

        'state' => [
            'notification' => [
                'title' => 'Estado del candidato actualizado',
                'body'  => 'El estado del candidato se ha actualizado correctamente.',
            ],
        ],
    ],

    'mail' => [
        'application-refused' => [
            'subject' => 'Tu candidatura: :application',
        ],
    ],
];
