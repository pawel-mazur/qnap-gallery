<?php

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

//Request::setTrustedProxies(array('127.0.0.1'));

$app->get('/{limit}', function ($limit) use ($app) {

    return $app['twig']->render('index.html.twig', array('limit' => $limit));
})
->bind('homepage')
    ->value('limit', $app['default_photos'])
    ->assert('limit', '\d+')
;

$app->get('/photos/{limit}', function ($limit) use ($app) {

    /** @var Connection $db */
    $db = $app['db'];

    $qb = new QueryBuilder($db);

    $qb
        ->select('iPictureId, cDirName, cFileName, YearMonthDay')
        ->from('pictureTable', 'pt')
        ->innerJoin('pt', 'dirTable', 'dt', 'dt.iDirId = pt.iDirId')
        ->orderBy('RAND()')
        ->setMaxResults($limit);

    $photos = $db->fetchAll($qb->getSQL());

    foreach ($photos as &$photo) {

        /** @var UrlGeneratorInterface $generator */
        $generator = $app['url_generator'];

        $photo['image_url_thumb'] = $generator->generate('image', ['image' => $photo['iPictureId'], 'size' => 's100'], UrlGeneratorInterface::ABSOLUTE_URL);
        $photo['image_url_small'] = $generator->generate('image', ['image' => $photo['iPictureId'], 'size' => 'default'], UrlGeneratorInterface::ABSOLUTE_URL);
        $photo['image_url_big'] = $generator->generate('image', ['image' => $photo['iPictureId'], 'size' => 's800'], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    return $app->json($photos);
})
->bind('photos')
    ->value('limit', $app['default_photos'])
    ->assert('limit', '\d+')
;

$app->get('/image/{image}/{size}', function ($image, $size) use ($app) {

    /** @var Connection $db */
    $db = $app['db'];

    $qb = new QueryBuilder($db);

    $qb
        ->select('*')
        ->from('pictureTable', 'pt')
        ->innerJoin('pt', 'dirTable', 'dt', 'dt.iDirId = pt.iDirId')
        ->setMaxResults(1);

    if ('rand' !== $image) {
        $qb->where('pt.iPictureId = :image');
        $photo = $db->fetchAssoc($qb->getSQL(), ['image' => $image]);
    } else {
        $qb->orderBy('RAND()');
        $photo = $db->fetchAssoc($qb->getSQL());
    }

    if (false === $photo) throw new NotFoundHttpException('Photo not found');

    $file = sprintf('%s%s%s%s%s', $app['basePath'], $photo['cFullPath'], '.@__thumb/', $size, $photo['cFileName'] );

    return $app->sendFile($file);
})
->bind('image')
    ->value('image', 'rand')
    ->assert('image', '^rand$|\d+')
    ->value('size', 'default')
    ->assert('size', '^(default|s100|s800)$')
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
