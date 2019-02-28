<?php 
/*
Plugin Name: ALT Lab IPE CASE SPECIAL THINGS
Plugin URI:  https://github.com/
Description: For all kinds of things to customize
Version:     1.0
Author:      ALT Lab
Author URI:  http://altlab.vcu.edu
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Domain Path: /languages
Text Domain: my-toolset

*/
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );


add_action('wp_enqueue_scripts', 'alt_ipe_load_scripts');

function alt_ipe_load_scripts() {                           
    $deps = array();
    $version= '1.0'; 
    $in_footer = true;    
    wp_enqueue_script('prefix-main-js', plugin_dir_url( __FILE__) . 'js/prefix-main.js', $deps, $version, $in_footer); 
    wp_enqueue_style( 'prefix-main-css', plugin_dir_url( __FILE__) . 'css/prefix-main.css');
}
 

//from https://webextent.net/override-learndash-templates/
function alt_ipe_replacement_learndash_templates( $filepath, $name, $args, $echo, $return_file_path){
 if ( 'quiz' == $name ){
   $filepath = plugin_dir_path(__FILE__ ) . 'templates/quiz.php';
 }
 return $filepath;
 
}
add_filter('learndash_template','alt_ipe_replacement_learndash_templates', 90, 5);


add_action('plugins_loaded', 'alt_ipe_extender_init');

function alt_ipe_extender_init(){
	if( class_exists('peerFeedback_Queries')) {
		function alt_ipe_get_group_members(){
			$user = wp_get_current_user();
			$user_email = $user->user_email;
			$group = get_group_users_by_id(2);			
			return $group;
		}
	}
}




function get_group_users_by_id($group_id)
	{
		global $wpdb;
		$groupID = $group_id;
		
		$sql = "SELECT * FROM " . $wpdb->prefix . DBTABLE_PEER_FEEDBACK_USERS." WHERE groupID=".$groupID;
	
		$groupUsers = $wpdb->get_results( $sql );
		$userCount= $wpdb->num_rows;
		
		if($userCount>=1)
		{
			$sortedUserArray = array();			
			foreach($groupUsers as $userInfo)
			{
				
				// Create temp array of users so we can order by surname
				$firstName = $userInfo->firstName;
				$lastName = $userInfo->lastName;
				$email = $userInfo->email;
				$userID = $userInfo->ID;
				$password= $userInfo->password;				
				
				
				$sortedUserArray[$userID] = array
				(
					'lastName'	=> $lastName,
					'firstName'	=> $firstName,
					'email'		=> $email,					
					'userID'	=> $userID,
					'password'	=> $password
				);
			}
			
			// Now sort the array by surname
			usort($sortedUserArray, function ($a, $b) { return strcmp($a['lastName'], $b['lastName']);});
			
			return $sortedUserArray;

		}
		else
		{
			return false;
		}
			
	}
	