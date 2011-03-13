<?php
/**
 * Plugin Name: Tweet Muncher
 * Plugin URI: http://voorgevorderden.com/
 * Description: Imports certain tweets as posts
 * Version: 0.1
 * Author: Ivo van Doesburg
 * Author URI: http://voorgevorderden.com/
 *
 */

function twm_activate() {
	wp_get_current_user();
	update_option('twm_settings', Array(
		'category' => 'Tweet',
		'search' => 'belachelijk',
		'author_id' => $current_user->ID,
		)
	);
	update_option('twm_since_id', 0);
}

function twm_deactivate() {
	delete_option('twm_settings');
	delete_option('twm_since_id');
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

function twm_test()
{
	$settings = get_option('twm_settings');
	$since_id = get_option('twm_since_id');
	$base = 'http://search.twitter.com/search.json?';
	$url = $base . http_build_query(Array('q'=>$settings['search'], 'since_id'=>$since_id));
	$response = twm_get_json_api($url);
	echo "<pre>";
	print_r($response);
	if($response['status'] != 'error')
	{
//		update_option('twm_since_id', $response['results'][0]['id']);
		foreach($response['results'] as $tweet)
		{
			$post_author = $settings['author_id'];
			$post_date = strtotime($tweet['created_at']);
//			$post_date = $post_date + 3600; // Uurtje extra, er gaat iets mis met de tijdzone ...
			$post_date = date('Y-m-d H:i:s', $post_date);
			
			$postdata = compact('post_author', 'post_date', 'post_content', 'post_title', 'post_status');
			$cat = get_term_by('name', $settings['category'], 'category');
			$postdata['post_category'] = array($cat->term_id);
			$post_id = wp_insert_post($postdata);
			add_post_meta( $post_id, 'twm_tweet', $tweet, true );
		}
	}
}

add_action('admin_menu', 'twm_add_options_page');
function twm_add_options_page()
{
	add_options_page( 'Tweet Muncher', 'Tweet Muncher', 8, 'tweet-muncher-admin', 'twm_admin_page' );
	add_options_page( 'Tweet Muncher Test', 'Tweet Muncher Test', 8, 'tweet-muncher-test', 'twm_test' );
}

function twm_get_json_api($url)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$response = curl_exec($ch);
	$info = curl_getinfo($ch);
	if(strlen(curl_error($ch) > 0))
		return Array('status'=>'error', 'error'=>curl_error($ch));

	// als response te json_decoden is, gedecodeerd teruggeven, anders status error
	if($d = json_decode($response, true))
		return $d;
	else
		return Array('status'=>'error', 'error'=>'http status ' . $info['http_code']);
}
	
register_activation_hook(__FILE__, 'twm_activate');
register_deactivation_hook( __FILE__, 'twm_deactivate');

