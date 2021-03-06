<?php

global $cuwa_secret_cache_name;
$cuwa_secret_cache_name = 'cuwa_net_id_secret';

/**
 * Don't bother admins or various content managers with authentication
 * when they are already authenticated with Drupal.
 * now based on cul_common_permission
 */
function can_bypass_auth($roles = NULL) {
  return user_access('bypass cuwebauth');
}

/**
 * Who is allowed to set CUWebAuth on nodes,
 * now based on cul_common_permission
 */
function can_set_auth($roles = NULL) {
  return user_access('access cuwebauth checkbox');
}

function verify_netid() {
  $verified = FALSE;
  if (isset($_COOKIE['netid']) && isset($_COOKIE['verify_netid'])) {
    $secret = get_and_set_cuwa_secret();
    global $cuwa_secret_cache_name;
    if (md5($_COOKIE['netid'] . $secret) == $_COOKIE['verify_netid']) {
      $verified = TRUE;
    }
  }
  return $verified;
}

/**
 * Basic authentication method, redirects to a CUWebAuth protected directory,
 * and upon successful authentication, it will set a 'netid' cookie.
 */
function cu_authenticate($destination = '', $permit = '') {
  if (isset($destination) && $destination != '') {
    $destination = urlencode($destination);
  }
  else {
    $destination = urlencode(request_uri());
  }

  $netID = getenv('REMOTE_USER');
  if (isset($netID) && $netID != '') {
    return $netID;
  }
  else if (verify_netid()) {
    return $_COOKIE['netid'];
  }
  else {
    //bring the user back to the path they started with, try to avoid the internal node number.
    //assumes use of 'friendly' URL's
    get_and_set_cuwa_secret();
    unset($_GET['destination']);
    if (!empty($permit)) {
      $permit .= "/"; // permit names used as subdirectory names under authenticate
      $path = drupal_get_path('module', 'cul_common') . '/authenticate/' . $permit . 'index.php';
      if (!file_exists($path)) {
        return FALSE; // unexpected permit
      }
    }
    drupal_goto(drupal_get_path('module', 'cul_common') . '/authenticate/' . $permit . 'index.php',
    	array('query' => array('destination' => $destination)));
  }
}

/**
 * Simulate a CUWebAuth logout.
 */
function cuwebauth_logout($logout_url = NULL, $include_cuwa_cookies = FALSE) {
  unset($_COOKIE['netid']);
  unset($_COOKIE['verify_netid']);
  setcookie('netid', '', REQUEST_TIME - 3600);
  setcookie('verify_netid', '', REQUEST_TIME - 3600);
  if ($include_cuwa_cookies) {
    unset($_COOKIE['cuwltgttime']);
    unset($_COOKIE['CUWALastWeblogin']);
    unset($_COOKIE['cuweblogin2']);
    setcookie('cuwltgttime', '', REQUEST_TIME - 3600);
    setcookie('CUWALastWeblogin', '', REQUEST_TIME - 3600);
    setcookie('cuweblogin2', '', REQUEST_TIME - 3600);
  }
  if ($logout_url) {
    drupal_goto($logout_url);
  }
}

/**
 * Call cuwebauth_logout() from client.
 */
function cuwebauth_logout_from_url() {
  $logout_url = NULL;
  if (isset($_GET['$logout_url'])) {
    $logout_url = $_GET['$logout_url'];
  }
  cuwebauth_logout($logout_url);
}


function get_cuwebauth($node) {
	if (isset($node->nid)) {
		$nid = $node->nid;
		$result = db_query('SELECT nid FROM {cuwebauth} where nid = :nid',
		array(':nid' => $node->nid));
		return $result->fetchColumn();

		/*
		$result = db_select('cuwebauth', 'c')
		  ->fields('c', array('nid'))
		  ->condition('nid', $node->nid)
		  ->execute();
		*/
		}
	else {
		return false;
		}
}

function manage_cuwebuath($node) {
  if (isset($node->cuwebauth)) {
    $cuwebauth = get_cuwebauth($node);
    if ($node->cuwebauth && ! $cuwebauth) {
      // TODO Please review the conversion of this statement to the D7 database API syntax.
      /* db_query('INSERT INTO {cuwebauth} (nid) VALUES (%d)', $node->nid) */
      $id = db_insert('cuwebauth')
  ->fields(array(
    'nid' => $node->nid,
  ))
  ->execute();
    }
    else if (! $node->cuwebauth && $cuwebauth) {
      // TODO Please review the conversion of this statement to the D7 database API syntax.
      /* db_query('DELETE FROM {cuwebauth} WHERE nid = %d', $node->nid) */
      db_delete('cuwebauth')
  ->condition('nid', $node->nid)
  ->execute();
    }
  }
}


function get_random_string($length = 10, $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz') {
  $string = '';
  for ($p = 0; $p < $length; $p++) {
    $string .= $characters[mt_rand(0, strlen($characters) -1)];
  }
  return $string;
}

function get_and_set_cuwa_secret($refresh = FALSE) {
  static $cuwa_secret;
  global $cuwa_secret_cache_name;
  if (($cached = cache_get($cuwa_secret_cache_name, 'cache')) && ! empty($cached->data) && ! $refresh) {
    $cuwa_secret = $cached->data;
  }
  else {
    $cuwa_secret = get_random_string();
    cache_set($cuwa_secret_cache_name, $cuwa_secret, 'cache');
  }
  return $cuwa_secret;
}

