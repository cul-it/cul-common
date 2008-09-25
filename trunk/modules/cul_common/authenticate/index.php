<?php
$netID = getenv('REMOTE_USER');
if (isset($netID) && $netID != '') {
    setcookie('netid', $netID, 0, '/', '.cornell.edu');
    #setcookie('primary_affiliation',  exec('java SimpleQuery uid=' . $netID . ' eduPersonPrimaryAffiliation'), 0, '/', '.cornell.edu');
} else {
  echo 'no net id';
}
echo '\n<br />destination: ' . $_GET['destination'];
#header('Location: ' . $_GET['destination']);
exit();
?>




