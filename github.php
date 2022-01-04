<?php 
    define('GITHUB_USER', '<user>');
    define('GITHUB_TOKEN', '<token>');

    if(!isset($_GET['username'])) {
        die('please enter a username! [GET / username]');
    } else {
        $username = htmlentities($_GET['username'], ENT_QUOTES);
    }

    function callApi($query, $variables) {
        $json = json_encode(['query' => $query, 'variables' => $variables]);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.github.com/graphql');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_USERPWD, GITHUB_USER . ':' . GITHUB_TOKEN);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_USERAGENT, 'bitsflipped.ch - badges - BOT');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json'));
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            return 'Error: ' . curl_error($ch);
        } else {
            return json_decode($result, true);
        }
        curl_close($ch);

        return $result;
    }

    function getData($username) {
        $query = '
        query userData($login: String!){
            user(login: $login) {
                name
                login
                avatarUrl
                createdAt
                databaseId
                contributionsCollection {
                    totalCommitContributions
                    restrictedContributionsCount
                }
                repositoriesContributedTo(first: 1, contributionTypes: [COMMIT, ISSUE, PULL_REQUEST, REPOSITORY]) {
                    totalCount
                }
                repositories(first: 100, ownerAffiliations: OWNER, orderBy: {direction: DESC, field: STARGAZERS}) {
                    totalCount
                    nodes {
                        stargazers {
                            totalCount
                        }
                    }
                }
                followers {
                    totalCount
                }
            }
        }
        ';

        $variables = json_encode(['login' => $username]);

        $data = callApi($query, $variables);

        if(!$data['data']['user']) {
            die('Username not found!');
        }
        
        $data = $data['data']['user'];

        $output['avatarUrl'] = $data['avatarUrl'];
        $output['name'] = $data['name'] == null ? $data['login'] : $data['name'];
        $output['publicRepos'] = $data['repositories']['totalCount'];
        $output['followers'] = $data['followers']['totalCount'];
        $output['commitCount'] = $data['contributionsCollection']['totalCommitContributions'];
        $stars = 0;
        foreach($data['repositories']['nodes'] as $repo) {
            $stars += $repo['stargazers']['totalCount'];
        }
        $output['stars'] = $stars;

        return $output;
    }

    function thousandsFormat($num) {
        if($num>1000) {
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

    function createImage($data, $width = 300, $height = 70) {
        $image = new Imagick();
        $draw = new ImagickDraw();

        $image->newImage($width, $height, '#1c1c1c');

        $avatar = new Imagick($data['avatarUrl']);
        $avatar->scaleImage($height, $height);

        $follower = new Imagick('assets/follower.png');
        $follower->scaleImage(11, 11);
        $follower->colorizeImage('#fff', 1, true);
        $star = new Imagick('assets/star.png');
        $star->scaleImage(11, 11);
        $star->colorizeImage('#fff', 1, true);
        $repo = new Imagick('assets/repo.png');
        $repo->scaleImage(11, 11);
        $repo->colorizeImage('#fff', 1, true);

        $image->compositeImage($avatar, Imagick::COMPOSITE_OVER, 0, 0);
        $image->compositeImage($follower, Imagick::COMPOSITE_OVER, $height + 7, 27);
        $image->compositeImage($star, Imagick::COMPOSITE_OVER, $height + 7, 42);
        $image->compositeImage($repo, Imagick::COMPOSITE_OVER, $height + 7, 56);

        $draw->setFont('arial.ttf');
        $draw->setFontSize(15);
        $draw->setFillColor('#fff');

        $image->annotateImage($draw, $height + 7, 17, 0, $data['name']);
        $draw->setFontSize(12);
        $image->annotateImage($draw, $height + 7 + 15, 37, 0, thousandsFormat($data['followers']) . ' Followers');
        $image->annotateImage($draw, $height + 7 + 15, 52, 0, thousandsFormat($data['stars']) . ' Stars');
        $image->annotateImage($draw, $height + 7 + 15, 65, 0, thousandsFormat($data['publicRepos']) . ' Repos');

        $image->setImageFormat('png');
        return $image;
    }

    function displayImage($image) {
        header('Content-Type: image/png');
        echo $image;
    }

    $data = getData($username);
    $image = createImage($data);
    displayImage($image);