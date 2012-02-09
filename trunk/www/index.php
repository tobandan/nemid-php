<?php
error_reporting(E_ALL);

/**
    This is a script for test of nemid.php - a php library for using
    the Danish NemID for authentication.
    
    NemID authenticating works by sending a nonce via an applet to
    NemID. NemID returns a xml document signed with the user's
    private key. The returned document contains, in addition to the
    nonce, the user's OCES certificate and the certificate path to
    the root.
    
    Nemid.php prepares the parameters for the form/applet, receives
    the signed xml document, checks the signature and extracts the
    certificates. It then checks the revocation status*) for the
    certificate and verifies the certificate chain.
    
    Optionally it then looks up*) the users CPR number (Danish
    national identification number) using the PID supplied in the
    user's certificate. Only public institutions are allowed to do
    this.
    
    Private entities may perform a CPR confirmation*) using the PID
    and a user supplied CPR number. Nemid.php does not support CPR
    validation.
    
    *) requires registration of IP number, a service id and a VOCES
    certificate from NemID.
    
    The distribution includes the necessary private key and
    certificate for connecting to NemID's test environment.
    
    I you are going to use the library for real you will need (the
    sha256 fingerprint for) the 'TRUST2408 OCES Primary CA' root
    certificate.
    
    This script is configured to disable the ocspcheck - remember to
    enable it if you are registered at NemID.
    
    You will need to go to https://appletk.danid.dk/testtools/ -
    using oces/nemid4all for access - to create a testuser. Read
    'Vejledning i brug af test tools' (in Danish) - part of the TU
    package mentioned below - for further information.
    
    Comprehensive documention is available in the  TU (Service
    Provider in Danish) package available here:
    
    https://www.nets-danid.dk/produkter/for_tjenesteudbydere/
    nemid_tjenesteudbyder/nemid_tjenesteudbyder_support/
    tjenesteudbyderpakken/
    
    Enjoy!

*/

const DISABLE_OCSP_CHECK = true;

require('../lib/Nemid.php');
session_start(); # a session is needed for remembering the nonce

$i = nemid(new Nemidconfig(), new Trustedrootdigests());

header('content-type: text/plain');
print_r($i);

/**
	Configuration for testing NemID - 
	
*/

class Nemidconfig {
	public $privatekey 			= '../certs/testkey.pem';
	public $privatekeypass 		= '';
	public $certificate 		= '../certs/testcertifikat.pem';
	public $serverurlprefix		= 'https://appletk.danid.dk';  # test
	#public $serverurlprefix	= 'https://applet.danid.dk';   # prod
	public $nonceprefix			= 'nemid-test-';
}

/**
	This is not used in this demo as it requires a VOCES certificate/key and a serviceid
*/

class Pidcprconfig {
	public $server		    	= 'https://pidws.pp.certifikat.dk/pid_serviceprovider_server/pidxml/'; # test
	#public $server           	= 'https://pidws.certifikat.dk/pid_serviceprovider_server/pidxml/';    # prod
	public $certificateandkey 	= '';
	public $privatekeypass		= '';
	public $serviceid			= '';
}

/**
	Digest (using sha256 of the der encoded certificate) of root for the test environment
*/

class Trustedrootdigests {
	public $trustedrootdigests = array (
	  'preproductionCertificateOcesII' 	=> '0e2fd1fda36a4bf3995e28619704d60e3382c91e44a2b458ab891316380b1d50',
	);
}

/**
	Driver for testing nemid:
		prepares parameters for the template
		checks the returned signature and the certificatechain
		returns pid and cn.
*/

function nemid($nemidconfig, $trustedroots, $pidcprconfig = null)
{
	$p = isset($_POST['result']) ? $_POST['result'] : false;
	if (!$p) {
		$nemidlogin = new WAYF\nemidlogin();
		$params = $nemidlogin->prepareparamsfornemid($nemidconfig);
		$_SESSION['nonce'] = $params['signproperties'];
		print render('nemid', $params);
		exit;
	} else {
		$error = null;
		if ($p === 'ok') {
		    print_r(base64_decode($p));
		    print_r($_POST);
			$nemid = new WAYF\NemidCertificateCheck();
			$certificate = $nemid->checkAndReturnCertificate($_POST['signature'], $_SESSION['nonce'], $trustedroots, DISABLE_OCSP_CHECK);
			unset($_SESSION['nonce']);
			$subject = end($certificate['tbsCertificate']['subject']);
			$pid = $subject['serialNumber'];
			$cn = $subject['commonName'];
			$cpr = null;
			if ($pidcprconfig) {
				$cpr = $nemid->pidCprRequest($pidcprconfig, $pid);
			}
		} elseif ($p === 'cancel') {
			$error = 'User canceled login';
		} else {
		    include '../lib/nemid-error-codes.php';
		    $errorcode = base64_decode($_POST['result']);
			$error = $errorcodes[$errorcode];
		}
		return compact('pid', 'cn', 'cpr', 'error');
	} 
}

function render($template, $vars = array())
{
    extract($vars);
    ob_start();
    include('../templates/' . $template . '.tpl.php');
    $content = ob_get_contents();
    ob_end_clean();
    return $content;
}
