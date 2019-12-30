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

gatherAverages($stats);

echo json_encode($stats, JSON_UNESCAPED_UNICODE);

function calculate(&$res, $path, $post)
{
    //считаем суммарные штуки
    $res['totals']['comments_count'] = (int)$res['totals']['comments_count'] + $post['comments']['count'];

    $res['totals']['likes_count'] = (int)$res['totals']['likes_count'] + $post['likes']['count'];

    $res['totals']['reposts_count'] = (int)$res['totals']['reposts_count'] + $post['reposts']['count'];

    $res['totals']['views_count'] = (int)$res['totals']['views_count'] + $post['views']['count'];

    $res['totals']['count']++;

    $text = $post['text'];

    $headerEnd = stripos($text, '/1') + 5;
    $header = substr($text, 0, $headerEnd);

    $parts = explode('|', $header);
    $mark = [];
    foreach ($parts as $part) {
        preg_match('/\d\/\d/', $part, $mark);
        if (stripos($part, 'мин.') !== false) {
            //продолжительность
            $duration = (int)preg_replace('/[^\d]/', '', $part);
            $res[$path]['duration'] = (int)$res[$path]['duration'] + $duration;
            $res['totals']['duration'] = (int)$res['totals']['duration'] + $duration;
        } elseif (!empty($mark)) {
            //оценки
            $res[$path]['count']++;
            $mark = explode('/', $mark[0])[0];
            $res[$path]['total_mark'] = (int)$res[$path]['total_mark'] + $mark;
            $res['totals']['total_mark'] = (int)$res['totals']['total_mark'] + $mark;
            $highMark  = $mark > 7 ? true : false;
            $mark = [];
        } elseif ($year = (int)preg_replace('/[^\d]/', '', $part)) {
            if ($year == '2019') {
                $res[$path]['current_year']++;
            }
        }
    }


    if (stripos($text, '#балтикавосьмерка') !== false
        || stripos($text, '#балтикавосьмёрка') !== false
        || $highMark
    ) {
        $res[$path]['high_mark']++;
        $text = str_replace('#балтикавосьмерка', '', $text);
        $text = str_replace('#балтикавосьмерка', '', $text);
    }

    //длина поста
    $textTrimmed = str_replace($path, '', $text);
    $textTrimmed = str_replace($header, '', $textTrimmed);
    $length = strlen($textTrimmed);
    $res[$path]['length'] = (int)$res[$path]['length'] + $length;
    $res['totals']['length'] = (int)$res['totals']['length'] + $length;
}

function gatherAverages(&$stats)
{
    foreach ($stats as $type => &$stat) {
        if ($type !== 'misc') {
            $stat['average_mark'] = $stat['total_mark'] / $stat['count'];
            $stat['average_duration'] = $stat['duration'] / $stat['count'];
            $stat['average_length'] = $stat['length'] / $stat['count'];
        }
    }

}