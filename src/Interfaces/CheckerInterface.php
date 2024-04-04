<?php

namespace App\Interfaces;

interface CheckerInterface
{
    public function __construct(\GuzzleHttp\Client $client);
    public function checkUrl(string $url);
}
