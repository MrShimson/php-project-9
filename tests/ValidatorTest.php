<?php

namespace App\Tests;

use PHPUnit\Framework\TestCase;
use App\Validator;

class ValidatorTest extends TestCase
{
    public static function exceptionDataProvider()
    {
        $msgEmpty = 'URL не должен быть пустым';
        $msgWrong = 'Некорректный URL';

        return [
            ['', $msgEmpty],
            ['example.com', $msgWrong],
            ['example.com.http://', $msgWrong],
            ['https:/example.com', $msgWrong]
        ];
    }

    /**
     * @dataProvider exceptionDataProvider
     * @covers App\Validator
     */
    public function testException($url, $message): void
    {
        $this->expectExceptionMessage($message);

        Validator::validate($url);
    }

    /**
     * @covers App\Validator
     */
    public function testValidate(): void
    {
        $url = 'https://example.com';

        $this->assertTrue(Validator::validate($url));
    }
}
