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
 

/*
**
TEMPLATE OVERRIDE
**
*/

//from https://webextent.net/override-learndash-templates/
function alt_ipe_replacement_learndash_templates( $filepath, $name, $args, $echo, $return_file_path){
 if ( 'quiz' == $name ){
   $filepath = plugin_dir_path(__FILE__ ) . 'templates/quiz.php';
 }
 return $filepath;
 
}
add_filter('learndash_template','alt_ipe_replacement_learndash_templates', 90, 5);


/*
**
GET GROUP QUIZ RESULTS
**
*/


//GET GRP ID FROM LEARN DASH GROUPS which is in the metadata for the logged in user
function alt_ipe_get_group_members(){
	$user_id = get_current_user_id();//get logged in user
	$user = get_user_meta($user_id,'');	//get user ID
	foreach($user as $key=>$value){//cycle through metadata looking for learndash partial match
	  if("learndash_group_users_" == substr($key,0,22)){ //such a mess to do partial match
	   		$users = alt_ipd_get_group_users($value[0]);//get other users who have this metadata field
		  }
		}		
	return alt_ipd_users_to_ids($users);//get user ids with matching groups
	}
//alt_ipd_get_stat_refs($quiz_settings['quiz_pro'], alt_ipe_get_group_members())

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


//add_filter( 'posts_join', 'alt_ipd_join_stats_tables_join' );
function alt_ipd_join_stats_tables_join($user_ids, $quiz_id){
	$quiz_id = (int)$quiz_id;
	global $wpdb;
	$results = $wpdb->get_results( "SELECT wp_wp_pro_quiz_statistic_ref.statistic_ref_id, wp_wp_pro_quiz_statistic_ref.quiz_id, wp_wp_pro_quiz_statistic_ref.user_id, wp_wp_pro_quiz_statistic.statistic_ref_id, wp_wp_pro_quiz_statistic.answer_data AS answer_choice, wp_wp_pro_quiz_statistic.question_id, wp_wp_pro_quiz_statistic.correct_count, wp_wp_pro_quiz_question.title, wp_wp_pro_quiz_question.question, wp_wp_pro_quiz_question.answer_data FROM wp_wp_pro_quiz_statistic_ref INNER JOIN wp_wp_pro_quiz_statistic ON wp_wp_pro_quiz_statistic_ref.statistic_ref_id = wp_wp_pro_quiz_statistic.statistic_ref_id JOIN wp_wp_pro_quiz_question ON wp_wp_pro_quiz_question.id = wp_wp_pro_quiz_statistic.question_id WHERE (wp_wp_pro_quiz_statistic_ref.quiz_id =" . $quiz_id . " AND wp_wp_pro_quiz_statistic_ref.user_id IN (" . $user_ids . ")) ORDER BY question_id ASC");
	return $results;
}


class Quiz_summary {
	public function __construct($question_id, $question_title, $question_statement, $answers) {
		$this->question_id = $question_id;
		$this->question_title = $question_title;
		$this->question_statement = $question_statement;
	}
}

function doing_math($data){
	$a = [];
	foreach ($data as $key => $quiz) {
	echo '<h2>q id:' . $quiz->question_id . ' data key:' . $key . '</h2>';
	if (find_key_value($a, 'question_id', (int)$quiz->question_id)){
		update_question_data($a, $quiz->question_id);//RUNS FOR DUPLICATE IDS ONLY 
		} else {
			$answer_text = [];
			$get_answer_text = maybe_unserialize($quiz->answer_data);
			$response_choices = substr($quiz->answer_choice, 1, -1);
			$response_choices = explode(",", $response_choices);
			foreach ($get_answer_text as $key => $data)
			{
				$choice_count = $response_choices[$key];
			    array_push($answer_text, array($data->getAnswer()=>$choice_count));
			}
			array_push($a, array("question_id"=> (int)$quiz->question_id,array("question_title"=>$quiz->title,"question"=>$quiz->question), $answer_text));
		}
     	
	}
	//print_r($a);
	//print("<pre>".print_r($a,true)."</pre>");
	//var_dump($data);
}


function find_key_value($array, $key, $val){
    foreach ($array as $item)
    {
        if (is_array($item) && find_key_value($item, $key, $val)) return true;

        if (isset($item[$key]) && $item[$key] == $val) return true;
    }

    return false;
}

function update_question_data($a, $question_id){
		foreach ($a as $key => &$question) {
			echo 'passed a key: ' .$key . ' passed q_id: ' . $question_id . ' a[q_id]' . $a[$key]['question_id']. '<br>';
			$responses = $question[1];
			foreach ($responses as $key => $response) {
				echo key($response) . ' - ';
				echo $response[key($response)] . '<br>';
				$a[$key][key($response)] = 23;
				print("<pre>".print_r($response,true)."</pre>");				
			}
	}
		print("<pre>".print_r($a,true)."</pre>");

}
