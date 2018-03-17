<?php

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

//Request::setTrustedProxies(array('127.0.0.1'));

$app->get('/', function () use ($app) {

    /** @var Connection $db */
    $db = $app['db'];
    $qb = new QueryBuilder($db);

    $qb
        ->select('pat.iPhotoAlbumId id, pat.cAlbumTitle title', 'pat.iAlbumCover cover')
        ->from('pictureAlbumTable', 'pat');

    $albums = $db->fetchAll($qb->getSQL());

    return $app['twig']->render('albums.html.twig', ['albums' => $albums]);
})
    ->bind('homepage')
;

$app->get('/slideshow/{album}/{limit}', function ($album, $limit) use ($app) {

    return $app['twig']->render('slideshow.html.twig', array('album' => $album, 'limit' => $limit));
})
    ->bind('slideshow')
    ->value('limit', $app['default_photos'])
    ->assert('limit', '\d+')
;

$app->error(function (\Exception $e, Request $request, $code) use ($app) {
    if ($app['debug']) {
        return;
    }

    // 404.html, or 40x.html, or 4xx.html, or error.html
    $templates = array(
        'errors/'.$code.'.html.twig',
        'errors/'.substr($code, 0, 2).'x.html.twig',
        'errors/'.substr($code, 0, 1).'xx.html.twig',
        'errors/default.html.twig',
    );

    return new Response($app['twig']->resolveTemplate($templates)->render(array('code' => $code)), $code);
});
