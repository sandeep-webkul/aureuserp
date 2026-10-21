<?php

return [
    'title' => 'Écritures comptables',

    'navigation' => [
        'title' => 'Écritures comptables',
    ],

    'record-sub-navigation' => [
        'payment' => 'Paiement',
    ],

    'global-search' => [
        'number'   => 'Numéro',
        'partner'  => 'Partenaire',
        'date'     => 'Date de facture',
        'due-date' => 'Date d\'échéance de la facture',
    ],

    'form' => [
        'section' => [
            'general' => [
                'title'  => 'Général',

                'fields' => [
                    'reference'       => 'Référence',
                    'accounting-date' => 'Date comptable',
                    'journal'         => 'Journal',
                ],
            ],
        ],

        'tabs' => [
            'lines' => [
                'title' => 'Éléments comptables',

                'repeater' => [
                    'title'       => 'Éléments',
                    'add-item'    => 'Ajouter un élément',

                    'delete-tax-line' => [
                        'title' => 'La ligne de taxe ne peut pas être supprimée',
                        'body'  => 'Cette ligne a été générée automatiquement à partir d\'une taxe sur une autre ligne et est nécessaire pour le rapport fiscal. Supprimez la taxe de la ligne source pour supprimer cette entrée.',
                    ],

                    'delete-balancing-line' => [
                        'title' => 'La ligne d\'équilibrage ne peut pas être supprimée',
                        'body'  => 'Cette ligne est générée automatiquement pour maintenir l\'écriture équilibrée. Ajustez les valeurs de débit ou de crédit sur les autres lignes pour supprimer le déséquilibre.',
                    ],

                    'delete-auto-line' => [
                        'title' => 'Cette ligne ne peut pas être supprimée',
                        'body'  => 'Cette ligne est générée automatiquement par le système et ne peut pas être supprimée manuellement.',
                    ],

                    'columns' => [
                        'account'                  => 'Compte',
                        'partner'                  => 'Partenaire',
                        'label'                    => 'Libellé',
                        'amount-currency'          => 'Montant (devise)',
                        'currency'                 => 'Devise',
                        'taxes'                    => 'Taxes',
                        'debit'                    => 'Débit',
                        'credit'                   => 'Crédit',
                        'discount-amount-currency' => 'Montant de la remise (devise)',
                    ],

                    'fields' => [
                        'account'                  => 'Compte',
                        'partner'                  => 'Partenaire',
                        'label'                    => 'Libellé',
                        'amount-currency'          => 'Montant (devise)',
                        'currency'                 => 'Devise',
                        'taxes'                    => 'Taxes',
                        'debit'                    => 'Débit',
                        'credit'                   => 'Crédit',
                        'discount-amount-currency' => 'Montant de la remise (devise)',
                    ],
                ],
            ],

            'other-information' => [
                'title'    => 'Autres informations',

                'fields' => [
                    'checked'                 => 'Vérifié',
                    'company'                 => 'Société',
                    'fiscal-position'         => 'Position fiscale',
                    'fiscal-position-tooltip' => 'Les positions fiscales sont utilisées pour adapter les taxes et les comptes en fonction de la localisation du client.',
                ],
            ],

            'term-and-conditions' => [
                'title' => 'Termes et conditions',
            ],
        ],
    ],

    'table' => [
        'total'   => 'Total',
        'columns' => [
            'invoice-date' => 'Date de facture',
            'date'         => 'Date',
            'number'       => 'Numéro',
            'partner'      => 'Partenaire',
            'reference'    => 'Référence',
            'journal'      => 'Journal',
            'company'      => 'Société',
            'total'        => 'Total',
            'state'        => 'État',
            'checked'      => 'Vérifié',
        ],

        'summarizers' => [
            'total' => 'Total',
        ],

        'groups' => [
            'partner'        => 'Partenaire',
            'journal'        => 'Journal',
            'state'          => 'État',
            'payment-method' => 'Mode de paiement',
            'date'           => 'Date',
            'invoice-date'   => 'Date de facture',
            'company'        => 'Société',
        ],

        'filters' => [
            'number'                       => 'Numéro',
            'invoice-partner-display-name' => 'Nom affiché du partenaire de la facture',
            'invoice-date'                 => 'Date de facture',
            'invoice-due-date'             => 'Date d\'échéance de la facture',
            'invoice-origin'               => 'Origine de la facture',
            'reference'                    => 'Référence',
            'created-at'                   => 'Créé le',
            'updated-at'                   => 'Mis à jour le',
        ],

        'actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'Paiement supprimé',
                    'body'  => 'Le paiement a été supprimé avec succès.',
                ],
            ],
        ],

        'bulk-actions' => [
            'delete' => [
                'notification' => [
                    'title' => 'Paiements supprimés',
                    'body'  => 'Les paiements ont été supprimés avec succès.',
                ],
            ],
        ],

        'toolbar-actions' => [
            'export' => [
                'label' => 'Exporter',
            ],
        ],
    ],

    'infolist' => [
        'section' => [
            'general' => [
                'title'   => 'Général',
                'entries' => [
                    'number'          => 'Numéro',
                    'reference'       => 'Référence',
                    'accounting-date' => 'Date comptable',
                    'journal'         => 'Journal',
                ],
            ],
        ],

        'tabs' => [
            'lines' => [
                'title' => 'Éléments comptables',

                'repeater' => [
                    'entries' => [
                        'account'  => 'Compte',
                        'partner'  => 'Partenaire',
                        'label'    => 'Libellé',
                        'currency' => 'Devise',
                        'taxes'    => 'Taxes',
                        'debit'    => 'Débit',
                        'credit'   => 'Crédit',
                    ],
                ],
            ],

            'other-information' => [
                'title' => 'Autres informations',

                'fieldset' => [
                    'accounting' => [
                        'title' => 'Comptabilité',

                        'entries' => [
                            'company'         => 'Société',
                            'fiscal-position' => 'Position fiscale',
                            'checked'         => 'Vérifié',
                        ],
                    ],
                ],
            ],

            'term-and-conditions' => [
                'title' => 'Termes et conditions',
            ],
        ],
    ],

];
