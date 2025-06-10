<?php

namespace App\Models;

use Google\Client;

class GoogleClient
{
    protected $client;

    function __construct()
    {
        $this->client = new Client();
        $this->client->setApplicationName(config('app.name'));
        $this->client->setScopes([
            'https://www.googleapis.com/auth/drive',
            'https://www.googleapis.com/auth/spreadsheets'
        ]);
        $this->client->setAuthConfig(storage_path('app/credentials/' . config('app.google_credentials_filename')));
        $this->client->setAccessType('offline');
    }
}
