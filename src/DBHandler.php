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

    public function getUrlIdsWithNames(): array
    {
        $query = "SELECT id, name FROM urls ORDER BY id DESC";

        return $this->pdo->query($query, \PDO::FETCH_ASSOC)->fetchAll();
    }

    private function buildCheckQuery(array $data): string
    {
        $columns = array_keys($data);
        $format = "INSERT INTO checks (%s) VALUES (%s)";

        $columnsString = '';
        $valuesString = '';
        $lastKey = array_key_last($columns);

        foreach ($columns as $key => $value) {
            if ($key === $lastKey) {
                $columnsString .= "{$value}";
                $valuesString .= ":{$value}";
            } else {
                $columnsString .= "{$value}, ";
                $valuesString .= ":{$value}, ";
            }
        }

        return sprintf($format, $columnsString, $valuesString);
    }

    public function addCheck(int $id, array $checkData = []): void
    {
        // Собирает массив $row со всеми данными проверки
        // (где ключи - названия столбцов, а значения - результаты проверки),
        // создает из ключей массива $row строку запроса и заносит все данные
        // в таблицу checks
        $time = Carbon::now();
        $row = array_merge(['url_id' => $id], $checkData, ['created_at' => $time]);

        $query = $this->buildCheckQuery($row);

        $statement = $this->pdo->prepare($query);

        foreach ($row as $key => $value) {
            $statement->bindValue(":{$key}", $value);
        }

        $statement->execute();
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
        $table = $this->getUrlIdsWithNames();

        $callback = function ($row) {
            $query = "SELECT
            urls.id,
            urls.name,
            checks.created_at,
            checks.status_code
            FROM urls JOIN checks ON urls.id=checks.url_id
            WHERE urls.id=:id
            ORDER BY checks.created_at DESC LIMIT 1";

            $statement = $this->pdo->prepare($query);
            $statement->bindValue(':id', $row['id']);
            $statement->execute();

            $result = $statement->fetch(\PDO::FETCH_ASSOC);

            if ($result === false) {
                return $row;
            }

            return $result;
        };

        return array_map($callback, $table);
    }
}
