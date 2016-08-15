<?php

/**
 * Given a CU Net ID, retrieve patron data from Cornell LDAP directory.
 *
 * For a complete list of publicly available attributes and their definitions, see
 * @link http://identity.cit.cornell.edu/ds/index.html .
 *
 */
function get_ldap_data($return_fields = NULL, $netid = NULL) {
  if ($netid == NULL) {
    $netid = cu_authenticate();
  }
  $output = NULL;

  if ($return_fields == NULL) {
    $return_fields = array('eduPersonPrimaryAffiliation',
                           'cornellEduAcadCollege',
                           'givenName',
                           'sn',
                           'cornellEduCampusAddress',
                           'cornellEduCampusPhone',
                           'Mail',
                         );
  }
  else if (is_string($return_fields)) {
    $return_fields = explode(',', $return_fields);
  }

  if ($ds = ldap_connect("directory.cornell.edu")) {
    $r = ldap_bind($ds);
    $sr = ldap_search($ds, "ou=People,o=Cornell University,c=US", "uid=$netid", $return_fields);

    if ($entries = ldap_get_entries($ds, $sr)) {
      $output = array();
      for ($i = 0; $i < count($return_fields); $i++) {
        $attr_name = $entries[0][$i];
        if ($attr_name != '') {
          $value = $entries[0][$attr_name][0];
          $output[$attr_name] = $value;
        }
      }
    }

    ldap_close($ds);
  }
  else {
    watchdog('cul_common (LDAP data)', 'Could not connect to LDAP server', array(), WATGHDOG_ERROR);
  }
  return $output;
}

/**
 * Call get_ldap_data() via URL, return JSON.
 *
 */
function get_ldap_json() {
  $return_fields = NULL;
  if (isset($_GET['return_fields'])) {
    $return_fields = urldecode($_GET['return_fields']);
  }
  drupal_json_output(get_ldap_data($return_fields));
}
