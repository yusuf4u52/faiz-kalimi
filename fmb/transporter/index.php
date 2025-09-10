<?php include('header.php');

session_start();

include('../users/connection.php');
require_once('../users/libraries/Google/autoload.php');

/************************************************
  Make an API request on behalf of a user. In
  this case we need to have a valid OAuth 2.0
  token for the user, so we need to send them
  through a login flow. To do this we need some
  information from our API console project.
 ************************************************/
$client = new Google_Client();
$client->setClientId($client_id);
$client->setClientSecret($client_secret);
$client->setRedirectUri($redirect_uri);
$client->addScope("email");
$client->addScope("profile");

/************************************************
When we create the service here, we pass the
client to it. The client then queries the service
for the required scopes, and uses that when
generating the authentication URL later.
 ************************************************/
$service = new Google_Service_Oauth2($client);

/************************************************
If we have a code back from the OAuth 2.0 flow,
we need to exchange that with the authenticate()
function. We store the resultant access token
bundle in the session, and redirect to ourself.
 */

if (isset($_GET['code'])) {
	$client->authenticate($_GET['code']);
	$_SESSION['access_token'] = $client->getAccessToken();
	header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
	exit;
}

/************************************************
If we have an access token, we can make
requests, else we generate an authentication URL.
 ************************************************/
if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
	$client->setAccessToken($_SESSION['access_token']);
} else {
	$authUrl = $client->createAuthUrl();
}
if (isset($authUrl) || isset($_GET['status'])) { ?>
	<div class="content mt-4 text-center">
		<div class="container">
			<div class="row">
				<div class="col-12 offset-sm-2 col-sm-8 offset-lg-3 col-lg-6">
					<div class="card">
						<div class="card-body">
							<img class="img-fluid mx-auto d-block" src="styles/img/logo.png" alt="Faiz ul Mawaid il Burhaniyah (Kalimi Mohalla - Poona)" 
								width="253" height="253" />
							<hr>
							<img class="img-fluid mx-auto d-block" src="../users/assets/img/sakat-hoi.png" alt="sakat hoi"
								width="877" height="284" />
							<img class="img-fluid mx-auto d-block" src="../users/assets/img/pakavi.png" alt="pakavi" width=981
								height="254" />
							<hr>
							<h3>Are you Kalimi Mohallah Transporter?</h3>
							<a class="btn btn-light btn-lg" href="<?php echo $authUrl; ?>">Login</a>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
<?php } else {
	$user = $service->userinfo->get();
	$_SESSION['fromLogin'] = "true";
	$_SESSION['email'] = $user->email;
	header('Location: home.php');
}

if (isset($_GET['status'])) { ?>
	<script type="text/javascript">
		alert('<?php echo $_GET['status']; ?>');
	</script>
<?php }

include('footer.php'); ?>
