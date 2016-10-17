<?php

// Full bootstrap of Drupal 7 to find settings.php and use drupal_get_destination
define('DRUPAL_ROOT', $_SERVER['DOCUMENT_ROOT']);
require_once DRUPAL_ROOT . '/includes/bootstrap.inc';
require_once DRUPAL_ROOT . '/includes/path.inc';
require_once DRUPAL_ROOT . '/includes/common.inc';
require_once DRUPAL_ROOT . '/includes/module.inc';
require_once DRUPAL_ROOT . '/includes/unicode.inc';
require_once DRUPAL_ROOT . '/includes/file.inc';

// Do basic bootstrap to make sure the database can be accessed
drupal_bootstrap(DRUPAL_BOOTSTRAP_DATABASE);

$settings_path = $_SERVER['DOCUMENT_ROOT'] . '/' . conf_path() . "/settings.php";
require_once $settings_path;

$secret = '';

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
if (!$connection || !mysql_select_db($url['path'])) {
  // Show error screen otherwise
  echo mysql_error();
}
else {
  if (empty($db['prefix'])) {
    $table_name = 'cache';
  }
  else {
    $table_name = $db['prefix'] . 'cache';
  }
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

if (! isset($_GET['destination']) || $_GET['destination'] == '') {
  $destination = '/';
}
else {
  $destination = urldecode($_GET['destination']);
}

$current_url = url(NULL, array('absolute' => TRUE));
$parts = parse_url($current_url);
$url = $parts['scheme'] . '://' . $parts['host'];
if (!empty($parts['port'])) {
  $url .= ':' . $parts['port'];
}
$url .= $destination;
header('Location: ' . $url);
exit();

?>
