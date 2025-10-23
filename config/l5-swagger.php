<?php

return [
    'default' => 'default',
    'documentations' => [
        'default' => [
            'api' => [
                'title' => 'L5 Swagger UI',
            ],

            'routes' => [
                /*
                 * Route for accessing api documentation interface
                 */
                'api' => 'api/documentation',
            ],
            'paths' => [
                'docs' => storage_path('api-docs'),
                'annotations' => [
                    base_path('app/OpenApi'),
                    base_path('app/Http/Controllers'),
                ],
                'excludes' => [],
            ],
        ],
    ],
    'defaults' => [
        'paths' => [
            // Dónde se guarda el JSON/YAML generado
            'docs'       => storage_path('api-docs'),
            'docs_json'  => 'api-docs.json',
            'docs_yaml'  => 'api-docs.yaml',

            // De dónde leer anotaciones @OA\*
            'annotations'=> base_path('app'),

            // Directorios a excluir del escaneo (opcional, pero NO null)
            'excludes'   => [],

            // <-- ESTA ES LA QUE FALTA
            // Base path de la API. Usá "/" normalmente, o ENV si lo necesitás.
            'base'       => env('L5_SWAGGER_BASE_PATH', '/'),
        ],

        'routes' => [
            'api' => 'api/documentation',
        ],

        'proxy' => [
            'schema'    => env('L5_SWAGGER_PROXY_SCHEMA', null),    // p.ej. "https"
            'host'      => env('L5_SWAGGER_PROXY_HOST', null),      // p.ej. "mi-app.onrender.com"
            'base_path' => env('L5_SWAGGER_PROXY_BASE_PATH', null), // p.ej. "/"
        ],

        'ui' => [
            'display' => [
                'doc_expansion' => 'none',
                'filter'        => true,
            ],
            'authorization' => [
                'persist_authorization' => true,
                // evitar nulls en la vista
                'oauth2' => [
                    'scopes' => [],
                ],
            ],
        ],

        // evitar null en la vista (puede estar vacío si no usás auth)
        'securityDefinitions' => [
            'securitySchemes' => [
                // ejemplo bearer JWT; si no lo usás, dejá el array vacío
                'bearerAuth' => [
                    'type'         => 'http',
                    'scheme'       => 'bearer',
                    'bearerFormat' => 'JWT',
                ],
            ],
        ],
        'security' => [
            ['bearerAuth' => []],
        ],
    ],
];
