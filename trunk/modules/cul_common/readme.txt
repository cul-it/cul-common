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

