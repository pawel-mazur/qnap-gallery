<?php

use Doctrine\DBAL\Query\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

//Request::setTrustedProxies(array('127.0.0.1'));

$app->get('/', function () use ($app) {

    /** @var \Doctrine\DBAL\Connection $db */
    $db = $app['db'];

    $qb = new QueryBuilder($db);

    $qb
        ->select('*')
        ->from('pictureTable', 'pt')
        ->innerJoin('pt', 'dirTable', 'dt', 'dt.iDirId = pt.iDirId')
        ->orderBy('RAND()')
        ->setMaxResults(1);

    $photos = $db->fetchAll($qb->getSQL());

    return $app['twig']->render('index.html.twig', array('photo' => $photos[0]));
})
->bind('homepage')
;

$app->get('/photos/{limit}', function ($limit) use ($app) {

    /** @var \Doctrine\DBAL\Connection $db */
    $db = $app['db'];

    $qb = new QueryBuilder($db);

    $qb
        ->select('iPictureId, cDirName, cFileName')
        ->from('pictureTable', 'pt')
        ->innerJoin('pt', 'dirTable', 'dt', 'dt.iDirId = pt.iDirId')
        ->orderBy('RAND()')
        ->setMaxResults($limit);

    $photos = $db->fetchAll($qb->getSQL());

    return $app->json($photos);
})
->bind('photos')
->value('limit', 20)
;

$app->get('/image/{image}', function ($image) use ($app) {

    /** @var \Doctrine\DBAL\Connection $db */
    $db = $app['db'];

    $qb = new QueryBuilder($db);

    $qb
        ->select('*')
        ->from('pictureTable', 'pt')
        ->innerJoin('pt', 'dirTable', 'dt', 'dt.iDirId = pt.iDirId')
        ->where('pt.iPictureId = :image');

    $photos = $db->fetchAssoc($qb->getSQL(), ['image' => $image]);

    $file = sprintf('%s%s%s%s%s', $app['basePath'], $photos['cFullPath'], '.@__thumb/', 'default', $photos['cFileName'] );

    return $app->sendFile($file);
})
->bind('image')
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
