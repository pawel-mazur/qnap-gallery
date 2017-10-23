<?php

use Silex\Application;
use Silex\Provider\AssetServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\HttpFragmentServiceProvider;

$app = new Application();

require __DIR__.'/../config/parameters.php';

$app->register(new ServiceControllerServiceProvider());
$app->register(new AssetServiceProvider());
$app->register(new TwigServiceProvider());
$app->register(new HttpFragmentServiceProvider());
$app['twig'] = $app->extend('twig', function ($twig, $app) {
    // add custom globals, filters, tags, ...

    return $twig;
});

$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => [
        'driver'   => $app['database.driver'],
        'host'     => $app['database.host'],
        'port'     => $app['database.port'],
        'user'     => $app['database.user'],
        'password' => $app['database.password'],
        'dbname'   => $app['database.dbname'],
    ]
));

return $app;
