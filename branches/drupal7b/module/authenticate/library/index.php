<?php
$localpath=$_SERVER['SCRIPT_NAME'];
$absolutepath=realpath($localpath);
// a fix for Windows slashes
$absolutepath=str_replace("\\","/",$absolutepath);
$docroot=substr($absolutepath,0,strpos($absolutepath,$localpath));
echo "<br />local: $localpath" . PHP_EOL;
echo "<br />absolute: $absolutepath" . PHP_EOL;
echo "<br />docroot: $docroot" . PHP_EOL;
echo "<br />dirname(__FILE__): " . dirname(__FILE__) . PHP_EOL;
echo "<br />realpath(dirname(__FILE__)): " . realpath((dirname(__FILE__)) . PHP_EOL;

include $docroot."/sites/all/modules/custom/cul_common/authenticate/index.php";

//include DRUPAL_ROOT . '/' . realpath(dirname(__FILE__) . '/' . '../index.php');
?>

