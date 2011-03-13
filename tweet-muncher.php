<?php
/**
 * Plugin Name: Tweet Muncher
 * Plugin URI: https://github.com/quarkness/tweet-muncher
 * Description: Imports certain tweets as posts
 * Version: 0.4
 * Author: Ivo van Doesburg
 * Author URI: http://voorgevorderden.com/
 *
 */

add_action('twm_hourly_event', 'twm_import_posts');

function twm_activate() {
	wp_get_current_user();
	$settings = get_option('twm_settings');
	if(!is_array($settings))
	{
		update_option('twm_settings', Array(
			'category' => 'Tweet',
			'search' => 'belachelijk',
			'author_id' => $current_user->ID,
			)
		);
		update_option('twm_since_id', '');
	}
	wp_schedule_event(time(), 'hourly', 'twm_hourly_event');
}

function twm_deactivate() {
//	delete_option('twm_settings');
//	delete_option('twm_since_id');
	wp_clear_scheduled_hook('twm_hourly_event');
}

function twm_admin_page()
{
	echo '<h2>Tweet Muncher</h2>';
	if(isset($_GET['action']) && $_GET['action'] == 'import')
	{
		echo '<h3>Importing manually</h3>';
		twm_import_posts(true);
	}
	else
	{
		$message = '';	
	
		$settings = get_option('twm_settings');
		$since_id = get_option('twm_since_id');
	
		if ($_POST) {
			$settings['category'] = $_POST['twm']['category'];
			$settings['search'] = $_POST['twm']['search'];
			$settings['author_id'] = $_POST['twm']['author_id'];
			update_option('twm_settings', $settings);
			update_option('twm_since_id', $_POST['twm']['since_id']);
			$cat = get_term_by('name', $settings['category'], 'category');
			if(false === $cat)
			{	
				$cat_id = wp_create_category( $_POST['twm']['category'] );
				$message .= "<div class='updated'><p>Added category <strong>{$_POST['twm']['category']}</strong> with id <strong>{$cat_id}</strong></p></div>";	
			}
			$message .= '<div class="updated"><p><strong>Okido.</strong></p></div>';	
		}
	
		echo '<div class="wrap">';
		echo '<a href="options-general.php?page=tweet-muncher&amp;action=import">Import Manually</a>';
		echo '<h3>Settings</h3>';
		echo $message;
		echo '<form method="post" action="options-general.php?page=tweet-muncher">';
		echo "<p>Stel dit even in, wil je.</p>";
		echo '<p>category: <input type="text" name="twm[category]" size="100" value="' . $settings['category'] . '" />';
		echo '<p>search: <input type="text" name="twm[search]" size="100" value="' . $settings['search'] . '" />';
		echo '<p>since_id: <input type="text" name="twm[since_id]" size="100" value="' . $since_id . '" />';
		echo '<p>author_id: <input type="text" name="twm[author_id]" size="100" value="' . $settings['author_id'] . '" />';
		echo '<p class="submit"><input class="button-primary" type="submit" method="post" value="Update Options"></p>';
		echo '</form>';
		echo '</div>';
	}
}

function twm_import_posts($output = false)
{
	$settings = get_option('twm_settings');
	$since_id = get_option('twm_since_id');
	$base = 'http://search.twitter.com/search.json?';
	$url = $base . http_build_query(Array('q'=>$settings['search'], 'since_id'=>$since_id));
	if($output)
	{
		echo "<pre>";
		echo "<p>Querying {$url}</p>";
	}
	$response = twm_get_json_api($url);
	if($response['status'] != 'error')
	{
		if(count($response['results']) > 0)
		{
			update_option('twm_since_id', $response['results'][0]['id_str']);
			foreach($response['results'] as $tweet)
			{
				if($output)
					var_dump($tweet);
				$tweet_text = make_clickable($tweet['text']);
				$post_author = $settings['author_id'];
				$post_date_gmt = strtotime($tweet['created_at']);
				$post_title = "Tweet van {$tweet['from_user']}";
				$post_content = "<em><a href='http://twitter.com/{$tweet['from_user']}/status/{$tweet['id_str']}'>{$tweet['from_user']}</a>:</em> {$tweet_text}";
				$post_date_gmt = date('Y-m-d H:i:s', $post_date_gmt);
				$post_status = 'publish';
				preg_match_all('/(^|\s)#(\w+)/', $tweet['text'], $matches);
				$tags_input = join(', ', $matches[2]);
				$postdata = compact('post_author', 'post_date_gmt', 'post_content', 'post_title', 'post_status', 'tags_input');
				$cat = get_term_by('name', $settings['category'], 'category');
				$postdata['post_category'] = array($cat->term_id);
				
				if($output)
					print_r($postdata);
				$post_id = wp_insert_post($postdata);
				add_post_meta( $post_id, 'twm_tweet', $tweet, true );
			}
		}
		else
		{
			if($output)
				echo "No tweets after tweet {$since_id}";
		}
	}
	else
	{
		if($output)
			echo "Oops!";
	}
}

add_action('admin_menu', 'twm_add_options_page');
function twm_add_options_page()
{
	add_options_page( 'Tweet Muncher Settings', 'Tweet Muncher', 8, 'tweet-muncher', 'twm_admin_page' );
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

