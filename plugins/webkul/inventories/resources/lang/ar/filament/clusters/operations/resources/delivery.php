<?php

return [
    'navigation' => [
        'title' => 'التسليمات',
        'group' => 'التحويلات',
    ],

    'global-search' => [
        'partner' => 'الشريك',
        'origin'  => 'المصدر',
    ],

    'table' => [
        'actions' => [
            'edit' => [
                'notification' => [
                    'title' => 'تم تحديث التسليم',
                    'body'  => 'تم تحديث التسليم بنجاح.',
                ],
            ],

            'delete' => [
                'notification' => [
                    'success' => [
                        'title' => 'تم حذف التسليم',
                        'body'  => 'تم حذف التسليم بنجاح.',
                    ],

                    'error' => [
                        'title' => 'تعذر حذف التسليم',
                        'body'  => 'لا يمكن حذف التسليم لأنه قيد الاستخدام حالياً.',
                    ],
                ],
            ],
        ],

        'bulk-actions' => [
            'delete' => [
                'notification' => [
                    'success' => [
                        'title' => 'تم حذف التسليمات',
                        'body'  => 'تم حذف التسليمات بنجاح.',
                    ],

                    'error' => [
                        'title' => 'تعذر حذف التسليمات',
                        'body'  => 'لا يمكن حذف التسليمات لأنها قيد الاستخدام حالياً.',
                    ],
                ],
            ],
        ],
    ],
];
