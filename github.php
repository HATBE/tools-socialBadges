<?php
    define('GITHUB_USER', '<user>');
    define('GITHUB_TOKEN', '<token>');

    $api = 'https://api.github.com/';

    $output = [];

    if(!isset($_GET['username'])) {
        die('please enter a username! [GET / username]');
    } else {
        $username = htmlentities($_GET['username'], ENT_QUOTES);
    }

    function apiGet($url, $path) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url . $path);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_USERPWD, GITHUB_USER . ':' . GITHUB_TOKEN);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/99.0.7113.93 Safari/537.36');
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            return 'Error: ' . curl_error($ch);
        } else {
            return json_decode($result, true);
        }
        curl_close($ch);
    }

    function getData($api, $username) {
        $user = apiGet($api, "users/{$username}");

        if(isset($user['message'])) {
            die('user does not exist!');
        }

        $output['avatarUrl'] = $user['avatar_url'];
        $output['name'] = $user['name'] == null ? $user['login'] : $user['name'];
        $output['htmlUrl'] = $user['html_url'];
        $output['publicRepos'] = $user['public_repos'];
        $output['followers'] = $user['followers'];
        $output['commitCount'] = apiGet($api, "search/commits?q=author:{$username}&per_page=1")['total_count'];

        return $output;
    }

    function createImage($data, $width = 300, $height = 70) {
        $font_arial = __DIR__ . '/arial.ttf';

        $image = imagecreate($width, $height);

        $avatar = imagecreatefromstring(file_get_contents($data['avatarUrl']));
        $avatar =  imagescale($avatar, $height, $height, IMG_BICUBIC_FIXED);

        $color_white = imagecolorallocate($image, 255, 255, 255);
        $color_dark_gray = imagecolorallocate($image, 28, 28, 28);

        imagefill($image, 0, 0, $color_dark_gray);

        imagecopymerge($image, $avatar, 0, 0, 0, 0, $height, $height, 100);

        imagettftext($image, 12, 0, $height + 5, 17, $color_white, $font_arial, $data['name']);
        imagettftext($image, 8, 0, $height + 5, 45, $color_white, $font_arial, "Commits: " . number_format($data['commitCount'], 0, ".", "'"));
        imagettftext($image, 8, 0, $height + 5, 55, $color_white, $font_arial, "Repos: " . number_format($data['publicRepos'], 0, ".", "'"));
        imagettftext($image, 8, 0, $height + 5, 65, $color_white, $font_arial, "Follower: " . number_format($data['followers'], 0, ".", "'"));

        return $image;
    }

    function displayImage($image) {
        header('Content-Type: image/png');
        imagepng($image);
    
        imagedestroy($image);
    }

    $data = getData($api, $username);
    $image = createImage($data);
    displayImage($image);
