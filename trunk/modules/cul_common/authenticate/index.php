<?php
$netID = getenv('REMOTE_USER');
if (isset($netID) && $netID != '') {
    setcookie('netid', $netID, 0, '/', '.cornell.edu');
}

header('Location: ' . $_GET['destination']);
exit();

?>
