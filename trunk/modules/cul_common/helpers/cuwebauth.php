<?php

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

/**
 * Basic authentication method, redirects to a CUWebAuth protected directory,
 * and upon successful authentication, it will set a 'netid' cookie.
 */
function cu_authenticate() {
  if (isset($_COOKIE['netid'])) {
    return $_COOKIE['netid'];
  } else {
    //bring the user back to the path they started with, try to avoid the internal node number.
    //assumes use of 'friendly' URL's
    unset($_REQUEST['destination']);
    drupal_goto(drupal_get_path('module','cul_common') . '/authenticate', 'destination=' . urlencode(request_uri()));
  }
}

/**
 * Simulate a CUWebAuth logout.
 */
function cuwebauth_logout($logout_url=NULL) {
  unset($_COOKIE['netid']);
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

/**
 * Implementation of hook_form_alter()
 */
function cul_common_form_alter(&$form, $form_state, $form_id) {
  if (isset($form['#node']) && can_set_auth()) {
      $node = $form['#node'];
      $form['cuwebauth'] = array (
        '#type' => 'checkbox',
        '#title' => 'Require CUWebLogin?',
        '#default_value' => isset($node->cuwebauth) ? $node->cuwebauth : 0,
        '#weight' => -10,
      );
  }
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
