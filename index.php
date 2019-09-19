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
    wp_enqueue_script('ipe-main-js', plugin_dir_url( __FILE__) . 'js/ipe-main.js', $deps, $version, $in_footer);
    wp_enqueue_style( 'ipe-main-css', plugin_dir_url( __FILE__) . 'css/ipe-main.css');
    wp_localize_script( 'ipe-main-js', 'proctor_score', array(
		'ajax_url' => admin_url( 'admin-ajax.php' )
	));
}
add_action('wp_enqueue_scripts', 'alt_ipe_load_scripts');


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


function doing_math($data){
	$a = [];
	foreach ($data as $alpha_key => $quiz) {
	if (find_key_value($a, 'question_id', (int)$quiz->question_id)){ //IF ALREADY EXISTS IN QUESTION ARRAY DO THIS
		//$answer_text = [];
		$question_index = count($a);
		$answer_text = [];
		$get_answer_text = maybe_unserialize($quiz->answer_data);
		$response_choices = substr($quiz->answer_choice, 1, -1);
		$response_choices = explode(",", $response_choices);
		$a_value = count($a)-1;
		foreach ($get_answer_text as $key => $data)
			{
				$choice_count = $response_choices[$key];
			    array_push($answer_text, array($data->getAnswer()=>(int)$choice_count));
			}
		foreach ($answer_text as $key => $data)
			{
				$choice_key = key($data);
				$choice_value = $data[$choice_key];
				update_question_data($a, $key, $choice_key, $choice_value, $a_value);//RUNS FOR DUPLICATE IDS ONLY
			}

		} else {
			$answer_text = [];
			$get_answer_text = maybe_unserialize($quiz->answer_data);
			$response_choices = substr($quiz->answer_choice, 1, -1);
			$response_choices = explode(",", $response_choices);
			foreach ($get_answer_text as $key => $data)
			{
				$choice_count = $response_choices[$key];
			    array_push($answer_text, array($data->getAnswer()=>(int)$choice_count));
			}
			array_push($a, array("question_id"=> (int)$quiz->question_id,array("question_title"=>$quiz->title,"question"=>$quiz->question), $answer_text));
		}
	}
	//print("<pre>".print_r($a,true)."</pre>");
	return $a;
}


function update_question_data(&$a, $key, $choice_key, $choice_value, $a_value){
	$response = &$a[$a_value][1][$key];
	$response[$choice_key] =  (int)$response[$choice_key] + (int)$choice_value;
}

function group_responses_printer($data){
	$html = '<div class="full-question-data">';
	foreach ($data as $key => $question){
		$question_title = $question[0]['question_title'];
		$question_text = $question[0]['question'];
		$html .= '<div class="question-summary"><h3>' . $question_title . ' - ' . $question_text . '</h3></div>';
		$responses = $question[1];
		foreach ($responses as $key => $response) {
			$answer = key($response);
			$value = $response[$answer];
			if ($value === 1){
				$plural = '';
			} else {
				$plural = 's';
			}
			$html .= $value . ' member' . $plural . ' chose ' . $answer . '<br>';
		}
	}
	return $html . '</div>';
}

function find_key_value($array, $key, $val){
    foreach ($array as $item)
    {
        if (is_array($item) && find_key_value($item, $key, $val)) return true;

        if (isset($item[$key]) && $item[$key] == $val) return true;
    }

    return false;
}



function ipe_proctor_view(){
	if ( is_user_logged_in() ) {
		$html = '<div id="success-notification"></div>';//place to set update alerts
		$group_members = alt_ipe_get_group_members_leader();
		$proctor_scores = [];
		$gradebook_contents = get_post_meta(785,'ld_gb_components', true);//all the gradebook info - associated w post ID which you can find via https://ipecase.org/VCU/wp-admin/edit.php?post_type=gradebook
		$gradebook = maybe_unserialize($gradebook_contents);
		$proctor_assignments = [];
		foreach ($gradebook as $key => $assignment) {
			$assignment_name = strtolower($assignment['name']);//make it lower case for match below
			if (strpos($assignment_name, 'preceptor') !== false ){
				array_push($proctor_assignments, array($assignment['name'] => $assignment['id']));
			}
		}
		$html .= '<div class="proctor-grades"><div class="cover"> <h2>'. get_the_title($group_members[0]['group']) .'</h2></div><div class="empty-cell assignment-title assignment-cell"></div>';
		foreach ($proctor_assignments as $key => $assignment) {
			$html .= '<div class="column assignment-title"><a href="' . etherpad_assignment_link(key($assignment), $group_members[0]['etherpad_group_id']) . '">' . key($assignment) . '</a></div>';
		}
		foreach ($group_members as $key => $member) {
			if (isset($group_members[$key-1]['group'])){
				$check = $group_members[$key-1]['group'];
			} else {
				$check = 'foo';
			}
			if ($member['group'] !=  $check && $key != 0 ){				
				$html .= '<div class="cover"> <h2>'. get_the_title($member['group']) .'</h2></div><div class="empty-cell assignment-title assignment-cell"></div>';
				foreach ($proctor_assignments as $key => $assignment) {
					$html .= '<div class="column assignment-title"><a href="' . etherpad_assignment_link(key($assignment), $member['etherpad_group_id']) . '">' . key($assignment) . '</a></div>';
				}
			}
			$html .= '<div class="proctor-assignment-cell proctor-student-name">'. key($member) . '</div>';
			$user_id = $member[key($member)];
			foreach ($proctor_assignments as $key => $assignment) {
				$assignment_id = $assignment[key($assignment)];
				$score = return_assignment_score($user_id, $assignment_id);
				$comment = return_assignment_comment($user_id, $assignment_id);
				$html .= '<div class="proctor-assignment assignment-cell">' . selected_proctor_score($score, $user_id, $assignment_id, $comment) . '</div>';
			}
		}
	//grades are in wp_usermeta at patters like ld_gb_manual_grades_785_1 (785 being the gradebook) and 1 being the item
	//print("<pre>".print_r($proctor_assignments,true)."</pre>");
	//print("<pre>".print_r($group_members,true)."</pre>");
	return $html;
	} else {
		return 'Please login.';
	}

}
add_shortcode( 'proctor', 'ipe_proctor_view' );

function return_assignment_score($user_id, $assignment_id){
	$assignment = get_user_meta($user_id,'ld_gb_manual_grades_785_'. $assignment_id, true);
	if ($assignment){
		return $assignment[0]['score'];
	}
}

function return_assignment_comment($user_id, $assignment_id){
	$assignment = get_user_meta($user_id,'ld_gb_manual_grades_785_'. $assignment_id, true);
	if ($assignment){
		return $assignment[0]['name'];
	}
}

function etherpad_assignment_link($assignment_name,$etherpad_group_id){
	$name = substr($assignment_name, 0,6);
	return 'archives/etherpad/'.sanitize_title($name).'?group='.$etherpad_group_id;
}


//GET GRP ID FROM LEARN DASH GROUPS which is in the metadata for the logged in user
function alt_ipe_get_group_members_leader(){
	//global $user;
	$user_id =  get_current_user_id();//get logged in user
	$user = get_user_meta($user_id);	//get user ID
	$all_users = [];
	foreach($user as $key => $value){//cycle through metadata looking for learndash partial match
	  $i = substr($key,0,24);

	  if("learndash_group_leaders_" == substr($key,0,24)){ //such a mess to do partial match
	   		$users = alt_ipd_get_group_users($value[0]);//get other users who have this metadata field
	   		$group_id =  $value[0];
	   		//print("<pre>".print_r(alt_ipd_users_for_proctor_view($users),true)."</pre>");
	   		foreach ($users as $key => $student) {
        $name =  $student->display_name;
        $etherpad_group_id = get_user_meta($student->ID, 'etherpad_group_id', true);
				array_push($all_users, array($name =>$student->ID, 'group'=>$group_id, 'etherpad_group_id' => $etherpad_group_id));
			}
		  }
		}
		//print("<pre>".print_r($all_users,true)."</pre>");
	 return $all_users;//get user ids with matching groups
	}

//RENDERED REDUNDANT
// function alt_ipd_users_for_proctor_view($users){
// 	$user_ids = [];
// 	foreach ($users as $user) {
// 		$name =  $user->display_name;
// 		array_push($user_ids, array($name =>$user->ID));
// 	}
// 	return $user_ids;
// }

function selected_proctor_score($score, $user_id, $assignment_id, $assignment_comment){
	$scores = array('unscored'=>'unscored',
			'0 - unsatisfactory' => 50,
			'1 - needs improvement' => 75,
			'2 - satisfactory' => 85,
			'3 - excellent' => 100);
	$html = '<select id="unique-'.$user_id. $assignment_id .'" name="proctor-grade" data-user="'.$user_id.'" data-assignment="'.$assignment_id.'" data-comment="'.$assignment_comment.'">' ;
		foreach ($scores as $key => $value) {
			if ($value == $score ){
				$selected = 'selected="selected"';
			} else {
				$selected = '';
			}
			$html .= '<option value="'. $value .'"' . $selected . '>' . $key . '</option>';
			//  <option value="100">3 - excellent</option>
		}
	$html .= '</select>';
	$html .= '<input class="assignment-comment" type="text" name="comment" id="comment-' . $user_id . '" value="' . $assignment_comment . '">';
	return $html;
}


add_action( 'wp_ajax_update_proctor_grades', 'update_proctor_grades' );

function update_proctor_grades(){
	$user_id = $_POST['user_id'];
	$assignment_id =  $_POST['assignment_id'];
	$score =  $_POST['assignment_score'];
	$comment = $_POST['assignment_comment'];
	$db_score = array();
	array_push($db_score, array('score'=>$score, 'name'=>$comment, 'status'=>'', 'component'=>1));
	$serialized = $db_score; //it appears that it's serializing it without me
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
	 	 update_user_meta($user_id, 'ld_gb_manual_grades_785_' . $assignment_id, $serialized);
	 	}
	 	die();
}

/*
//QUIZ CURVING --------------------------
*/


//THE HOOK THAT RUNS AFTER QUIZ COMPLETION
add_action("learndash_quiz_completed", function($data) {
//Called when quiz is completed
	write_log($data);

	$user_id = get_current_user_id();//get user
	$quiz_id = $data['pro_quizid'];//get quiz
	$id = $data['quiz']->ID;//og post id to get acf data - this differs from the quiz id
	$user_discipline = get_user_discipline($user_id);//get discipline
	$all_quizzes = get_user_quiz_data($user_id);//get all quizzes
	$user_ids = alt_ipe_get_group_members();

		if(get_acf_curve_data($id, $user_discipline)){
			$curve = get_acf_curve_data($id, $user_discipline);//get curve value
			update_quiz_score($user_id, $quiz_id, $curve);	//update records
			$refer = wp_get_referer();//MAYBE DO THIS FOR REDIRECT LATER . . . . .
			//REMOVE FOR PRODUCTION
			// $quiz_id = 'quiz id = '.  $data['pro_quizid'];
			// 	$content = ' quiz post id - ' . $id . '<br> disc- ' . $user_discipline . ' curve- ' . $curve . "<pre>".print_r($data['quiz']->ID,true)."</pre>";
			// 	$my_post = array(
			//     'post_title'    => 'disc = ' . get_user_discipline($user_id),
			//     'post_content'  => $content,
			//     'post_status'   => 'publish',
			//     'post_author'   => 1,
			// 	);

				// Insert the post into the database.
				//wp_insert_post( $my_post );
		//END REMOVE
			wp_redirect( $refer ); //redirect to page
			exit;
		}
	 if (group_quiz_test($id) === TRUE){
		//$content = "<pre>".print_r($data,true)."</pre>";
			// $my_post = array(
		 //    'post_title'    => 'group test- ' . group_quiz_test($id) . 'quiz id - ' . $quiz_id ,
		 //    'post_content'  => "<pre>".print_r($data,true)."</pre>",
		 //    'post_status'   => 'publish',
		 //    'post_author'   => 1,
			// );

			// // Insert the post into the database.
			// wp_insert_post( $my_post );

			assign_group_scores( $data, $quiz_id, $user_id);
	}

}, 5, 1);


//CURVE STUFF

//get CURVE AMOUNT
function get_acf_curve_data($post_id, $user_discipline){
	// check if the repeater field has rows of data
	//$count = count(get_field('field_5ced7856feaa7', $post_id)); 
	if( have_rows('field_5ced7856feaa7', $post_id) ):
	 	// loop through the rows of data
	 	write_log('count of curve values ' .  count(get_field('field_5ced7856feaa7', $post_id)));
	    while ( have_rows('field_5ced7856feaa7', $post_id) ) : the_row();

	    	$discipline = get_sub_field('field_5ced7865feaa8');
	        $curve_value = get_sub_field('field_5ced78cdfeaa9');
	        write_log('acf disc=' . $discipline);
	        write_log('disc=' . $user_discipline);
	        write_log('post id=' . $post_id);	        
	    	if ($user_discipline == $discipline){	       //|| $user_discipline == 'All'
	    	    //var_dump($curve_value);
	    		$curve = $curve_value;
	    	}	    	


	    endwhile;
	    return $curve;
	else :

	    // no rows found
	    return 0;

	endif;

}

//get user discipline
function get_user_discipline($user_id){
	$discipline = get_user_meta($user_id, '_discipline', true);
	return $discipline;
}

//get quiz scores from user meta
function get_user_quiz_data($user_id){
	$scores = maybe_unserialize(get_user_meta($user_id, '_sfwd-quizzes', true));
	return $scores;
}

//average the quiz
function average_quiz_points($points, $total_points){
    if ($points>0){
        return (int)$points/(int)$total_points;
    } else {
        return 0;
    }
}

//update quiz score in usermeta with curve score
function update_quiz_score($user_id, $quiz_id, $curve){
	$all_quizzes = get_user_quiz_data($user_id);
	//print("<pre>".print_r($all_quizzes,true)."</pre>");
	foreach ($all_quizzes as &$quiz) {

		if ((int)$quiz['pro_quizid'] === (int)$quiz_id){ // && !array_key_exists("curved",$quiz)

			$quiz['curved'] = $curve;//add metafield that lets you know how much it was curved by
        	$quiz['og_points'] =  (int)$quiz['points']; //original score
			$quiz['points'] = (int)$quiz['points'] + (int)$curve;//new score with curve added
			$quiz['percentage'] = average_quiz_points($quiz['points']*100, $quiz['total_points']);
        	update_user_meta( $user_id, '_sfwd-quizzes', $all_quizzes);
		}

	}
}

//to show quiz curve
function return_curved_quiz($user_id, $quiz_id){
	$all_quizzes = get_user_quiz_data($user_id);
	//print("<pre>".print_r($all_quizzes,true)."</pre>");
    $curve = 0;
		foreach ($all_quizzes as $quiz) {
			if ((int)$quiz['quiz'] === (int)$quiz_id ){ // && !array_key_exists("curved",$quiz)
				$new_score = $quiz['points'];			
			if($quiz['curved']){
				$quiz['curved'];//what is this?
				$curve = $quiz['curved'];
			} else {
				$curve = 0;
			}

		}

	}
	return  $curve;
}


//show curve score
function return_score_percentage($user_id, $quiz_id){
	$all_quizzes = get_user_quiz_data($user_id);
	foreach ($all_quizzes as $quiz) {
			if ((int)$quiz['quiz'] === (int)$quiz_id ){ // && !array_key_exists("curved",$quiz)
				$new_score = $quiz['percentage'];
				$pretty_percent = number_format( $new_score + .5, 0 ) . '%';
				return $pretty_percent;
		}
	}

}
//GROUP QUIZ
function group_quiz_test($post_id){
	$all_categories = get_the_category($post_id);
	$group = $all_categories[0]->slug;
	if ($group === 'group') {
		return true;
	} else {
		return false;
	}
}


//$score, $course, $lesson, $quiz_id
function assign_group_scores($data, $quiz_id, $user_id){
	$user_ids = alt_ipe_get_group_members();
	if(get_user_quiz_data($user_id)){//has quiz data
		$all_quizzes = get_user_quiz_data($user_id);//get the submitter quiz data
		foreach ($all_quizzes as &$quiz) {
				if ((int)$quiz['quiz'] === (int)$quiz_id){  //match found for source to copy to other users

				$quiz['group_score'] = 'scored as group';//flag as scored by group process

				$new_group_score = $quiz;

				update_user_meta( $user_id, '_sfwd-quizzes', $all_quizzes);
				}
			}
		}
	//apply to other people in the group
	$clean_user_ids = not_me($user_ids, $user_id);//remove quiz taker from this loop
	foreach ($clean_user_ids as $group_user_id){
	   $other_users_quizzes = get_user_quiz_data($group_user_id);
       array_push($other_users_quizzes, $new_group_score);
       update_user_meta( $group_user_id, '_sfwd-quizzes', $all_quizzes);
	}
}

function not_me($user_ids, $user_id){
    if (($key = array_search($user_id, $user_ids)) !== false) {
        unset($user_ids[$key]);
    }
    return $user_ids;

}
//LOGGIN FROM https://www.elegantthemes.com/blog/tips-tricks/using-the-wordpress-debug-log



if ( ! function_exists('write_log')) {
   function write_log ( $log )  {
      if ( is_array( $log ) || is_object( $log ) ) {
         error_log( print_r( $log, true ) );
      } else {
         error_log( $log );
      }
   }
}


//group member shortcode
function alt_ipe_get_group_members_short(){
	$user_id = get_current_user_id();//get logged in user
	$user = get_user_meta($user_id,'');	//get user ID
	$html = '';
	foreach($user as $key=>$value){//cycle through metadata looking for learndash partial match
	  if("learndash_group_users_" == substr($key,0,22)){ //such a mess to do partial match
	   		$users = alt_ipd_get_group_users($value[0]);//get other users who have this metadata field
		  }
		}
	//print("<pre>".print_r($users,true)."</pre>");
	foreach ($users as $user){
			$html .= $user->data->display_name . ' (' . get_user_discipline($user->data->ID) . ')<br>';
		}
	return $html;
	 }

add_shortcode( 'ipe-members', 'alt_ipe_get_group_members_short' );
