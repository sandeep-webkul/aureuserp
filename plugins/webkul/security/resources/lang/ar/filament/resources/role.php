<?php

return [
    'form' => [
        'fields' => [
            'web'     => 'ويب',
            'sanctum' => 'Sanctum',
        ],
    ],

    'notification' => [
        'system-role-delete' => [
            'title' => 'لا يمكن حذف دور النظام',
            'body'  => 'هذا دور نظام ولا يمكن حذفه.',
        ],
    ],

    'table' => [
        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'تم تحديث الدور',
                    'body'  => 'تم تحديث الدور بنجاح.',
                ],
            ],
            'delete' => [
                'notification' => [
                    'title' => 'تم حذف الدور',
                    'body'  => 'تم حذف الدور بنجاح.',
                ],
            ],
        ],

        'bulk-actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'تم حذف الأدوار',
                    'body'  => 'تم حذف الأدوار المحددة بنجاح.',
                ],
            ],
        ],
    ],
];
