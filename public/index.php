<?php

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;
use Valitron\Validator;
use App\Connection;
use App\DBHandler;
use GuzzleHttp\Client;
use App\Checker;

session_start();

$container = new Container();
$container->set('renderer', function () {
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});
$container->set('flash', function () {
    return new \Slim\Flash\Messages();
});

$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);

$app->get('/', function ($request, $response) {
    return $this->get('renderer')->render($response, 'main.index.phtml');
})->setName('main.index');

$app->get('/urls', function ($request, $response) {
    $dbh = new DBHandler(Connection::get()->connect());
    $params['table'] = $dbh->getUrlsWithLastCheck();

    return $this->get('renderer')->render($response, 'urls.index.phtml', $params);
})->setName('urls.index');

$app->get('/urls/{id}', function ($request, $response, array $args) {
    $dbh = new DBHandler(Connection::get()->connect());

    $id = $args['id'];
    $params['url'] = $dbh->getUrl($id);
    $params['checks'] = $dbh->getChecks($id);
    $flash = $this->get('flash')->getMessages();

    if (!empty($flash)) {
        $params['flash'] = $flash;
    }

    return $this->get('renderer')->render($response, 'urls.show.phtml', $params);
})->setName('urls.show');

$router = $app->getRouteCollector()->getRouteParser();

$app->post('/urls', function ($request, $response) use ($router) {
    $url = $request->getParsedBodyParam('url');

    $validator = new Validator($url);
    $validator->rule('required', 'name')
    ->message('URL не должен быть пустым')
    ->rule('lengthMax', 'name', 255)
    ->message('Некорректный URL')
    ->rule('url', 'name')
    ->message('Некорректный URL');

    if ($validator->validate()) {
        // Подготовка имени к добавлению в БД
        $name = $url['name'];
        $len = strlen($name);
        $name = str_ends_with($name, '/') ? substr($name, 0, $len - 1) : $name;
        // Добавление записи в таблицу urls БД (или пропуск добавления записи
        // в случае её существования) и флеш-сообщения
        // и редирект на страницу конкретного URL
        $dbh = new DBHandler(Connection::get()->connect());
        $id = $dbh->getUrlIdByName($name);
        if (empty($id)) {
            $dbh->addUrl($name);
            $id = $dbh->getUrlIdByName($name);
            $this->get('flash')->addMessage('success', 'Страница успешно добавлена');
        } else {
            $this->get('flash')->addMessage('success', 'Страница уже существует');
        }

        return $response->withRedirect($router->urlFor('urls.show', ['id' => $id]));
    } else {
        $errors = $validator->errors();
        $mainError = is_array($errors) ? $errors['name'][0] : 'Непредвиденная ошибка';
        $params = [
            'name' => $url['name'],
            'error' => $mainError
        ];

        return $this->get('renderer')->render($response->withStatus(422), 'main.index.phtml', $params);
    }
})->setName('urls.store');

$app->post('/urls/{url_id}/checks', function ($request, $response, array $args) use ($router) {
    $dbh = new DBHandler(Connection::get()->connect());
    $checker = new Checker(new Client());

    $id = $args['url_id'];
    $url = $dbh->getUrl($id);

    $check = $checker->checkUrl($url['name']);

    if (empty($check['error'])) {
        $dbh->addCheck($id, $check['data']);
        $this->get('flash')->addMessage('success', 'Страница успешно проверена');
    } else {
        if (empty($check['data'])) {
            $this->get('flash')->addMessage('error', $check['error']);
        } else {
            $dbh->addCheck($id, $check['data']);
            $this->get('flash')->addMessage('warning', $check['error']);
        }
    }

    return $response->withRedirect($router->urlFor('urls.show', ['id' => $id]));
})->setName('urls.checks');

$app->run();
