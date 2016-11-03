<?php

use CQRSApi\Action;
use CQRSApi\Factory;

return [
    'dependencies' => [
        'factories' => [
            Action\GetEventsAction::class => Factory\GetEventsFactory::class,
        ],
    ],
    'routes' => [
        [
            'name' => 'notification_token',
            'path' => '/cqrs/event',
            'middleware' => [
                Action\GetEventsAction::class,
            ],
            'allowed_methods' => ['GET'],
        ],
    ]
];
