<?php
/*
Plugin Name: Tweep Roll
Plugin URI: http://www.mariasadventures.com/tweep-roll
Description: This widget outputs a list of the people you are following on Twitter. Like a Blogroll, but for Twitter!
Version: 1.0
Author: Maria Cheung <maria@mariasadventures.com>
Author URI: http://www.mariasadventures.com
*/

// Copyright (c) 2009 Maria Cheung. All rights reserved.
//
// Released under the GPL license
// http://www.opensource.org/licenses/gpl-license.php
//
// This is an add-on for WordPress
// http://wordpress.org/
//
// **********************************************************************
// This program is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
// **********************************************************************

define('MAGPIE_CACHE_ON', 0); //2.7 Cache Bug
define('MAGPIE_INPUT_ENCODING', 'UTF-8');

$twitter_options['widget_fields']['title'] = array('label'=>'Title:', 'type'=>'text', 'default'=>'');
$twitter_options['widget_fields']['username'] = array('label'=>'Username:', 'type'=>'text', 'default'=>'');
$twitter_options['widget_fields']['num'] = array('label'=>'# of Tweeps:', 'type'=>'text', 'default'=>'5');
$twitter_options['prefix'] = 'twitter';

// Display Twitter friends
function tweep_roll($username = '', $num = 10, $list = false) {

	global $twitter_options;

	// Get a list of the users Twitter friends, using Snoopy
	require_once(ABSPATH.WPINC.'/class-snoopy.php');
	$snoop = new Snoopy;
	$snoop->agent = 'Tweep Roll http://www.mariasadventures.com/tweep-roll';
	$snoop->fetch('http://twitter.com/statuses/friends/'.$username.'.json');

	if ($list) echo '<ul class="twitter">';
	
	if ($username == '') {
		if ($list) echo '<li>';
		echo 'RSS not configured';
		if ($list) echo '</li>';
	} else {
			if ( empty($snoop->results) ) {
				if ($list) echo '<li>';
				echo 'I have no Twitter friends :(';
				if ($list) echo '</li>';
			} else {
				$results = json_decode($snoop->results);
				for ($i=1; $i<=$num; $i++) {
					if($encode_utf8) $msg = utf8_encode($msg);
					$link = $message['link'];
				
					if ($list) echo '<li class="twitter-item">'; elseif ($num != 1) echo '<p class="twitter-message">';
					
					if ($linked != '' || $linked != false) {
						if($linked == 'all')  { 
							$msg = '<a href="'.$link.'" class="twitter-link">'.$msg.'</a>';  // Puts a link to the status of each tweet 
						} else {
							$msg = $msg . '<a href="'.$link.'" class="twitter-link">'.$linked.'</a>'; // Puts a link to the status of each tweet
						}
					} 
					$location = (!empty($results[$i]->location) ? '('.$results[$i]->location.')' : '');
					$description = (!empty($results[$i]->description) ? ' - '.$results[$i]->description : '');
					echo '<a href="http://twitter.com/'.$results[$i]->screen_name.'">'.$results[$i]->screen_name.'</a> '.$location.$description;
          
					if ($list) echo '</li>'; elseif ($num != 1) echo '</p>';
				}
			}
		}
		if ($list) echo '</ul>';
	}

// Tweep Roll widget stuff
function widget_tweep_roll_init() {

	if (!function_exists('register_sidebar_widget'))
		return;
	
	$check_options = get_option('widget_tweep_roll');
	if ($check_options['number']=='') {
		$check_options['number'] = 10;
		update_option('widget_tweep_roll', $check_options);
	}
  
	function widget_tweep_roll($args, $number = 10) {

		global $twitter_options;
		
		// $args is an array of strings that help widgets to conform to
		// the active theme: before_widget, before_title, after_widget,
		// and after_title are the array keys. Default tags: li and h2.
		extract($args);

		// Each widget can store its own options. We keep strings here.
		include_once(ABSPATH . WPINC . '/rss.php');
		$options = get_option('widget_tweep_roll');
		
		// fill options with default values if value is not set
		$item = $options[$number];
		foreach($twitter_options['widget_fields'] as $key => $field) {
			if (! isset($item[$key])) {
				$item[$key] = $field['default'];
			}
		}
		
		$messages = fetch_rss('http://twitter.com/statuses/friends/'.$username.'.xml');


		// These lines generate our output.
		echo $before_widget . $before_title . $item['title'] . $after_title;
		tweep_roll($item['username'], $item['num'], true);
		echo $after_widget;
				
	}

	// This is the function that outputs the form to let the users edit
	// the widget's title. It's an optional feature that users cry for.
	function widget_tweep_roll_control($number) {
	
		global $twitter_options;

		// Get our options and see if we're handling a form submission.
		$options = get_option('widget_tweep_roll');
		if ( isset($_POST['twitter-submit']) ) {

			foreach($twitter_options['widget_fields'] as $key => $field) {
				$options[$number][$key] = $field['default'];
				$field_name = sprintf('%s_%s_%s', $twitter_options['prefix'], $key, $number);

				if ($field['type'] == 'text') {
					$options[$number][$key] = strip_tags(stripslashes($_POST[$field_name]));
				} elseif ($field['type'] == 'checkbox') {
					$options[$number][$key] = isset($_POST[$field_name]);
				}
			}

			update_option('widget_tweep_roll', $options);
		}

		foreach($twitter_options['widget_fields'] as $key => $field) {
			
			$field_name = sprintf('%s_%s_%s', $twitter_options['prefix'], $key, $number);
			$field_checked = '';
			if ($field['type'] == 'text') {
				$field_value = htmlspecialchars($options[$number][$key], ENT_QUOTES);
			} elseif ($field['type'] == 'checkbox') {
				$field_value = 1;
				if (! empty($options[$number][$key])) {
					$field_checked = 'checked="checked"';
				}
			}
			
			printf('<p style="text-align:right;" class="twitter_field"><label for="%s">%s <input id="%s" name="%s" type="%s" value="%s" class="%s" %s /></label></p>',
				$field_name, __($field['label']), $field_name, $field_name, $field['type'], $field_value, $field['type'], $field_checked);
		}

		echo '<input type="hidden" id="twitter-submit" name="twitter-submit" value="1" />';
	}
	
	function widget_tweep_roll_setup() {
		$options = $newoptions = get_option('widget_tweep_roll');
		
		if ( $options != $newoptions ) {
			update_option('widget_tweep_roll', $newoptions);
			widget_tweep_roll_register();
		}
	}	
	
	function widget_tweep_roll_register() {
		
		$options = get_option('widget_tweep_roll');
		$dims = array('width' => 300, 'height' => 300);
		$class = array('classname' => 'widget_tweep_roll');

		$name = 'Tweep Roll';
		$id = "tweep-roll"; // Never never never translate an id
		wp_register_sidebar_widget($id, $name, 'widget_tweep_roll', $class, $i);
		wp_register_widget_control($id, $name, 'widget_tweep_roll_control', $dims, $i);
		
		add_action('sidebar_admin_setup', 'widget_tweep_roll_setup');
	}

	widget_tweep_roll_register();
}

// Run our code later in case this loads prior to any required plugins.
add_action('widgets_init', 'widget_tweep_roll_init');
?>