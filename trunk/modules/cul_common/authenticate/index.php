<?php
$netID = getenv('REMOTE_USER');
if (isset($netID) && $netID != '') {
    setcookie('netid', $netID, 0, '/', '.cornell.edu');
}

echo 'Location: http://' . $_SERVER['HTTP_HOST'] . $_GET['destination'];

//header('Location: http://' . $_SERVER['HTTP_HOST'] . $_GET['destination']);
exit();

?>
