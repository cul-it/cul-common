<?php

/* relative path to settings.php
	this file
		<<drupal_root>>/sites/all/modules/custom/cul_common/authenticate/index.php
	settings
		<<drupal_root>>/sites/default/settings.php
	path
		../../../../../default/settings.php
	use dirname
		dirname(__FILE__)	authenticate
		dirname(dirname(... cul_common
		dirname(dirname(dirname(... custom
		dirname(dirname(dirname(dirname(... modules
		dirname(dirname(dirname(dirname(dirname(... all
		dirname(dirname(dirname(dirname(dirname(dirname(... sites		
*/

$settings_path = realpath(dirname(__FILE__) . '/' . '../../../../../default/settings.php');
require_once $settings_path;

$secret = '';
/*
$url = parse_url($db_url);

// Decode url-encoded information in the db connection string
$url['user'] = urldecode($url['user']);
// Test if database url has a password.
$url['pass'] = isset($url['pass']) ? urldecode($url['pass']) : '';
$url['host'] = urldecode($url['host']);
$url['path'] = urldecode($url['path']);

// Allow for non-standard MySQL port.
if (isset($url['port'])) {
  $url['host'] = $url['host'] . ':' . $url['port'];
}
*/

/*
	 Drupal 7 settings.php is different
*/
$db = $databases['default']['default'];
$url['user'] = $db['username'];
$url['pass'] = $db['password'];
$url['host'] = empty($db['port']) ? $db['host'] : $db['host'] . ':' . $db['port'];
$url['path'] = $db['database'];

// - TRUE makes mysql_connect() always open a new link, even if
//   mysql_connect() was called before with the same parameters.
//   This is important if you are using two databases on the same
//   server.
// - 2 means CLIENT_FOUND_ROWS: return the number of found
//   (matched) rows, not the number of affected rows.
$connection = @mysql_connect($url['host'], $url['user'], $url['pass'], TRUE, 2);
if (!$connection || !mysql_select_db(substr($url['path'], 1))) {
  // Show error screen otherwise
  echo mysql_error();
}
else {
  $table_name = $db_prefix . 'cache';
  $result = mysql_query('SELECT data from ' . $table_name . ' WHERE cid = "cuwa_net_id_secret"');
  if (!$result) {
    die('Invalid query: ' . mysql_error());
  }
  else {
    while ($row = mysql_fetch_assoc($result)) {
      $secret = $row['data'];
    }
  }
}
mysql_close($connection);

$netid = getenv('REMOTE_USER');
if (isset($netid) && $netid) {
  setcookie('netid', $netid, 0, '/', '.cornell.edu');
  setcookie('verify_netid', md5($netid . $secret), 0, '/', '.cornell.edu');
}

$destination = $_GET['destination'];
if (! isset($_GET['destination']) || $_GET['destination'] == '') {
  $destination = '/';
}

header('Location: http://' . $_SERVER['HTTP_HOST'] . $destination);
exit();

?>





