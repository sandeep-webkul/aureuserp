<?php

return [

    'navigation' => [
        'group' => 'Plugins',
    ],

    'title' => 'Plugin',

    'table' => [
        'version'             => 'Versión',
        'dependencies'        => 'Dependencias',
        'dependencies_suffix' => ' Dependencias',
    ],

    'status' => [
        'installed'     => 'Instalado',
        'not_installed' => 'No instalado',
    ],

    'filters' => [
        'installation_status' => 'Estado de instalación',
        'all_plugins'         => 'Todos los plugins',
        'installed'           => 'Instalado',
        'not_installed'       => 'No instalado',
        'active_status'       => 'Estado activo',
        'author'              => 'Autor',
        'webkul'              => 'Webkul',
        'third_party'         => 'Terceros',
    ],

    'actions' => [
        'install' => [
            'title'       => 'Instalar',
            'heading'     => 'Instalar plugin :name',
            'description' => "¿Está seguro de que desea instalar el plugin ':name'? Esto ejecutará migraciones y seeders.",
            'submit'      => 'Instalar plugin',
        ],
        'uninstall' => [
            'title'      => 'Desinstalar',
            'heading'    => 'Desinstalar plugin',
            'submit'     => 'Desinstalar plugin',
        ],
    ],

    'notifications' => [
        'installed' => [
            'title' => 'Plugin instalado correctamente',
            'body'  => "El plugin ':name' se ha instalado.",
        ],
        'installed-failed' => [
            'title' => 'Error en la instalación',
        ],
        'uninstalled' => [
            'title' => 'Plugin desinstalado correctamente',
            'body'  => "El plugin ':name' se ha desinstalado.",
        ],
        'uninstalled-failed' => [
            'title' => 'Error en la desinstalación',
        ],
    ],

    'infolist' => [
        'section'  => [
            'plugin'       => ' Información del plugin',
            'dependencies' => 'Dependencias',
        ],
        'name'         => 'Nombre del plugin',
        'version'      => 'Versión',
        'dependencies' => 'Plugins requeridos',
        'dependents'   => 'Plugins que dependen de este',
        'is_installed' => 'Estado de instalación',
        'license'      => 'Licencia',
        'summary'      => 'Descripción',

        'dependencies-repeater' => [
            'title'        => 'Plugins requeridos',
            'name'         => 'Nombre del plugin',
            'is_installed' => 'Instalado',
            'placeholder'  => 'No se requieren dependencias',
        ],

        'dependents-repeater' => [
            'title'        => 'Plugins que dependen de este',
            'name'         => 'Nombre del plugin',
            'is_installed' => 'Instalado',
            'placeholder'  => 'Sin dependientes',
        ],

    ],

];
