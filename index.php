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


function alt_ipe_get_group_members(){
	$user_id = get_current_user_id();
	$user = get_user_meta($user_id,'');	
	foreach($user as $key=>$value){
	  if("learndash_group_users_" == substr($key,0,22)){ //such a mess to do partial match
	   		var_dump($value);
	   		$users = alt_ipd_get_group_users($value[0]);
	   		 alt_ipd_users_to_ids($users);
		  }
		}		
			
	}



//GET GRP ID FROM LEARN DASH GROUPS
//GET THE PPL IN THAT GRP
//GET THE QUIZ ID
//GET THE SCORES FROM QUIZ
//RESTRICT BY PPL IN GROUP

function alt_ipd_get_group_users($group_id){
	$args = array(
	'meta_key'     => 'learndash_group_users_' . $group_id,
	'meta_value'   => $group_id,
	
 ); 
	$the_group = get_users( $args );
	return $the_group;
}

function alt_ipd_users_to_ids($users){
	foreach ($users as $user) {
		var_dump($user->ID);
	}
}