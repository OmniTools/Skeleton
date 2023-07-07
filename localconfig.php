<?php

return [
    'database' => [
        'dbms' => 'mysql',
        'host' => 'localhost',
        'user' => 'root',
        'password' => 'root',
        'schema' => 'webapp-database',
    ],
    'base' => 'https://xxxx:8890/',
    'passwordSalt' => 'xxxx',
    'Platform' => [
       'Environment' => 'Dev',
       'DevMode' => true,
    ],

    'Google' => [
        'Maps' => [
            'Api' => [
                'Key' => 'xxxx',
            ],
        ],
        'TagManager' => [
          //  'Id' => 'GTM-TNGF7CV',
        ],
    ],
    'thumbnails' => [
        // 'webp' => true,
        'imagemagick' => [
            // 'path' => '/usr/local/bin/convert',
            'path' => '/opt/homebrew/bin/convert',
        ],
    ],
    'mail' => [
        'transport' => 'Localhost',
        '_smtp' => [
            'host' => 'xxxx',
            'username' => 'xxxx',
            'password' => 'xxxx',
        ],
        'defaults' => [
            'from' => [
                'address' => 'xxxx@xxxx.de',
                'name' => 'xxxx',
            ],
        ],
    ],
    'errorlogging' => [
        '_Sentry' => [
            'dsn' => 'xxxxxx',
            'environment' => 'development',
        ],
    ],
] ;
