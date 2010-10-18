CONTENTS

Node based authentication with CUWebLogin.

Authenticating programmatically with CUWebLogin.

Logging out.
  - Via PHP.
  - Via Link or Javascript.

Voyager Patron Information.
  - Via PHP.
  - Via Javascript.

LDAP Patron Information.
  - Via PHP.
  - Via Javascript.

Misc. Voyager Functions
  - get_voyager_connection()
  - _set_error_message($message, $stid=0)

____________________________________________________
Node based authentication with CUWebLogin.

(This functionality requires that CUWebAuth Apache module, version 2 is
installed and configured.)

By default, all nodes now have an extra form field--a check box with the label
"Require CUWebLogin?"--that indicates if particular nodes should require
CUWebLogin. This field applies to all nodes regardless of type, and is weighted
to be the first field on node add/edit screens.

Prior to displaying a node with this field checked, Drupal checks for the
existence of a 'netid' cookie set with the user's Cornell Net ID. If this cookie
does not exist, the user is redirected to a CUWebLogin protected script that
sets this cookie upon successful authentication, then returns to the originally
requested URI where the original authentication check will then succeed.

As a convenience for content managers, authentication checks are not performed
for users logged into Drupal with the following roles:
  - administrator
  - content manager
  - webvision-admin

This authentication bypass is designed to be configurable in the future.


____________________________________________________
Authenticating programmatically.

(This functionality requires that CUWebAuth Apache module, version 2 is
installed and configured.)

The same node based authentication mechanism can be called directly within PHP
for arbitrary purposes with the following method:
  cu_authenticate()

...which checks for the existence of a 'netid' cookie set with the user's Cornell
 Net ID. If it finds the cookie, it returns the value of the cookie. If this
cookie does not exist, the user is redirected to a CUWebLogin protected script
that sets this cookie upon successful authentication, then returns to the
originally requested URI where the original authentication check will then
succeed.


UPDATE: 9/2010

PROBLEM:

The cul_common Drupal module stores a user's Cornell net ID in a cookie as a way of carrying this information across the boundary between a static CUWebAuth (CUWA) resource and a dynamic resource that behaves as if it were protected by CUWA.

It was pointed out that a malicious user with sufficient knowledge and means could manipulate this cookie for resources that rely on this value to display user specific data.


SOLUTION:

The following solution ensures that through the provided API, only the net ID returned by CUWA is available to Drupal. A malicious user who tries to manipuate the netid cookie will go through the CUWA authentication process again, where the correct value will be restored before returning to Drupal and processing the request.

A variation of the following solution is already being used by Jim Reidy's CUWebAuth Drupal login module, so only things relying on the older cul_common module had the identified security issue, and therefore, only the My Account resource is affected.

These basic authentication functions can work while under a CUWA protected area or not:

        function verify_netid() {
          $verified = FALSE;
          if (isset($_COOKIE['netid']) && isset($_COOKIE['verify_netid'])) {
            if (crypt(md5($_COOKIE['netid']), md5(get_and_set_cuwa_salt())) == $_COOKIE['verify_netid']) {
              $verified = TRUE;
            }
          }
          return $verified;
        }

        function cu_authenticate() {
          $netID = getenv('REMOTE_USER');
          if (isset($netID) && $netID) {
            return $netID;
          } else if (verify_netid()) {
            return $_COOKIE['netid'];
          } else {
            //bring the user back to the path they started with, try to avoid the internal node number.
            //assumes use of 'friendly' URL's
            get_and_set_cuwa_salt();
            unset($_REQUEST['destination']);
            drupal_goto(drupal_get_path('module','cul_common') . '/authenticate', 'destination=' . urlencode(request_uri()));
          }
        }

The intention here is that the cu_authenticate() function can be called by any Drupal code to conveniently invoke CUWA and retrieve a verified net ID value, thus making CUWA usable in a non-static environment.

Initially, a user is redirected to a CUWA protected directory that contains a PHP script that retrieves the REMOTE_USER environment variable set by the CUWA Apache module, along with a salt value stored in the Drupal cache, and creates two cookies: one with the netid value, and one with an encrypted value based on this netid and the salt:

        require_once(dirname(__FILE__) . '../../../../../default/settings.php');

        $salt = '';
        $url = parse_url($db_url);

        // Decode url-encoded information in the db connection string
        $url['user'] = urldecode($url['user']);
        // Test if database url has a password.
        $url['pass'] = isset($url['pass']) ? urldecode($url['pass']) : '';
        $url['host'] = urldecode($url['host']);
        $url['path'] = urldecode($url['path']);

        // Allow for non-standard MySQL port.
        if (isset($url['port'])) {
            $url['host'] = $url['host'] .':'. $url['port'];
        }

        $connection = @mysql_connect($url['host'], $url['user'], $url['pass'], TRUE, 2);
        if (!$connection || !mysql_select_db(substr($url['path'], 1))) {
            // Show error screen otherwise
            echo mysql_error();
        } else {
            $result = mysql_query('SELECT data from cache WHERE cid = "cuwa_net_id_salt"');
            if (!$result) {
                die('Invalid query: ' . mysql_error());
            } else {
                while ($row = mysql_fetch_assoc($result)) {
                    $salt = $row['data'];
                }
            }
        }
        mysql_close($connection);

        $netid = getenv('REMOTE_USER');
        if (isset($netid) && $netid) {
            setcookie('netid', $netid, 0, '/', '.cornell.edu');
            setcookie('verify_netid', crypt(md5($netid), md5($salt)), 0, '/', '.cornell.edu');
        }

        header('Location: http://' . $_SERVER['HTTP_HOST'] . $_GET['destination']);
        exit();

(It should be noted that values assigned to PHP super globals, such as $_SESSION and $_COOKIE are lost when the assigning script performs a redirect. The accepted workaround for this is to call setcookie() explicitly, later accessing the value through $_COOKIE.)

Upon successfully authenticating, the user is redirected back into Drupal, accessing the originally requested URL, where the cu_authenticate() function is called a second time, this time safely returning the verified net ID value.

As for the salt value, it is randomly generated and stored in Drupal's cache:

        function get_random_string($length=10, $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz') {
            $string = '';
            for ($p = 0; $p < $length; $p++) {
                $string .= $characters[mt_rand(0, strlen($characters))];
            }
            return $string;
        }

        function get_and_set_cuwa_salt($refresh=FALSE) {
            static $cuwa_salt;
            global $cuwa_salt_cache_name;
            if (($cached = cache_get($cuwa_salt_cache_name, 'cache')) && ! empty($cached->data) && ! $refresh) {
                $cuwa_salt = $cached->data;
            } else {
                $cuwa_salt = get_random_string();
                cache_set($cuwa_salt_cache_name, $cuwa_salt, 'cache');
            }
            return $cuwa_salt;
        }

This value is updated every time the Drupal cron runs (we typically set this to run every hour):

        function cul_common_cron() {
          get_and_set_cuwa_salt(TRUE);
        }

So, even if someone has this source code, they cannot know the value of the verify_netid cookie being used at any given time on any Drupal site that uses this code.


____________________________________________________
Logging out.

Via PHP.

The following method provides a functional logout mechanism:
  cuwebauth_logout($logout_url=NULL)

This method destroys the 'netid' cookie, and if a $logout_url is provided, it
redirects the user to the indicated URL.


Via Link or Javascript.

Similarly, a user can be provided a link to the logout function, or it may be
called via an AJAX GET request:
  http://<domain>/cul_common.logout

The $logout_url can be provided in the query string:
  http://<domain>/cul_common.logout?logout_url=<url>

Obviously, the query string will need to be URL encoded; cuwebauth_logout()
automatically decodes the URL.


____________________________________________________
Voyager Patron Information

(This functionality requires that PHP be compiled with OCI libraries.)

Via PHP.

The following function can be called in PHP to retrieve user data from Voyager:
  Array get_voyager_patron_data($force_refresh=FALSE)

Internally, this is accomplished through a lookup based on the user's Cornell net
ID,  which is retrieved from the 'netid' cookie. If this cookie does not exist,
the CUWebLogin process already described is automatically invoked.

This lookup is not optimized with the appropriate indexes in Voyager, so by
default, the results of this method are cached in Drupal upon the first request
made for each user. Subsequent requests are retrieved from the optimized local
cache unless $force_refresh is set to TRUE. On a forced refresh, the data is
re-retrieved from Voyager and the local cache is updated.

The function returns a populated PHP array with the following keys:
  - 'patron_id'
  - 'patron_barcode'
  - 'first_name'
  - 'last_name'


Via Javascript.

The get_voyager_patron_data() function can also be called via an AJAX GET
request using the following URL:
  http://<domain>/cul_common.voyager

The result is JSON formatted with the same populated keys indicated above. A
forced refresh can be invoked through the following query string:
  http://<domain>/cul_common.voyager?forced_refresh=1


____________________________________________________
LDAP Patron Information

(This functionality requires that PHP be compiled with LDAP libraries.)

Via PHP.

The following function can be called in PHP to retrieve user data from the
Cornell LDAP directory:
  Array get_ldap_data($return_fields=NULL)

Internally, this is accomplished through a lookup based on the user's Cornell net
ID, which is retrieved from the 'netid' cookie. If this cookie does not exist,
the CUWebLogin process already described is automatically invoked.

By default, the function returns a populated PHP array with the following LDAP
attributes as keys:
  - 'eduPersonPrimaryAffiliation'
  - 'cornellEduAcadCollege'
  - 'givenName'
  - 'sn'
  - 'cornellEduCampusAddress'
  - 'cornellEduCampusPhone'
  - 'Mail'

Complete descriptions of these attributes, and other available attributes can be
found on the Cornell LDAP site:
  http://identity.cit.cornell.edu/ds/index.html

You can specify different attributes with the $return_fields argument, which
should be a list of LDAP attributes given either as a PHP array of strings, or a
comma delimited string.

The following is a list of allowed types that can be obtained using the
cornellEduType attribute:

  - academic
  - academic - admin
  - affiliate
  - affiliate - BTI
  - affiliate - BTI - retired
  - affiliate - CRESP
  - affiliate - CRESP - retired
  - affiliate - CUMC
  - affiliate - CURW
  - affiliate - CURW - emeritus
  - affiliate - Campus Club
  - affiliate - Cornell Alumni Magazine
  - affiliate - Cornell Compact
  - affiliate - EAP
  - affiliate - Journal of Economic Theory
  - affiliate - PRI
  - affiliate - Public Service Ctr
  - affiliate - ROTC
  - affiliate - Telluride
  - affiliate - USDA
  - affiliate - USDA - retired
  - alumni
  - cu-connect - directory
  - exception
  - exception - CBS temp
  - exception - CIT
  - exception - active
  - exception - program
  - exception - trustee
  - exception - w/sponsor
  - pending
  - retiree
  - special
  - staff
  - student
  - student - A&LS
  - student - A&S
  - student - AA&P
  - student - Arts
  - student - Engr
  - student - Extramural
  - student - Grad
  - student - Hotel
  - student - HumEc
  - student - I&LR
  - student - ITD
  - student - JGSM
  - student - Law
  - student - Vet
  - student - summer
  - student - visiting
  - temporary
  - unknown

The eduPersonPrimaryAffiliation attribute returns the same information without
the " - <college>" information, so it may be more useful in cases where you only
need to know if someone is, say, a student vs. faculty.


Via Javascript.

The get_ldap_data() function can also be called via an AJAX GET request using
the following URL:

  http://<domain>/cul_common.ldap

The result is JSON formatted with the same default populated keys indicated
above. Custom LDAP attributes can be specified with the following query string:
  http://<domain>/cul_common.ldap?return_fields=givenName,sn,Mail


____________________________________________________
Misc. Voyager Functions

get_voyager_connection()

This method encapsulates the username, password and URL for connecting to the
Voyager database through Oracle. It returns a connection object, or calls the
_set_error_message() function to report the error to Drupal watchdog.


_set_oracle_error_message($message, $stid=0)

This method makes it more convenient to report Oracle database errors to
watchdog, using a custom message. The $stid argument is the output of
oci_parse($conn, $query). This method is automatically called by the
get_voyager_connection() function if there is a problem connecting.

