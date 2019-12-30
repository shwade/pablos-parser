<?php
header('Content-type: text/html; charset=utf-8');
$wallId = "-85591793";
$count = 1;
$token = "56163a4356163a4356163a43f856772ee45561656163a430c6fefcf0eef0da9069f78b5";
$epochTime = 1546300800;

$stats = [
    'music' => [],
    'tv' => [],
    'games' => [],
    'books' => [],
    'movies' => [],
    'total' => [],
    'misc' => [],
];

for ($offset = 0; $offset < 3; $offset += 1) {
    $api = file_get_contents("http://api.vk.com/method/wall.get?owner_id={$wallId}&count={$count}&offset={$offset}&access_token={$token}&v=5.103");
    $wall = json_decode($api, true);
    foreach ($wall['response']['items'] as $post) {
        if ($post['is_pinned']) {
            continue;
        }
        if ($post['date'] < $epochTime) {
            break;
        }
        $text = $post['text'];
        switch ($text) {
            case(strpos($text, '#жмузыка') !== false) :
                calculate($stats, 'music', $post);
                break;
            case(strpos($text, '#жсериалы') !== false) :
                calculate($stats, 'tv', $post);
                break;
            case(strpos($text, '#жигры') !== false) :
                calculate($stats, 'games', $post);
                break;
            case(strpos($text, '#жкниги') !== false) :
                calculate($stats, 'books', $post);
                break;
            case(strpos($text, '#жкино') !== false) :
                calculate($stats, 'movies', $post);
                break;
            default:
                calculate($stats, 'misc', $post);
                break;
        }
    }
}

function calculate(&$res, $path, $post)
{
    //считаем суммарные штуки
    $res['totals']['comments_count'] = isset($res['totals']['comments_count']) ?
        $res['totals']['comments_count'] + $post['comments']['count'] :
        $post['comments']['count'];

    $res['totals']['likes_count'] = isset($res['totals']['likes_count']) ?
        $res['totals']['likes_count'] + $post['likes']['count'] :
        $post['likes']['count'];

    $res['totals']['reposts_count'] = isset($res['totals']['reposts_count']) ?
        $res['totals']['reposts_count'] + $post['reposts']['count'] :
        $post['reposts']['count'];

    $res['totals']['views_count'] = isset($res['totals']['views_count']) ?
        $res['totals']['views_count'] + $post['views']['count'] :
        $post['views']['count'];

    $res['totals']['posts_count'] = $res['totals']['posts_count'] ? $res['totals']['posts_count']++ : 1;

    $text = $post['text'];

    //оценки
    $res[$path]['count']++;
    $mark = [];
    preg_match('/\d\/\d/', $text, $mark);
    $mark = explode('/', $mark[0])[0];
    $res[$path]['total_mark'] = isset($res[$path]['total_mark']) ? $res[$path]['total_mark'] + $mark : $mark;

    //продолжительность
    if ($path !== 'books') {
        $duration = (int)preg_replace('/[^\d]/', '', explode('|', $text)[2]);
        $res[$path]['duration'] = isset($res[$path]['durtaion']) ? $res[$path]['duration'] + $duration : $duration;
    }

}