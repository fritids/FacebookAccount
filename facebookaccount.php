<?PHP
/*
Plugin Name: FacebookAccount
Plugin URI: http://www.yallee.it
Description: A plugin to handle user registration and login through Facebook
Version: 0.1
Author: Ignazio Setti
Author URI: http://www.yallee.it
*/

/**
 * FACEBOOK SDK LIBRARY
 */

/**
 * WP HOOKS
 **/
add_action( 'plugins_loaded', 'fa_setup' );

remove_all_filters('authenticate');
add_filter('authenticate', 'wp_authenticate_username_password', 30, 3);
add_filter('authenticate', 'wp_authenticate_cookie', 20, 3);
add_filter('authenticate', 'fa_authenticate', 10, 3);


/**
 * END WP HOOKS
 **/

function is_login_page() {
	return in_array($GLOBALS['pagenow'], array('wp-login.php', 'wp-register.php'));
}

function fa_setup() {
	/* Set constant path to the Cleaner Gallery plugin directory. */
	define( 'FACEBOOKACCOUNT_DIR', plugin_dir_path( __FILE__ ));
	
	$pluginUrl = plugins_url( 'facebookaccount.js', __FILE__); 
	wp_register_script('facebookaccount', $pluginUrl);
	wp_enqueue_script('facebookaccount');
	
	/* Add the Facebook button to the register form */	
	add_action('register_form','fa_register_form');
	add_action('register_head', 'fa_register_message');
	add_action('login_form','fa_login_form', 10);
	//add_action('login_footer', 'fa_login_form', 10);
	//add_action('register_form_bottom', 'fa_login_form');
	add_filter( 'user_register', 'fa_user_register');
	do_action( 'custom_login_loaded' );	
}

function fa_user_register($userID) {
	update_user_meta($userID, 'fbuid', $_POST['fbuid']);
	return $userID;
}


function fa_authenticate($user, $username, $password) {
	global $wpdb;
	
	//$user = get_userdatabylogin($username);
	$fbuid = $user ? get_user_meta($user->ID, 'fbuid', true): false;
	$user = get_userdatabylogin( $username );
	// Stop from authenticating the Wordpress-way, try the FB authentication
	/* Load Facebook SDK */	
	require_once("facebook.php");
	$config = array();
	$config['appId'] = '196278210430536';
	$config['secret'] = '9c8f66483692db61879f926822175224';
	$facebook = new Facebook($config);
	$uid = $facebook->getUser();
	
	if ($fbuid) {	
		try {
			$user_profile = $facebook->api('/me','GET');
			if ($fbuid == $user_profile['id']) {
				$user = new WP_User($user->ID);
				//return $user;
			}
			else {
				$user = new WP_Error('denied', __("<strong>ERRORE</strong>: L'utente non ha ancora effettuato l'accesso a Facebook.") );
			}
		
		} catch(FacebookApiException $e) {
			$user = new WP_Error('denied', __("<strong>ERRORE</strong>: L'utente non ha ancora effettuato l'accesso su Facebook.") );
		} 
	}
	else {
		try {
			$user_profile = $facebook->api('/me','GET');
			// User doesn't have an fbuid, so compare emails to see if the same person
			if($user && $user->user_email == $user_profile['email']) {
				// Same person, store fbuid
				update_user_meta($user->ID, 'fbuid', $fbuid);
				$user = new WP_User($user->ID);
			}
			else {
				// User doesn't event exist. Auto register through FB?
				if(isset($_POST['fbuid']) && !empty($_POST['fbuid'])) {
					$user = new WP_Error('denied', __('<strong>ERRORE</strong>: L\'account Facebook non corrisponde ad alcun utente. Vai alla <a href="'.wp_login_url().'?action=register">pagina di registrazione</a>.'));
					remove_filter('authenticate', 'wp_authenticate_username_password', 30, 3);
					remove_filter('authenticate', 'wp_authenticate_cookie', 20, 3);
				}
			}
		}
		catch(FacebookApiException $e) {
			$user = new WP_Error('denied', __("<strong>ERRORE</strong>: L'utente non ha ancora effettuato l'accesso su Facebook.") );
		} 
	}
	
	return $user;
}

function fa_register_form() {
	echo get_facebook_initialization();
	echo '<input type="hidden" value="" id="fbuid" />';
	echo '<a href="#" onclick="register_with_facebook()" id="fa-register-button" alt="Register with Facebook"></a>';
}

function fa_register_message() {
	die(print_r($_POST));
}

function fa_login_form() {
	echo get_facebook_initialization();
	echo '<input type="hidden" value="" id="fbuid" />';
	echo '<a href="#" onclick="login_with_facebook()" id="fa-login-button" alt="Login with Facebook"></a>';
}

function get_facebook_initialization() {
	$channelUrl = plugins_url( 'channel.html', __FILE__); 
	$content =  <<<EOD
<div id="fb-root"></div>
<script>
  window.fbAsyncInit = function() {
    FB.init({
      appId      : '196278210430536', // App ID
      channelURL : '$channelUrl', // Channel File
      status     : true, // check login status
      cookie     : true, // enable cookies to allow the server to access the session
      oauth      : true, // enable OAuth 2.0
      xfbml      : true  // parse XFBML
    });

    // Additional initialization code here
  };

  // Load the SDK Asynchronously
  (function(d){
     var js, id = 'facebook-jssdk'; if (d.getElementById(id)) {return;}
     js = d.createElement('script'); js.id = id; js.async = true;
     js.src = "//connect.facebook.net/en_US/all.js";
     d.getElementsByTagName('head')[0].appendChild(js);
   }(document));
</script>
EOD;
	
	return $content;
}

?>