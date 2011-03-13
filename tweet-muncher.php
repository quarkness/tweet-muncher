<?php
/**
 * Plugin Name: Tweet Muncher
 * Plugin URI: http://labs.nrcmedia.nl/
 * Description: Imports certain tweets as posts
 * Version: 0.1
 * Author: Ivo van Doesburg
 * Author URI: http://labs.nrcmedia.nl/
 *
 */


function twm_load_defaults() {
	return Array(
		'category' => 'Tweet',
		'search' => 'belachelijk',
	);
}

function twm_activate() {
	update_option('twm_settings', twm_load_defaults());
}

function twm_deactivate() {
	delete_option('twm_settings');
}

function twm_admin_page()
{
	$message = '';	

	$settings = get_option('twm_settings');

	if ($_POST) {
		$settings = Array(
			'category' => $_POST['twm']['category'],
			'search' => $_POST['twm']['search'],
		);
		update_option('twm_settings', $settings);
		$message = '<div class="updated"><p><strong>Okido.</strong></p></div>';	
	}

	echo '<div class="wrap">';
	echo '<h2>Tweet Muncher settings</h2>';
	echo $message;
	echo '<form method="post" action="options-general.php?page=nmt-notifier.php">';
	echo "<p>Stel dit even in, wil je.</p>";
	echo '<p>category: <input type="text" name="twm[category]" size="100" value="' . $settings['category'] . '" />';
	echo '<p>search: <input type="text" name="twm[search]" size="100" value="' . $settings['search'] . '" />';
	echo '<p class="submit"><input class="button-primary" type="submit" method="post" value="Update Options"></p>';
	echo '</form>';
	echo '</div>';
}


add_action('admin_menu', 'twm_add_options_page');
function twm_add_options_page()
{
	add_options_page( 'Tweet Muncher', 'Tweet Muncher', 8, 'tweet-muncher.php', 'twm_admin_page' );
}

register_activation_hook(__FILE__, 'twm_activate');
register_deactivation_hook( __FILE__, 'twm_deactivate');

