<?php

declare(strict_types=1);

use App\Http\BuiltInHttpFetcher;
use App\Weather\OpenMeteoClient;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();

$app->addErrorMiddleware(true, true, true);

$client = new OpenMeteoClient(new BuiltInHttpFetcher());

$app->get('/', function (Request $request, Response $response): Response {
    $response->getBody()->write(
        (string) json_encode(['ok' => true, 'endpoints' => ['/weather']])
    );

    return $response->withHeader('Content-Type', 'application/json');
});

$app->get('/weather', function (Request $request, Response $response) use ($client): Response {
    $weather = $client->volosCurrentWeather();

    $response->getBody()->write(
        (string) json_encode($weather, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES)
    );

    return $response->withHeader('Content-Type', 'application/json');
});

$app->run();
