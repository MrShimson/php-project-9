<?php

namespace App;

use DiDom\Document;

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
        } catch (\GuzzleHttp\Exception\RequestException $error) {
            $response = $error->getResponse();

            if (empty($response)) {
                $message = $error->getMessage();
                $formatted = "Непредвиденная ошибка при проверке: {$message}";

                return [
                    'error' => $formatted,
                    'data' => null
                ];
            }

            $message = 'Проверка была выполнена успешно, но сервер ответил с ошибкой';
        }

        $status = $response->getStatusCode();
        $data = [
            'status_code' => $status
        ];

        $html = new Document($response->getBody()->getContents());
        $tags = [
            'h1' => 'h1::text',
            'title' => 'title::text',
            'description' => 'meta[name="description"]::attr(content)'
        ];

        foreach ($tags as $tagName => $tagScheme) {
            if ($html->has($tagScheme)) {
                $data[$tagName] = $html->first($tagScheme);
            } else {
                continue;
            }
        }

        return [
            'error' => isset($message) ? $message : null,
            'data' => $data
        ];
    }
}
