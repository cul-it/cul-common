<?php
$localpath=getenv("SCRIPT_NAME");
$absolutepath=realpath($localpath);
// a fix for Windows slashes
$absolutepath=str_replace("\\","/",$absolutepath);
$docroot=substr($absolutepath,0,strpos($absolutepath,$localpath));
echo "local: $localpath" . PHP_EOL;
echo "absolute: $absolutepath" . PHP_EOL;
echo "docroot: $docroot" . PHP_EOL;

include $docroot."/sites/all/modules/custom/cul_common/authenticate/index.php";

//include DRUPAL_ROOT . '/' . realpath(dirname(__FILE__) . '/' . '../index.php');
?>

