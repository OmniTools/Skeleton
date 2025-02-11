<?php

return [
    'Database' => [
        'Dbms' => 'Mysql',
        'Host' => 'localhost',
        'User' => 'root',
        'Password' => 'root',
        'Schema' => 'webapp-database',
    ],
    'PasswordSalt' => 'xxxx',
    'Platform' => [
        'Environment' => 'Dev',
        'DevMode' => true,
        'Title' => 'OmniTools Application Skeleton',
        'BaseUrl' => 'https://xxxx:8890/',
        'Links' => [
            'Privacy' => null,
            'Imprint' => null,
            'GeneralTerms' => null,
        ],
        'Api' => [
            'HashKey' => 'xxxx',
        ],
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
    '_thumbnails' => [
        // 'webp' => true,
        'imagemagick' => [
            // 'path' => '/usr/local/bin/convert',
            'path' => '/opt/homebrew/bin/convert',
        ],
    ],
    'Mail' => [
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
    'ErrorLogging' => [
        '_Sentry' => [
            'dsn' => 'xxxxxx',
            'environment' => 'development',
        ],
    ],
] ;
