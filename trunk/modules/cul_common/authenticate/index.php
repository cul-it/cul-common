<?php

echo 'index.php<br>';
echo 'requiring file at: ' . dirname(__FILE__) . '/../../../../default/settings.php' . '<br>';
require_once(dirname(__FILE__) . '/../../../../default/settings.php') or die ("couldn't get database settings");

echo 'index.php, db_url: ' . $db_url . '<br>';

$secret = '';
$url = parse_url($db_url);

echo 'index.php, url: ' . $url . '<br>';

// Decode url-encoded information in the db connection string
$url['user'] = urldecode($url['user']);
// Test if database url has a password.
$url['pass'] = isset($url['pass']) ? urldecode($url['pass']) : '';
$url['host'] = urldecode($url['host']);
$url['path'] = urldecode($url['path']);

// Allow for non-standard MySQL port.
if (isset($url['port'])) {
    $url['host'] = $url['host'] .':'. $url['port'];
}

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
} else {
    $result = mysql_query('SELECT data from cache WHERE cid = "cuwa_net_id_secret"');
    if (!$result) {
        die('Invalid query: ' . mysql_error());
    } else {
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

echo 'index.php, netid: ' . $netid . '<br>';
echo 'index.php, secret: ' . $secret . '<br>';
echo 'index.php, verify_netid: ' . md5($netid . $secret) . '<br>';
//header('Location: http://' . $_SERVER['HTTP_HOST'] . $_GET['destination']);

exit();

?>

