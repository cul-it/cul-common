<?php
$localpath=getenv("SCRIPT_NAME");
$absolutepath=realpath($localPath);
// a fix for Windows slashes
$absolutepath=str_replace("\\","/",$absolutepath);
$docroot=substr($absolutepath,0,strpos($absolutepath,$localpath));
include $docroot."/sites/all/modules/custom/cul_common/authenticate/index.php";

//include DRUPAL_ROOT . '/' . realpath(dirname(__FILE__) . '/' . '../index.php');
?>

