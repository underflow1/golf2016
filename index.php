<?php

function russian_date($rd){
    $date=explode("-", $rd);
    switch ($date[1]){
        case 1: $m='января'; break;
        case 2: $m='февраля'; break;
        case 3: $m='марта'; break;
        case 4: $m='апреля'; break;
        case 5: $m='мая'; break;
        case 6: $m='июня'; break;
        case 7: $m='июля'; break;
        case 8: $m='августа'; break;
        case 9: $m='сентября'; break;
        case 10: $m='октября'; break;
        case 11: $m='ноября'; break;
        case 12: $m='декабря'; break;
    }
    return $date[2].' '.$m.' '.$date[0];
}

require_once __DIR__.'/silex/vendor/autoload.php';

$app = new Silex\Application();

$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => array(
        'driver'   => 'pdo_mysql',
        'dbname' => 'golf',
        'host' => 'localhost',
        'user' => 'golf',
        'password' => '123qweQWE',
        'charset' => 'utf8'
    ),
));

$app->get('/', function() use($app) {
    return file_get_contents('sdf.html');
});


$app->get('/country/{sex}/{country}', function($sex, $country) use ($app) {
    $tabl = "rank".$sex;
    $sql = "
SELECT if(olympicpos <= 60, olympicpos, '') as olympicpos, name, country, worldpos
FROM $tabl
WHERE country = '$country' AND rankdate = (SELECT MAX(rankdate) FROM $tabl)
GROUP BY name
ORDER BY worldpos
";
    $post = $app['db']->fetchAll($sql);
    return json_encode(array(
        "success" => true,
        "data" => $post
    ));
});

$app->get('/cntr/{sex}/{country}', function($sex, $country) use ($app) {
    $tabl = "rank".$sex;
    $sql = "
SELECT  if(cur.olympicpos <= 60, cur.olympicpos, '') as olympicposs
       ,cur.name, cur.country
       ,cur.rankdate as currankdate
       ,pr.rankdate as prevrankdate
       ,pr.olympicpos as oldolymp, pr.name, pr.country, pr.rankdate as prevrankdate
       ,if ((pr.olympicpos is not null AND cur.olympicpos <= 60), pr.olympicpos - cur.olympicpos, '') as ch
       ,if ((pr.worldpos is not null), pr.worldpos - cur.worldpos, null) as chwrld
       ,cur.worldpos
      ,CASE
          WHEN (pr.olympicpos <= 60 AND cur.olympicpos <= 60) AND (pr.olympicpos - cur.olympicpos) > 0 then 'up'
          WHEN (pr.olympicpos <= 60 AND cur.olympicpos <= 60) AND (pr.olympicpos - cur.olympicpos) < 0 then 'down'
          WHEN (pr.olympicpos is null) AND cur.olympicpos <= 60 then 'up'
          ELSE 'same'
      END as dir
      ,CASE
          WHEN (pr.worldpos is not null) AND (pr.worldpos - cur.worldpos) > 0 then 'up'
          WHEN (pr.worldpos is not null) AND (pr.worldpos - cur.worldpos) < 0 then 'down'
          WHEN (pr.worldpos is null) AND cur.worldpos > 0 then 'up'
          ELSE 'same'
      END as dirwrld
      ,CASE
        WHEN cur.olympicpos <= 60 then 'black'
        ELSE 'gray'
      END as color
      ,CASE
        WHEN cur.olympicpos = 61 then '2px solid black'
        ELSE ''
      END as solid
      , '$sex' as sex
FROM $tabl AS cur

INNER JOIN (
    SELECT olympicpos, worldpos, name, country, rankdate
    from $tabl
    WHERE rankdate = (SELECT MAX(rankdate) as prevrankdate
                      FROM $tabl
                      WHERE rankdate <> (SELECT MAX(rankdate) from $tabl))
    ) AS pr
    ON pr.name = cur.name AND pr.country = cur.country

WHERE cur.rankdate=(SELECT MAX(rankdate) from $tabl) AND cur.country = '$country'
ORDER BY cur.worldpos";
    $post = $app['db']->fetchAll($sql);
    return json_encode(array(
        "success" => true,
        "data" => $post
    ));
});




$app->get('/rankwomen', function() use ($app) {
    $sql = "
SELECT  cur.olympicpos, cur.name, cur.country, cur.rankdate as currankdate,
        pr.olympicpos as oldolymp, pr.name, pr.country, pr.rankdate as prevrankdate
       ,if ((pr.olympicpos is not null), pr.olympicpos - cur.olympicpos, 60-cur.olympicpos) as ch
       ,if ((pr.worldpos is not null), pr.worldpos - cur.worldpos, null) as chwrld
       ,cur.worldpos
      ,CASE
          WHEN (pr.olympicpos is not null) AND (pr.olympicpos - cur.olympicpos) > 0 then 'up'
          WHEN (pr.olympicpos is not null) AND (pr.olympicpos - cur.olympicpos) < 0 then 'down'
          WHEN (pr.olympicpos is null) AND cur.olympicpos > 0 then 'up'
          ELSE 'same'
      END as dir
      ,CASE
          WHEN (pr.worldpos is not null) AND (pr.worldpos - cur.worldpos) > 0 then 'up'
          WHEN (pr.worldpos is not null) AND (pr.worldpos - cur.worldpos) < 0 then 'down'
          WHEN (pr.worldpos is null) AND cur.worldpos > 0 then 'up'
          ELSE 'same'
      END as dirwrld
      ,CASE
        WHEN cur.olympicpos <= 60 then 'black'
        ELSE 'gray'
      END as color
      ,CASE
        WHEN cur.olympicpos = 61 then '2px solid black'
        ELSE ''
      END as solid
      , 'women' as sex
FROM rankwomen AS cur

LEFT JOIN (
    SELECT olympicpos, worldpos, name, country, rankdate
    from rankwomen
    WHERE rankdate = (SELECT MAX(rankdate) as prevrankdate
                      FROM rankwomen
                      WHERE rankdate <> (SELECT MAX(rankdate) from rankwomen))
    ) AS pr
    ON pr.name = cur.name AND pr.country = cur.country

WHERE cur.rankdate=(SELECT MAX(rankdate) from rankwomen) AND cur.olympicpos is not null
ORDER BY cur.olympicpos
";

    $post = $app['db']->fetchAll($sql);
    return json_encode(array(
        "success" => true,
        "data" => $post
    ));
});

$app->get('/rankmen', function() use ($app) {
    $sql = "
SELECT  cur.olympicpos, cur.name, cur.country, cur.rankdate as currankdate,
        pr.olympicpos as oldolymp, pr.name, pr.country, pr.rankdate as prevrankdate
       ,if ((pr.olympicpos is not null), pr.olympicpos - cur.olympicpos, 60-cur.olympicpos) as ch
       ,if ((pr.worldpos is not null), pr.worldpos - cur.worldpos, null) as chwrld
       ,cur.worldpos
      ,CASE
          WHEN (pr.olympicpos - cur.olympicpos) > 0 then 'up'
          WHEN (pr.olympicpos - cur.olympicpos) < 0 then 'down'
          WHEN (pr.olympicpos is null) AND cur.olympicpos > 0 then 'up'
          ELSE 'same'
      END as dir
      ,CASE
          WHEN (pr.worldpos is not null) AND (pr.worldpos - cur.worldpos) > 0 then 'up'
          WHEN (pr.worldpos is not null) AND (pr.worldpos - cur.worldpos) < 0 then 'down'
          WHEN (pr.worldpos is null) AND cur.worldpos > 0 then 'up'
          ELSE 'same'
      END as dirwrld
      ,CASE
        WHEN cur.olympicpos <= 60 then 'black'
        ELSE 'gray'
      END as color
      ,CASE
        WHEN cur.olympicpos = 61 then '2px solid black'
        ELSE ''
      END as solid
      , 'men' as sex
FROM rankmen AS cur

LEFT JOIN (
    SELECT olympicpos, worldpos, name, country, rankdate
    from rankmen
    WHERE rankdate = (SELECT MAX(rankdate) as prevrankdate
                      FROM rankmen
                      WHERE rankdate <> (SELECT MAX(rankdate) from rankmen))
    ) AS pr
    ON pr.name = cur.name AND pr.country = cur.country

WHERE cur.rankdate=(SELECT MAX(rankdate) from rankmen) AND cur.olympicpos is not null
ORDER BY cur.olympicpos
";
    $post = $app['db']->fetchAll($sql);
    return json_encode(array(
        "success" => true,
        "data" => $post
    ));
});

$app->get('/mendate', function() use ($app) {
    $sql = "SELECT MAX(rankdate) as rankdate FROM rankmen";
    $post = $app['db']->fetchAssoc($sql);
    $asdfds = russian_date($post['rankdate']);
    $post['rankdate'] = russian_date($post['rankdate']);
    return json_encode(array(
        "success" => true,
        "data" =>   $post
    ));
});

$app->get('/womendate', function() use ($app) {
    $sql = "SELECT MAX(rankdate) as rankdate FROM rankwomen";
    $post = $app['db']->fetchAssoc($sql);
    $asdfds = russian_date($post['rankdate']);
    $post['rankdate'] = russian_date($post['rankdate']);
    return json_encode(array(
        "success" => true,
        "data" =>   $post
    ));
});

$app->run();

?>