<?php

namespace App;

class Validator
{
    protected const EMPTY_URL = 'URL не должен быть пустым';
    protected const WRONG_URL = 'Некорректный URL';

    protected static function hasCorrectScheme(string $url): bool
    {
        return str_starts_with($url, 'http://') || str_starts_with($url, 'https://');
    }

    public static function validate(string|null $url): bool|\Exception
    {
        if (empty($url)) {
            throw new \Exception(self::EMPTY_URL);
        }

        if (self::hasCorrectScheme($url)) {
            return true;
        }

        throw new \Exception(self::WRONG_URL);
    }
}
