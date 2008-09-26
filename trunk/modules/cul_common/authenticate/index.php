<?php
$netID = getenv('REMOTE_USER');
if (isset($netID) && $netID != '') {
    setcookie('netid', $netID, 0, '/', '.cornell.edu');
    echo 'authenticated';
} else {
   echo 'not authenticated';
}


//header('Location: ' .  $_GET['basepath'] . $_GET['destination']);
exit();

?>




