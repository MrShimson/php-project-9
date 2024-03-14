<?php

namespace App;

use Carbon\Carbon;

class DBHandler implements DBHandlerInterface
{
    private \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function addUrl(string $url): void
    {
        // Добавляет запись о URL в таблицу urls
        $query = "INSERT INTO urls (name, created_at) VALUES (:name, :time)";
        $time = Carbon::now();

        $statement = $this->pdo->prepare($query);
        $statement->bindValue(':name', $url);
        $statement->bindValue(':time', $time);
        $statement->execute();
    }

    public function getUrlIdByName(string $url): int|null
    {
        // Возвращает ID записи по заданному URL из таблицы urls
        $query = "SELECT id FROM urls WHERE name=:name";

        $statement = $this->pdo->prepare($query);
        $statement->bindValue(':name', $url);
        $statement->execute();

        $result = $statement->fetch(\PDO::FETCH_ASSOC);

        return $result === false ? null : $result['id'];
    }

    public function getUrl(int $id): array
    {
        // Возвращает запись по заданному ID из таблицы urls
        $query = "SELECT * FROM urls WHERE id=:id";

        $statement = $this->pdo->prepare($query);
        $statement->bindValue(':id', $id);
        $statement->execute();

        return $statement->fetch(\PDO::FETCH_ASSOC);
    }

    public function addCheck(int $id, array $checkData = []): void
    {
        // Добавляет данные из массива $checkData
        // (где ключи - названия столбцов, а значения - результаты проверки)
        // в таблицу checks с заданным в $id url_id
        if (empty($checkData)) {
            $query = "INSERT INTO checks (url_id) VALUES (:id)";

            $statement = $this->pdo->prepare($query);
            $statement->bindValue(':id', $id);
            $statement->execute();
        } else {
            // *Добавляет данные проверки?!
        }
    }

    public function getChecks(int $id): array
    {
        // Возвращает все проверки для URL с заданным ID
        $query = "SELECT * FROM checks WHERE url_id=:id AND created_at IS NOT NULL ORDER BY id DESC";

        $statement = $this->pdo->prepare($query);
        $statement->bindValue(':id', $id);
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getUrlsWithLastCheck(): array
    {
        // Выборка последних проверок для каждого URL из
        // объединения двух таблиц
        $query = "SELECT DISTINCT ON (urls.id)
        urls.id,
        urls.name,
        checks.status_code,
        checks.created_at
        FROM urls JOIN checks ON urls.id=checks.url_id
        ORDER BY urls.id DESC, checks.created_at DESC";

        return $this->pdo->query($query, \PDO::FETCH_ASSOC)->fetchAll();
    }
}
