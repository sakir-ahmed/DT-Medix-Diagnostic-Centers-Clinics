<?php

declare(strict_types=1);

return [
    'description' => 'Every view of a patient record, report or history.',
    'search' => 'Patient ID',
    'all_actions' => 'All actions',
    'empty' => 'No access recorded',
    'time' => 'Time',
    'user' => 'User',
    'action' => 'Action',
    'channel' => 'Channel',
    'ip' => 'IP address',
    'actions' => [
        'view' => 'Viewed record',
        'update' => 'Updated record',
        'photo' => 'Viewed photo',
        'report' => 'Viewed report',
        'history' => 'Viewed history',
        'download' => 'Downloaded report',
    ],
    'channels' => [
        'staff' => 'Staff',
        'api' => 'API',
        'portal' => 'Patient portal',
        'link' => 'Secure link',
    ],
];
