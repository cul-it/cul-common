<?php
$netID = getenv('REMOTE_USER');
if (isset($netID) && $netID != '') {
    setcookie('netid', $netID, 0, '/', '.cornell.edu');
}

//echo 'Location: ' .  $_GET['basepath'] . $_GET['destination'];
//echo '<br>';
//echo $_REQUEST['destination'];

header('Location: ' .  $_GET['basepath'] . $_GET['destination']);
exit();

?>
