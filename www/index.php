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

//get user- if present, insert/update access_token for this user
$user = $facebook->getUser();
if($user){

	mysql_query("CREATE TABLE IF NOT EXISTS `offline_access_users` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`user_id` varchar(32) NOT NULL,
		`name` varchar(32) NOT NULL,
		`access_token` varchar(255) NOT NULL,
		PRIMARY KEY (`id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
	");
	
	//get user data and access token
	try {
		$userData = $facebook->api('/me');
	} catch (FacebookApiException $e) {
		die("API call failed");
	}
	$accessToken = $facebook->getAccessToken();
	
	//check that user is not already inserted? If is. check it's access token and update if needed
	//also make sure that there is only one access_token for each user
	$row = null;
	$result = mysql_query("
		SELECT
			*
		FROM
			offline_access_users
		WHERE
			user_id = '" . mysql_real_escape_string($userData['id']) . "'
	");
	
	if($result){
		$row = mysql_fetch_array($result, MYSQL_ASSOC);
		if(mysql_num_rows($result) > 1){
			mysql_query("
				DELETE FROM
					offline_access_users
				WHERE
					user_id='" . mysql_real_escape_string($userData['id']) . "' AND
					id != '" . $row['id'] . "'
			");
		}
	}
	
	if(!$row){
		mysql_query(
			"INSERT INTO 
				offline_access_users(`user_id`, `name`, `access_token`)
			VALUES(
				'" . mysql_real_escape_string($userData['id']) . "',
				'" . mysql_real_escape_string($userData['name']) . "',
				'" . mysql_real_escape_string($accessToken) . 
                "')
		");
	} else {
		mysql_query(
			"UPDATE 
				offline_access_users
			SET
				`access_token` = '" . mysql_real_escape_string($accessToken) . "'
			WHERE
				`id` = " . $row['id'] . "
		");
	}
}

//redirect to facebook page
if(isset($_GET['code'])){
	header("Location: " . $fb_app_url);
	exit;
}

//create authorising url
if(!$user){
	$loginUrl = $facebook->getLoginUrl(array(
		'canvas' => 1,
		'fbconnect' => 0,
		'scope' => 'offline_access,user_status'
	));
}

?>
<!DOCTYPE html 
	PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="et" lang="en">
	<head>
		<title>Offline access with batch posting demo</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<style type="text/css">
			body { font-family:Verdana,"Lucida Grande",Lucida,sans-serif; font-size: 12px}
		</style>
	</head>
	<body>
		<h1>Study of Facebook activity trends</h1>
		
			<?php if ($user){ ?>
				<p>
					Thank you for participating in this novel effort to understand the human social network better.
				</p>
			<?php } else { ?>
				<p>
					<strong><a href="<?php echo $loginUrl; ?>" target="_top">Allow this app to access my profile</a></strong>
				</p>
				<p>
					This study aims to study the semantics behind the human social network of Facebook.
				</p>
			<?php } ?>
	</body>
</html>
