<?php
header('Content-type: text/html; charset=utf-8');
$wallId = "-85591793";
$count = 100;
$token = "56163a4356163a4356163a43f856772ee45561656163a430c6fefcf0eef0da9069f78b5";
$epochTime = 1546300800;

$stats = [];

for ($offset = 0; $offset < 1000; $offset += 100) {
    $api = file_get_contents("http://api.vk.com/method/wall.get?owner_id={$wallId}&count={$count}&offset={$offset}&access_token={$token}&v=5.103");
    $wall = json_decode($api, true);
    foreach ($wall['response']['items'] as $post) {
        if (isset($post['is_pinned']) && $post['is_pinned']) {
            continue;
        }
        if ($post['date'] < $epochTime) {
            break 2;
        }
        $text = $post['text'];
        switch ($text) {
            case(strpos($text, '#жмузыка') !== false) :
                calculate($stats, '#жмузыка', $post);
                break;
            case(strpos($text, '#жсериалы') !== false) :
                calculate($stats, '#жсериалы', $post);
                break;
            case(strpos($text, '#жигры') !== false) :
                calculate($stats, '#жигры', $post);
                break;
            case(strpos($text, '#жкниги') !== false) :
                calculate($stats, '#жкниги', $post);
                break;
            case(strpos($text, '#жкино') !== false) :
                calculate($stats, '#жкино', $post);
                break;
            default:
                calculate($stats, 'misc', $post);
                $miscPosts[] = $post;
                break;
        }
    }
}
echo json_encode($stats);

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
    $res['totals']['total_mark'] = isset($res['totals']['total_mark']) ? $res['totals']['total_mark'] + $mark : $mark;

    //продолжительность
    if ($path !== '#жкниги') {
        $duration = (int)preg_replace('/[^\d]/', '', explode('|', $text)[2]);
        $res[$path]['duration'] = isset($res[$path]['duration']) ? $res[$path]['duration'] + $duration : $duration;
        $res['totals']['duration'] = isset($res['totals']['duration']) ? $res['totals']['duration'] + $duration : $duration;
    }

    //текущий ли год
    $year = $duration = (int)preg_replace('/[^\d]/', '', explode('|', $text)[1]);
    if ($year == '2019') {
        $res[$path]['current_year'] = isset($res[$path]['current_year']) ? (int)$res[$path]['current_year']++ : 1;
    }

    if (stripos($text, '#балтикавосьмерка') !== false || stripos($text, '#балтикавосьмёрка') !== false) {
        $res[$path]['high_mark'] = isset($res[$path]['high_mark']) ? (int)$res[$path]['high_mark']++ : 1;
        $text = str_replace('#балтикавосьмерка', '', $text);
        $text = str_replace('#балтикавосьмерка', '', $text);
    }

    //длина поста
    $headerEnd = stripos($text, '/1') + 5;
    $textTrimmed = str_replace($path, '', substr($text, $headerEnd));
    $length = strlen($textTrimmed);
    $res[$path]['length'] = isset($res[$path]['length']) ? (int)$res[$path]['length'] + $length : $length;
    $res['totals']['length'] = isset($res['totals']['length']) ? (int)$res['totals']['length'] + $length : $length;

}