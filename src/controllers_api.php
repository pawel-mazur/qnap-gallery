<?php

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

//Request::setTrustedProxies(array('127.0.0.1'));

$app->get('/api/photos/{album}/{limit}', function ($album, $limit) use ($app) {

    /** @var Connection $db */
    $db = $app['db'];

    $qb = new QueryBuilder($db);

    $qb
        ->select('iPictureId, cDirName, cFileName, YearMonthDay')
        ->from('pictureAlbumTable', 'pat')
        ->innerJoin('pat', 'pictureAlbumMapping', 'pam', $qb->expr()->eq('pat.iPhotoAlbumId', 'pam.iPhotoAlbumId'))
        ->innerJoin('pam', 'pictureTable', 'pt', 'pam.iMediaId = pt.iPictureId')
        ->innerJoin('pt', 'dirTable', 'dt', 'dt.iDirId = pt.iDirId')
        ->andWhere($qb->expr()->eq('pat.iPhotoAlbumId', $album))
        ->orderBy('RAND()')
        ->setMaxResults($limit)
    ;

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
    ->assert('album', '\d+')
    ->assert('limit', '\d+')
;

$app->get('/api/image/{image}/{size}', function ($image, $size) use ($app) {

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
