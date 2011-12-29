<?php

require './config.php';
require './facebook.php';

//connect to mysql database
mysql_connect($db_host, $db_username, $db_password);
mysql_select_db($db_name);
mysql_query("SET NAMES utf8");

//Create facebook application instance.
$facebook = new Facebook(array(
  'appId'  => $fb_app_id,
  'secret' => $fb_secret
));

function fetch_all_status($facebook, $token, $since_id = NULL)
{
    return fetch_all('/me/statuses', $facebook, $token, $since_id);
}

function fetch_all_likes($status_id, $facebook, $token, $since_id = NULL)
{
    return fetch_all("/$status_id/likes", $facebook, $token, $since_id);
}

function fetch_all_comments($status_id, $facebook, $token, $since_id = NULL)
{
    return fetch_all("/$status_id/comments", $facebook, $token, $since_id);
}

function fetch_all($path, $facebook, $token, $since_id = NULL)
{
    $i = 0;
    $out = array();
    try
    {
        do
        {
            $pageOut = fetch_page($path, $facebook, $token, $since_id, $i++);
            if (empty($pageOut))
                break;
            $out = array_merge($out, $pageOut);
            if (count($pageOut) != 25)
                break;
        }
        while (true);
    }
    catch (FacebookApiException $e)
    {
        $out = array();
    }
    return $out;
}

function fetch_page($path, $facebook, $token, $since_id = NULL, $page = 0)
{
    //Number of entries we want on a page
    $pageLimit = 25;
    $offset = $page * $pageLimit;
    $params['access_token'] = $token;
    $params['limit'] = $pageLimit;
    if ($offset > 0)
        $params['offset'] = $offset;
    try
    {
            $feed = $facebook->api($path, 'GET', $params);
            $data = $feed['data'];
            foreach ($data as $update)
            {
                if (!empty($since_id) and $update['id'] == $since_id)
                    break;
                $output[] = $update;
            }
    }
    catch (FacebookApiException $e)
    {
        echo "throwing";
        throw $e;
    }
    return $output;
}

function show_all_status($facebook, $token, $since_id = NULL)
{
    $statuses = fetch_all_status($facebook, $token, $since_id = NULL);
    $output = "<table><tr><th>Message</th><th>Likes</th><th>Comments</th></tr>";
    foreach ($statuses as $status)
    {
        $output .= "<tr><td>". $status['message']. "</td>";
        $likes = fetch_all_likes($status['id'], $facebook, $token, $since_id = NULL);
        $comments = fetch_all_comments($status['id'], $facebook, $token, $since_id = NULL);
        $output .= "<td>" . count($likes) . "</td>";
        $output .= "<td>" . count($comments) . "</td></tr>";
    }
    $output .= "</table>";
    return $output;
}

function store_latest_status($user, $status)
{
    mysql_query("CREATE TABLE IF NOT EXISTS `user_latest_status` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` varchar(32) NOT NULL,
        `status_id` varchar(32) NOT NULL,
        PRIMARY KEY (`id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
    ");
    $result = mysql_query("
        SELECT
            *
        FROM
            user_latest_status
        WHERE
            user_id = '" . mysql_real_escape_string($user) . "'
    " );
    $row = null;
    if ($result)
        $row = mysql_fetch_array($result, MYSQL_ASSOC);
    if ($row)
    {
        mysql_query(
            "UPDATE
                user_latest_status
            SET
                `user_id` = '" . mysql_real_escape_string($user) . "',
                `status_id` = '" . mysql_real_escape_string($status['id']) . "',
            WHERE
                `user_id` = '" . mysql_real_escape_string($user) . "'
        ");
    }
    else
    {
        mysql_query(
            "INSERT INTO
                user_latest_status(`user_id`,`status_id`)
            VALUES(
                '" . mysql_real_escape_string($user) . "',
                '" . mysql_real_escape_string($status['id']) .
                "')
        ");
    }
}

function store_all_status($user, $facebook, $token, $since_id = NULL)
{
    mysql_query("CREATE TABLE IF NOT EXISTS `user_statuses` (
        `user_id` varchar(32) NOT NULL,
        `status_id` varchar(32) NOT NULL,
        `message` varchar(1024) NOT NULL,
        `time` varchar(64) NOT NULL,
        PRIMARY KEY (`status_id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
    ");

    mysql_query("CREATE TABLE IF NOT EXISTS `user_statuses_likes` (
        `status_id` varchar(32) NOT NULL,
        `person_id` varchar(32) NOT NULL,
        `time` varchar(64),
        PRIMARY KEY (`person_id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
    ");

    $statuses = fetch_all_status($facebook, $token, $since_id);
    store_latest_status($user, $statuses[0]);
    foreach ($statuses as $status)
    {
        //$likes = fetch_all_likes($status['id'], $facebook, $token, $since_id = NULL);
        $result = mysql_query("
            SELECT
                *
            FROM
                user_statuses
            WHERE
                status_id = '" . mysql_real_escape_string($status['id']) . "'
        ");
        $row = null;
        if ($result)
            $row = mysql_fetch_array($result, MYSQL_ASSOC);
        if($row)
        {
            mysql_query("
                UPDATE
                    user_statuses
                SET
                    `user_id` = '" . mysql_real_escape_string($user) . "',
                    `message` = '" . mysql_real_escape_string($status['message']) . "'
                    `time` = '" . mysql_real_escape_string($status['updated_time']) . "'
                WHERE
                    status_id='" . mysql_real_escape_string($status['id']) . "'
            ");
        }
        else
	{
            $r = mysql_query(
                "INSERT INTO
                    user_statuses(`user_id`, `status_id`, `message`, `time`)
                VALUES(
                    '" . mysql_real_escape_string($user) . "',
                    '" . mysql_real_escape_string($status['id']) . "',
                    '" . mysql_real_escape_string($status['message']) . "',
                    '" . mysql_real_escape_string($status['updated_time']) .
                    "')
            ");
        }
    }
}

$output = '';

//if form below is posted- 
//lets try to send wallposts to users walls, 
//which have given us a access_token for that
//get users and try fetching their feeds

$result = mysql_query("
    SELECT
        *
    FROM
        offline_access_users
    WHERE
	name NOT LIKE 'Tirtha%'
    ");
    
/*if($result){
    while($row = mysql_fetch_array($result, MYSQL_ASSOC)){
        $output .= "<p><b>". $row['name']. "</b>";
        $output .= show_all_status($facebook, $row['access_token'], null);
        $output .= "</p>";
    }
}*/
if ($result)
{
    while ($row = mysql_fetch_array($result, MYSQL_ASSOC))
    {
        $result1 = mysql_query("
            SELECT
                *
            FROM
                user_latest_status
            WHERE
                `user_id` = '" . mysql_real_escape_string($row['user_id']) . "'
            ");
        $row1 = null;
        if($result1)
            $row1 = mysql_fetch_array($result1, MYSQL_ASSOC);
        if ($row1)
            store_all_status($row['user_id'], $facebook, $row['access_token'], $row1['status_id']);
        else
            store_all_status($row['user_id'], $facebook, $row['access_token'], null);
    }
}


?><!DOCTYPE html 
    PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="et" lang="en">
    <head>
        <title>Read status</title>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <style type="text/css">
            body { font-family:Verdana,"Lucida Grande",Lucida,sans-serif; font-size: 12px}
        </style>
    </head>
    <body>
        <?php echo $output; ?>
    </body>
</html>