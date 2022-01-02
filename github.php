<?php
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    // https://stackoverflow.com/questions/45238419/how-to-query-github-graphql-api-from-php-script
    // USE THIS!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!

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

    function thousandsFormat($num) {
        if($num > 1000) {
              $x = round($num);
              $x_number_format = number_format($x);
              $x_array = explode(',', $x_number_format);
              $x_parts = array('k', 'm', 'b', 't');
              $x_count_parts = count($x_array) - 1;
              $x_display = $x;
              $x_display = $x_array[0] . ((int) $x_array[1][0] !== 0 ? '.' . $x_array[1][0] : '');
              $x_display .= $x_parts[$x_count_parts - 1];
              return $x_display;
        }
        return $num;
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
        $output['commitCount'] = apiGet($api, "search/commits?q=author:{$username}")['total_count']; // WRONG!!
        $repos = apiGet($api, "users/{$username}");

        return $output;
    }

    function createImage($data, $width = 300, $height = 70) {
        $image = new Imagick();
        $draw = new ImagickDraw();

        $image->newImage($width, $height, new ImagickPixel('#1c1c1c'));

        $avatar = new Imagick($data['avatarUrl']);
        $avatar->scaleImage($height, $height);

        $image->compositeImage($avatar, Imagick::COMPOSITE_OVER, 0, 0);

        $draw->setFont('arial.ttf');
        $draw->setFontSize(15);
        $draw->setFillColor('#fff');

        $image->annotateImage($draw, $height + 7, 17, 0, $data['name']);
        $draw->setFontSize(12);
        $image->annotateImage($draw, $height + 7, 37, 0, "Follower: " . thousandsFormat($data['followers']));
        $image->annotateImage($draw, $height + 7, 52, 0, "Commits: " . thousandsFormat($data['commitCount']));
        $image->annotateImage($draw, $height + 7, 65, 0, "Repos: " . thousandsFormat($data['publicRepos']));

        $image->setImageFormat('png');
        return $image;
    }

    function displayImage($image) {
        header('Content-Type: image/png');
        echo $image;
    }

    $data = getData($api, $username);
    $image = createImage($data);
   displayImage($image);
