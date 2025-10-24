<?php

return [

    'navigation' => [
        'group' => 'Plugin Manager',
    ],

    'title' => 'Plugin',

    'table' => [
        'version'             => 'Version',
        'dependencies'        => 'Dependencies',
        'dependencies_suffix' => 'Dependencies',
    ],

    'status' => [
        'installed'     => 'Installed',
        'not_installed' => 'Not Installed',
    ],

    'filters' => [
        'installation_status' => 'Installation Status',
        'all_plugins'         => 'All Plugins',
        'installed'           => 'Installed',
        'not_installed'       => 'Not Installed',
        'active_status'       => 'Active Status',
        'author'              => 'Author',
        'webkul'              => 'Webkul',
        'third_party'         => 'Third Party',
    ],

    'actions' => [
        'install' => [
            'heading'     => 'Install Plugin :name',
            'description' => "Are you sure you want to install the ':name' plugin? This will run migrations and seeders.",
            'submit'      => 'Install Plugin',
        ],
        'uninstall' => [
            'heading' => 'Uninstall Plugin',
            'submit'  => 'Uninstall Plugin',
        ],
    ],

    'notifications' => [
        'installed' => [
            'title' => 'Plugin Installed Successfully',
            'body'  => "The ':name' plugin has been installed.",
        ],
        'uninstalled' => [
            'title' => 'Plugin Uninstalled Successfully',
            'body'  => "The ':name' plugin has been uninstalled.",
        ],
    ],

    'infolist' => [
        'name'         => 'Plugin Name',
        'version'      => 'Version',
        'dependencies' => 'Required Plugins',
        'dependents'   => 'Plugins That Depend On This',
        'is_installed' => 'Installation Status',
        'is_active'    => 'Active Status',

    ],

];
