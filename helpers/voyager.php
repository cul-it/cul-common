<?php

function _set_oracle_error_message($message, $stid=0) {
  if ($stid) {
    $err = oci_error($stid);
  } else {
    $err = oci_error();
  }
  $message ='ERROR: ' . $message . ' ' . $err['message'];
  watchdog('cul_common (voyager database)', $message, array(), WATGHDOG_ERROR);
}

function get_voyager_connection() {
  if ($conn = oci_connect("dbread", "dbread", "//database.library.cornell.edu:1521/VGER")) {
    return $conn;
  } else {
    _set_oracle_error_message('Oracle could not establish a connection.');
    return null;
  }
}


/**
 * Voyager query handling code
 */
function _get_voyager_patron_data() {
  if ($conn = get_voyager_connection()) {
    $netid  = cu_authenticate();
    $email  = $netid . '@cornell.edu';
    $query = "SELECT p.patron_id, pb.patron_barcode, p.first_name, p.last_name
              FROM   patron_barcode pb, patron_address pa, patron p
              WHERE  pa.address_line1 = :email
              AND    pb.barcode_status = 1
              AND    pb.patron_id = pa.patron_id
              AND    pb.patron_id = p.patron_id";

    $stid = oci_parse($conn, $query);
    oci_bind_by_name($stid, ":email", $email);
    oci_define_by_name($stid, "PATRON_ID", $pid);
    oci_define_by_name($stid, "PATRON_BARCODE", $bc);
    oci_define_by_name($stid, "FIRST_NAME", $fn);
    oci_define_by_name($stid, "LAST_NAME", $ln);

    $output = array();
    if (oci_execute($stid, OCI_DEFAULT)) {
      while (oci_fetch($stid)) {
        $output['patron_id'] = $pid;
        $output['patron_barcode'] = $bc;
        $output['first_name'] = $fn;
        $output['last_name'] = $ln;
      }
    } else {
      _set_oracle_error_message('Query failed', $stid);
    }
    oci_close($conn);
  }
  return $output;
}

/**
 * Given a CU Net ID, retrieve patron data from Voyager.
 *
 * Cached results are retrieved unless a refresh is forced.
 *
 */
function get_voyager_patron_data($force_refresh=FALSE) {
  $netid = cu_authenticate();
  $output = db_fetch_array(db_query('SELECT patron_id, patron_barcode, first_name, last_name FROM {cache_patron_data} where netid = "%s"', $netid));

  if (! $output) {
    $output = _get_voyager_patron_data();
    if ($output) {
      db_query('INSERT INTO {cache_patron_data} (netid, patron_id, patron_barcode, first_name, last_name) VALUES ("%s", %d, "%s", "%s", "%s")', $netid, $output['patron_id'], $output['patron_barcode'], $output['first_name'], $output['last_name']);
    }
  } else {
      if ($force_refresh) {
        $output = _get_voyager_patron_data();
        db_query('update {cache_patron_data} set patron_id = %d, patron_barcode = "%s", first_name = "%s", last_name = "%s" where netid = "%s"', $output['patron_id'], $output['patron_barcode'], $output['first_name'], $output['last_name'], $netid);
      }
  }
  return $output;
}

/**
 * Call get_voyager_patron_data() via URL, return JSON.
 *
 */
function get_voyager_patron_json() {
  $force_refresh = FALSE;
  if (isset($_GET['force_refresh']) && $_GET['force_refresh']) {
    $force_refresh = TRUE;
  }
  drupal_json(get_voyager_patron_data($force_refresh));
}

/**
 * A workaround when Oracle support is not compiled into PHP
 * The results of this should be cached.
 *
 */
function voyagerQueryToJSON($query) {
  $json = exec('java -cp "' . dirname(__FILE__) . '/:' . dirname(__FILE__) . '/lib/ojdbc7-12.1.0.2.jar" voyagerQueryToJson "' . $query . '"');
  $encoding =  mb_detect_encoding($json, "auto");
  $json = mb_convert_encoding($json, $encoding, "UTF-8");
  return $json;
}
