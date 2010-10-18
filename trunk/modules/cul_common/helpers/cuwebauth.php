<?php

global $cuwa_secret_cache_name;
$cuwa_secret_cache_name = 'cuwa_net_id_secret';

/**
 * Don't bother admins or various content managers with authentication
 * when they are already authenticated with Drupal.
 */
function can_bypass_auth($roles = NULL) {
  if ($roles == NULL || ! is_array($roles)) {
    $roles = array('administrator', 'content manager', 'webvision-admin', 'faq-manager');
  }

  $can_bypass = FALSE;
  global $user;

  if (is_array($user->roles)) {
    foreach ($roles as $role) {
      if (in_array($role, array_values($user->roles))) {
        $can_bypass = TRUE;
        break;
      }
    }
  }

  return $can_bypass;
}

/**
 * Who is allowed to set CUWebAuth on nodes,
 * for now, this is the same as those who can bypass CUWebAuth
 */
function can_set_auth($roles = NULL) {
  return can_bypass_auth($roles);
}

function verify_netid() {
  $verified = FALSE;
  if (isset($_COOKIE['netid']) && isset($_COOKIE['verify_netid'])) {
    $secret = get_and_set_cuwa_secret();
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
function cu_authenticate() {
  $netID = getenv('REMOTE_USER');
  if (isset($netID) && $netID) {
    return $netID;
  } else if (verify_netid()) {
    return $_COOKIE['netid'];
  } else {
    //bring the user back to the path they started with, try to avoid the internal node number.
    //assumes use of 'friendly' URL's
    get_and_set_cuwa_secret();
    unset($_REQUEST['destination']);
    drupal_goto(drupal_get_path('module','cul_common') . '/authenticate', 'destination=' . urlencode(request_uri()));
  }
}

/**
 * Simulate a CUWebAuth logout.
 */
function cuwebauth_logout($logout_url=NULL) {
  unset($_COOKIE['netid']);
  unset($_COOKIE['verify_netid']);
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
    return db_result(db_query('SELECT nid FROM {cuwebauth} where nid = (%d)', $node->nid));
}

function manage_cuwebuath($node) {
   $cuwebauth = get_cuwebauth($node);
   if ($node->cuwebauth && ! $cuwebauth) {
     db_query('INSERT INTO {cuwebauth} (nid) VALUES (%d)', $node->nid);
   } else if (! $node->cuwebauth && $cuwebauth) {
     db_query('DELETE FROM {cuwebauth} WHERE nid = %d', $node->nid);
   }
}


function get_random_string($length=10, $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz') {
    $string = '';
    for ($p = 0; $p < $length; $p++) {
        $string .= $characters[mt_rand(0, strlen($characters))];
    }
    return $string;
}

function get_and_set_cuwa_secret($refresh=FALSE) {
    static $cuwa_secret;
    global $cuwa_secret_cache_name;
    if (($cached = cache_get($cuwa_secret_cache_name, 'cache')) && ! empty($cached->data) && ! $refresh) {
        $cuwa_secret = $cached->data;
    } else {
        $cuwa_secret = get_random_string();
        cache_set($cuwa_secret_cache_name, $cuwa_secret, 'cache');
    }
    return $cuwa_secret;
}

