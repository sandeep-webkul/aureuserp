<?php

return [
    'title' => 'Barcode',

    'navigation' => [
        'back'   => 'Back',
        'home'   => 'Operations',
        'search' => 'Search...',
    ],

    'dashboard' => [
        'operations' => 'Operations',
        'empty'      => 'No operations available.',
    ],

    'operation-search' => [
        'placeholder'    => 'Scan or enter operation barcode...',
        'open'           => 'Open',
        'not-found'      => 'No active operation found for this barcode.',
        'multiple-found' => ':count matching operations found.',
    ],

    'transfers' => [
        'title' => 'Transfers',
        'empty' => 'No transfers found.',
    ],

    'operation' => [
        'scan'        => 'Scan a product, lot, package, packaging, or transfer',
        'manual-scan' => 'Scan or search by product, reference, barcode...',
        'search'      => 'Search product, reference, barcode...',
        'moves'       => 'Moves',
        'source'      => 'From',
        'available'   => 'Available',
        'discard'     => 'Discard',
        'confirm'     => 'Confirm',
        'counted'     => 'Counted',
        'lot-serial'  => 'Lot/Serial Number',
        'stock-title' => 'Quantity in Stock',
        'empty-moves' => 'No moves found.',
    ],

    'scan' => [
        'empty'                    => 'Enter or scan a barcode.',
        'not-found'                => 'No matching barcode found.',
        'operation-matched'        => 'Transfer matched.',
        'product-not-on-operation' => 'This product is not part of the operation.',
        'package-matched'          => 'Package matched.',
        'move-located'             => 'Move located. Enter the counted quantity.',
        'move-updated'             => 'Move quantity updated.',
        'move-counted'             => 'Move marked as counted.',
    ],

    'actions' => [
        'confirm'                  => 'Confirm',
        'confirm-prompt'           => 'Are you sure you want to',
        'cancel'                   => 'Cancel',
        'check-availability'       => 'Check Availability',
        'validate'                 => 'Validate',
        'return'                   => 'Return',
        'stay-on-transfer'         => 'Stay on Transfer',
        'backorder-title'          => 'Incomplete Transfer',
        'backorder-prompt'         => 'If you validate now, the remaining products will be added to a backorder.',
        'backorder-col-product'    => 'Product',
        'backorder-col-done-todo'  => 'Done / To Do',
        'backorder-col-backorder'  => 'Backorder',
        'completed'                => 'Action completed.',
        'unsupported'              => 'Unsupported barcode action.',
        'no-moves'                 => 'This operation has no moves.',
        'no-return-quantities'     => 'There are no quantities to return.',
    ],
];
