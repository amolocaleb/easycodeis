<?php
	// load up global things
	include_once 'autoloader.php';

	if ( isset( $_GET['state'] ) && FB_APP_STATE == $_GET['state'] ) { // coming from facebook
		// try and log the user in with $_GET vars from facebook 
		$fbLogin = tryAndLoginWithFacebook( $_GET );
	}

	if ( isset( $_GET['oauth_token'] ) && isset( $_GET['oauth_verifier'] ) && isset( $_SESSION['request_oauth_token'] ) && $_SESSION['request_oauth_token'] == $_GET['oauth_token'] ) {
		$eciTwitterApi = new eciTwitterApi( TWITTER_CONSUMER_KEY, TWITTER_CONSUMER_SECRET, $_SESSION['request_oauth_token'], $_SESSION['request_oauth_token_secret'] );
		$twitterAccessToken = $eciTwitterApi->getAccessToken( $_GET['oauth_verifier'] );

		$_SESSION['oauth_token'] = !empty( $twitterAccessToken['api_data']['oauth_token'] ) ?  $twitterAccessToken['api_data']['oauth_token'] : '';
		$_SESSION['oauth_token_secret'] = !empty( $twitterAccessToken['api_data']['oauth_token_secret'] ) ? $twitterAccessToken['api_data']['oauth_token_secret'] : '';

		$eciTwitterApi = new eciTwitterApi( TWITTER_CONSUMER_KEY, TWITTER_CONSUMER_SECRET, $_SESSION['oauth_token'], $_SESSION['oauth_token_secret'] );

		$twitterLogin = $eciTwitterApi->tryAndLoginWithTwitter();
	}

	$eciTwitterApi = new eciTwitterApi( TWITTER_CONSUMER_KEY, TWITTER_CONSUMER_SECRET );
	$twitterPreLoginData = $eciTwitterApi->getDataForLogin( TWITTER_CALLBACK_URL );

	// only if you are logged out can you view the login page
	loggedInRedirect();
?>
<!DOCTYPE html>
<html>
	<head>
		<!-- title of our page -->
		<title>Easy, Code Is | Login</title>

		<!-- include fonts -->
		<link href="https://fonts.googleapis.com/css?family=Coda" rel="stylesheet">

		<!-- need this so everything looks good on mobile devices -->
		<meta name="viewport" content="initial-scale=1.0, user-scalable=no" />

		<!-- css styles for our login page-->
		<link href="css/global.css" rel="stylesheet" type="text/css">
		<link href="css/login.css" rel="stylesheet" type="text/css">

		<!-- jquery -->
		<script type="text/javascript" src="js/jquery.js"></script>

		<!-- include our loader overlay script -->
		<script type="text/javascript" src="js/loader.js"></script>

		<script>
			$( function() { // once the document is ready, do things
				// initialize our loader overlay
				loader.initialize();

				$( '#login_button' ).on( 'click', function() { // onclick for our login button
					processLogin();
				} );

				$( '.form-input' ).keyup( function( e ) {
					if ( e.keyCode == 13 ) { // our enter key
						processLogin();
					}
				} );
			} );

			function processLogin() {
				// clear error message and red borders on signup click
				$( '#error_message' ).html( '' );
				$( '#error_message_fb_php' ).html( '' );
				$( '#error_message_twitter_php' ).html( '' );
				$( 'input' ).removeClass( 'invalid-input' );

				// assume no fields are blank
				var allFieldsFilledIn = true;

				$( 'input' ).each( function() { // simple front end check, loop over inputs
					if ( '' == $( this ).val() ) { // input is blank, add red border and set flag to false
						$( this ).addClass( 'invalid-input ');
						allFieldsFilledIn = false;
					}
				} );

				if ( allFieldsFilledIn ) { // all fields are filled in!
					loader.showLoader();

					// server side login
					$.ajax( {
						url: 'php/process_login.php',
						data: $( '#login_form' ).serialize(),
						type: 'post',
						dataType: 'json',
						success: function( data ) {
							if ( 'ok' == data.status ) {
								loader.hideLoader();
								window.location.href = "index.php";
							} else if ( 'fail' == data.status ) {
								$( '#error_message' ).html( data.message );
								loader.hideLoader();
							}
						}
					} );
				} else { // some fields are not filled in, show error message and scroll to top of page
					$( '#error_message' ).html( 'All fields must be filled in.' );
					$( window ).scrollTop( 0 );
				}
			}
		</script>
	</head>
	<body>
		<div class="site-header">
			<div class="site-header-pad">
				<a class="header-home-link" href="index.php">
					Easy, Code Is
				</a>
			</div>
		</div>
		<div class="site-content-container">
			<div class="site-content-centered">
				<div class="site-content-section">
					<div class="site-content-section-inner">
						<div class="section-heading">Login</div>
						<form id="login_form" name="login_form">
							<div id="error_message" class="error-message">
								<?php if ( isset( $_SESSION['eci_login_required_to_connect_facebook'] ) && $_SESSION['eci_login_required_to_connect_facebook'] ) : ?>
									<div style="margin-bottom:10px;">
										An account already exists with that email address. To connect your Facebook account, enter your password.
									</div>
								<?php endif; ?>
								<?php if ( isset( $_SESSION['eci_login_required_to_connect_twitter'] ) && $_SESSION['eci_login_required_to_connect_twitter'] ) : ?>
									<div style="margin-bottom:10px;">
										An account already exists with that email address. To connect your Twitter account, enter your password.
									</div>
								<?php endif; ?>
							</div>
							<div>
								<div class="section-label">Email</div>
								<div>
									<?php if ( isset( $_SESSION['fb_user_info']['email'] ) ? $_SESSION['fb_user_info']['email'] : '' ) : ?>
										<?php $inputEmail = $_SESSION['fb_user_info']['email']; ?>
									<?php elseif ( isset( $_SESSION['tw_user_info']['email'] ) ? $_SESSION['tw_user_info']['email'] : '' ) : ?>
										<?php $inputEmail = $_SESSION['tw_user_info']['email']; ?>
									<?php else : ?>
										<?php $inputEmail = ''; ?>
									<?php endif; ?>
									<input class="form-input" type="text" name="email" value="<?php echo $inputEmail; ?>" />
								</div>
							</div>
							<div class="section-mid-container">
								<div class="section-label">Password</div>
								<div><input class="form-input" type="password" name="password" /></div>
							</div>
						</form>
						<div class="section-action-container">
							<div class="section-button-container" id="login_button">
								<div>Login</div>
							</div>
						</div>
						<div class="section-action-container">
							- OR -
						</div>
						<div class="section-action-container">
							<div id="error_message_fb_php" class="error-message">
								<?php if ( !empty( $fbLogin['status'] ) && 'fail' == $fbLogin['status'] ) : // we have a facebook error to display ?>
									<?php echo $fbLogin['message']; ?>
								<?php endif; ?>
							</div>
						</div>
						<div class="section-action-container">
							<a href="<?php echo getFacebookLoginUrl(); ?>" class="a-fb">
								<div class="fb-button-container">
									Login with Facebook (PHP)
								</div>
							</a>
						</div>
						<div class="section-action-container">
							<div id="error_message_twitter_php" class="error-message">
								<?php if ( 'fail' == $twitterPreLoginData['status'] ) : ?>
									<div>
										<?php echo $twitterPreLoginData['message']; ?>
									</div>
								<?php endif; ?>
							</div>
						</div>
						<div class="section-action-container">
							<a href="<?php echo $twitterPreLoginData['twitter_login_url'] ;?>" class="a-tw">
								<div class="tw-button-container">
									Login with Twitter (PHP)
								</div>
							</a>
						</div>
						<div class="section-footer-container">
							Not a member? <a class="a-default" href="signup.php">Sign Up</a>
						</div>
					</div>
				</div>
			</div>
		</div>
	</body>
</html>