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

//STUPID LEARNDASH DIRECTORY IS sfwd-lms

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


function alt_ipe_get_group_members(){
	$user_id = get_current_user_id();//get logged in user
	$user = get_user_meta($user_id,'');	//get user ID
	foreach($user as $key=>$value){//cycle through metadata looking for learndash partial match
	  if("learndash_group_users_" == substr($key,0,22)){ //such a mess to do partial match
	   		$users = alt_ipd_get_group_users($value[0]);//get other users who have this metadata field
	   		return alt_ipd_users_to_ids($users);//get their IDs
		  }
		}		
			
	}
//alt_ipd_get_stat_refs($quiz_settings['quiz_pro'], alt_ipe_get_group_members())

//GET GRP ID FROM LEARN DASH GROUPS
//GET THE PPL IN THAT GRP
//GET THE QUIZ ID
//GET THE SCORES FROM QUIZ
//RESTRICT BY PPL IN GROUP

//get other learndash group members by metadata field
function alt_ipd_get_group_users($group_id){
	$args = array(
	'meta_key'     => 'learndash_group_users_' . $group_id,
	'meta_value'   => $group_id,
	
 ); 
	$the_group = get_users( $args );
	return $the_group;
}

//get the user IDs for the group members in an array
function alt_ipd_users_to_ids($users){
	$user_ids = [];
	foreach ($users as $user) {
		array_push($user_ids, $user->ID);
	}
	return $user_ids;
}


//TEMPLATE PIECES

function alt_ipd_get_stat_refs($quiz_id, $user_ids){
	return implode(', ',$user_ids);
}


//add_filter( 'posts_join', 'alt_ipd_join_stats_tables_join' );
function alt_ipd_join_stats_tables_join($user_ids){
	//$ids = implode(', ',$user_ids);
	global $wpdb;
	$results = $wpdb->get_results( "SELECT statistic_ref_id, quiz_id, user_id FROM wp_wp_pro_quiz_statistic_ref WHERE user_id IN (".$user_ids .")");
	var_dump($results);
}


function test_it(){
	global $wpdb;
	$sql = 'SELECT * FROM ' . $wpdb->prefix . 'wp_pro_quiz_statistic';
//$ref_ids = $wpdb->get_col( $wpdb->prepare( 'SELECT statistic_ref_id FROM ' . $wpdb->prefix . 'wp_pro_quiz_statistic_ref WHERE  user_id = %d ', $user->ID ) );


	$results = $wpdb->get_results( $sql );
}





