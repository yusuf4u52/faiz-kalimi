<?php
session_set_cookie_params([
	'lifetime' => 0,
	'path' => '/',
	'domain' => '',
	'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
	'httponly' => true,
	'samesite' => 'Lax',
]);
session_start();

// Log everything, but never display raw errors/stack traces to visitors —
// this is the public-facing login page, reachable by anyone.
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

include('users/connection.php');
require_once('users/helpers.php');
require_once('users/libraries/Google/autoload.php');

function clear_login_session(): void
{
	$_SESSION = [];
	if (ini_get('session.use_cookies')) {
		$params = session_get_cookie_params();
		setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
	}
	session_unset();
	session_destroy();
}

/***********************************************
  Make an API request on behalf of a user. In
  this case we need to have a valid OAuth 2.0
  token for the user, so we need to send them
  through a login flow. To do this we need some
  information from our API console project.
 **************************************************/
$client = new Google_Client();
$client->setClientId($client_id);
$client->setClientSecret($client_secret);
$client->setRedirectUri($redirect_uri);
$client->addScope("email");
$client->addScope("profile");

$client->setAccessType('offline');
$client->setPrompt('consent');

/************************************************
When we create the service here, we pass the
client to it. The client then queries the service
for the required scopes, and uses that when
generating the authentication URL later.
 **************************************************/
$service = new Google_Service_Oauth2($client);

/************************************************
If we have a code back from the OAuth 2.0 flow,
we need to exchange that with the authenticate()
function. We store the resultant access token
bundle in the session, and redirect to ourself.

The state check guards against login CSRF: without
it, an attacker could trick a victim's browser into
completing an OAuth flow bound to the attacker's own
Google account, silently logging the victim in as
someone else.
 *************************************************/
if (isset($_GET['code'])) {
	$expectedState = $_SESSION['oauth2state'] ?? null;
	unset($_SESSION['oauth2state']);

	if ($expectedState === null || !isset($_GET['state']) || !hash_equals($expectedState, (string) $_GET['state'])) {
		header('Location: ' . $redirect_uri . '?status=' . rawurlencode('Login request could not be verified. Please try again.'));
		exit;
	}

	try {
		$client->authenticate($_GET['code']);
		$_SESSION['access_token'] = $client->getAccessToken();
		header('Location: ' . $redirect_uri);
		exit;
	} catch (Exception $e) {
		error_log('[index.php] OAuth code exchange failed: ' . $e->getMessage());
		header('Location: ' . $redirect_uri . '?status=' . rawurlencode('Login failed. Please try again.'));
		exit;
	}
}

/************************************************
If we have an access token, we can make
requests, else we generate an authentication URL.
 *************************************************/
if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
	$client->setAccessToken($_SESSION['access_token']);
	if ($client->isAccessTokenExpired()) {
		clear_login_session();
		header('Location: ' . $redirect_uri . '?status=' . rawurlencode('Session expired. Please login again.'));
		exit;
	}
} else {
	$oauthState = bin2hex(random_bytes(16));
	$_SESSION['oauth2state'] = $oauthState;
	$client->setState($oauthState);
	$authUrl = $client->createAuthUrl();
}

if (isset($authUrl) || isset($_GET['status'])) {
	include('users/header.php'); ?>
	<div class="content mt-4 text-center">
		<div class="container">
			<div class="row">
				<div class="col-12 offset-sm-2 col-sm-8 offset-lg-3 col-lg-6">
					<div class="card">
						<div class="card-body">
							<img class="img-fluid mx-auto d-block" src="assets/img/logo.avif"
								alt="Faiz ul Mawaid il Burhaniyah (Kalimi Mohalla - Poona)" width="253" height="253" />
							<hr>
							<?php if (isset($_GET['status'])) { ?>
								<div class="alert alert-danger" role="alert">
									<?php echo e((string) $_GET['status']); ?>
								</div>
							<?php } ?>
							<img class="img-fluid mx-auto d-block" src="assets/img/sakat-hoi.avif" alt="sakat hoi"
								width="877" height="284" />
							<img class="img-fluid mx-auto d-block" src="assets/img/pakavi.avif" alt="pakavi" width=981
								height="254" />
							<hr>
							<h3>Already have Kalimi Mohalla Sabil?</h3>
							<?php if (isset($authUrl)) { ?>
								<a class="btn btn-light btn-lg" href="<?php echo e($authUrl); ?>"><i class="bi bi-google"></i> Login with Google</a>
							<?php } ?>
						</div>
					</div>

				<?php include('users/footer.php');
} else {
	// A valid, unexpired access token is already in the session — confirm
	// the identity it belongs to and drop the user straight into the app.
	try {
		$user = $service->userinfo->get();

		$_SESSION['fromLogin'] = "true";
		$_SESSION['email'] = $user->email;
		header('Location: users/index.php');
		exit;
	} catch (Exception $e) {
		error_log('[index.php] userinfo lookup failed: ' . $e->getMessage());
		clear_login_session();
		header('Location: ' . $redirect_uri . '?status=' . rawurlencode('Session expired. Please login again.'));
		exit;
	}
}