<?php

namespace App\Interfaces;

interface DBHandlerInterface
{
    public function __construct(\PDO $pdo);
    public function addUrl(string $url);
    public function getUrlIdByName(string $url);
    public function getUrl(int $id);
    public function addCheck(int $id, array $checkData = []);
    public function getChecks(int $id);
    public function getUrlsWithLastCheck();
}
