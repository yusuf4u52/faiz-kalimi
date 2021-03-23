<!DOCTYPE html>

<!-- saved from url=(0029)http://bootswatch.com/flatly/ -->

<html lang="en">

<head>
  <?php include('_head.php'); ?>

</head>

<body>

  <nav class="navbar navbar-default">
    <div class="container-fluid">
      <div class="navbar-header">
        <a class="navbar-brand font-bold" href="/users/">Faizul Mawaidil Burhaniya (Kalimi Mohalla)</a>
      </div>
    </div>
  </nav>

  <div class="container">

    <!-- Forms

      ================================================== -->


    <div class="row">
      <div class="col-lg-12">


        <?php
        session_start(); //session start

        include('connection.php');
        require_once('libraries/Google/autoload.php');

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


        //Display user info or display login url as per the info we have.
        echo '<div style="margin:20px">';
        if (isset($authUrl) || isset($_GET['status'])) {
          //show login url
          echo '<div align="center">';
          echo '<h1>Login with Google</h1>';
          echo '<div><h3>Please click login button to connect to Google.</h3></div>';
          echo '<a class="login" href="' . $authUrl . '"><img src="images/google-login-button.png" /></a>';
          echo '<br><br><br><a href="mailto:kalimifaiz@gmail.com">kalimifaiz@gmail.com</a>';
          echo '</div>';
        } else {

          $user = $service->userinfo->get(); //get user info 

          $_SESSION['fromLogin'] = "true";
          $_SESSION['email'] = $user->email;
          header('Location: index.php');
        }
        echo '</div>';


        ?>





      </div>


    </div>

  </div>

  <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
  <?php
  if (isset($_GET['status'])) {
  ?>
    <script type="text/javascript">
    alert('<?php echo $_GET['status']; ?>');
    window.location.href='/index.html';
    </script>

  <?php } ?>

  </body>

</html>