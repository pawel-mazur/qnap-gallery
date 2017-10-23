<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

//Request::setTrustedProxies(array('127.0.0.1'));

$app->get('/', function () use ($app) {

    /** @var \Doctrine\DBAL\Connection $db */
    $db = $app['db'];

    $photos = $db->fetchAll('
      SELECT * FROM pictureTable pt 
      INNER JOIN dirTable dt ON dt.iDirId = pt.iDirId
      ORDER BY RAND() LIMIT 1
    ');

    return $app['twig']->render('index.html.twig', array('photo' => $photos[0]));
})
->bind('homepage')
;

$app->get('/image/{image}', function ($image) use ($app) {

    /** @var \Doctrine\DBAL\Connection $db */
    $db = $app['db'];

    $photos = $db->fetchAll('
      SELECT * FROM pictureTable pt 
      INNER JOIN dirTable dt ON dt.iDirId = pt.iDirId
      WHERE iPictureId = ? 
    ', [$image]);

    $file = sprintf('%s%s%s%s%s', $app['basePath'], $photos[0]['cFullPath'], '.@__thumb/', 'default', $photos[0]['cFileName'] );

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
