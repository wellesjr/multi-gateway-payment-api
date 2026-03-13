<?php

use App\Gateways\Gateway1Client;
use App\Gateways\Gateway2Client;

return [
    'gateway_clients' => [
        Gateway1Client::class,
        Gateway2Client::class,
    ],
];
