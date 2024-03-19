<?php

namespace App;

class Checker implements CheckerInterface
{
    private \GuzzleHttp\Client $client;

    public function __construct(\GuzzleHttp\Client $client)
    {
        $this->client = $client;
    }

    public function checkUrl(string $url): array
    {
        // Производит проверку и возвращает её данные в виде массива,
        // либо возвращает сообщение об ошибке (с данными или без)
        $client = $this->client;

        try {
            $response = $client->request('GET', $url, ['http_errors' => true]);
        } catch (\GuzzleHttp\Exception\ConnectException) {
            $message = 'Произошла ошибка при проверке, не удалось подключиться';

            return [
                'error' => $message,
                'data' => null
            ];
        } catch (\GuzzleHttp\Exception\ClientException $error) {
            $message = 'Проверка была выполнена успешно, но сервер ответил с ошибкой';
            $data = ['status_code' => $error->getCode()];

            return [
                'error' => $message,
                'data' => $data
            ];
        } catch (\GuzzleHttp\Exception\ServerException $error) {
            $message = 'Проверка была выполнена успешно, но сервер ответил с ошибкой';
            $data = ['status_code' => $error->getCode()];

            return [
                'error' => $message,
                'data' => $data
            ];
        }

        $status = $response->getStatusCode();
        $data = [
            'status_code' => $status
        ];

        return [
            'error' => null,
            'data' => $data
        ];
    }
}
