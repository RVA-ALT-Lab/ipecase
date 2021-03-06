<?php
/**
 * Displays a quiz.
 *
 * Available Variables:
 * 
 * $course_id       : (int) ID of the course
 * $course      : (object) Post object of the course
 * $course_settings : (array) Settings specific to current course
 * $course_status   : Course Status
 * $has_access  : User has access to course or is enrolled.
 * 
 * $courses_options : Options/Settings as configured on Course Options page
 * $lessons_options : Options/Settings as configured on Lessons Options page
 * $quizzes_options : Options/Settings as configured on Quiz Options page
 * 
 * $user_id         : (object) Current User ID
 * $logged_in       : (true/false) User is logged in
 * $current_user    : (object) Currently logged in user object
 * $post            : (object) The quiz post object
 * $lesson_progression_enabled  : (true/false)
 * $show_content    : (true/false) true if user is logged in and lesson progression is disabled or if previous lesson and topic is completed.
 * $attempts_left   : (true/false)
 * $attempts_count : (integer) No of attempts already made
 * $quiz_settings   : (array)
 * 
 * Note:
 * 
 * To get lesson/topic post object under which the quiz is added:
 * $lesson_post = !empty($quiz_settings["lesson"])? get_post($quiz_settings["lesson"]):null;
 * 
 * @since 2.1.0
 * 
 * @package LearnDash\Quiz
 */


//TESTING - remove for production
//echo "<pre>".print_r($taken_quiz,true)."</pre>";

//quiz settings has course, lesson, quiz_pro as pro_quizid


//  echo 'quiz id = ' .$quiz_id.'<br/>';
//  echo 'post id = ' .$post->ID.'<br/>';
//  echo 'user id = ' .get_current_user_id().'<br/>';
// var_dump($quiz);
//same

$date = new DateTime();
$date = $date->format("y:m:d h:i:s");
//write_log( $date . ' ' . __LINE__ );
//END TESTING now!!!!!

$group_members = alt_ipe_get_group_members();
$user_ids = implode(', ', $group_members);
//var_dump($user_ids);
$quiz_category = get_the_category($post->ID)[0]->name;//gets quiz category assuming there's only one -- not sure this is needed if we have acf fields


//echo 'group test = ' . var_dump(group_quiz_test($post->ID));

$user_discipline = get_user_discipline($user_id);
$the_quizzes = get_user_quiz_data($user_id);


 $curve = get_acf_curve_data($post->ID, $user_discipline);
//  echo 'curve is set to ' . $curve;
// // echo 'set curve: ' . $curve;
// echo '<br>attempts left: ' . $attempts_left;
// echo '<br>attempts count: ' . $attempts_count;
// echo '<br>graded curve: '.return_curved_quiz($user_id, $quiz_id);
// //var_dump(alt_ipe_get_group_members_leader($user_id)); //plenty returned



if ( ! empty( $lesson_progression_enabled ) ) {
	$last_incomplete_step = is_quiz_accessable( null, $post, true );
	if ( 1 !== $last_incomplete_step ) {
		if ( is_a( $last_incomplete_step, 'WP_Post' ) ) {
			if ( $last_incomplete_step->post_type === learndash_get_post_type_slug( 'topic' ) ) {
				echo sprintf(
					// translators: placeholders: topic URL.
					esc_html_x( 'Please go back and complete the previous %s.', 'placeholders: topic URL', 'learndash' ),
					'<a class="learndash-link-previous-incomplete" href="' . learndash_get_step_permalink( $last_incomplete_step->ID, $course_id ) . '">' . LearnDash_Custom_Label::label_to_lower('topic') . '</a>'
				);
			} elseif ( $last_incomplete_step->post_type === learndash_get_post_type_slug( 'lesson' ) ) {
				echo sprintf(
					// translators: placeholders: lesson URL.
					esc_html_x( 'Please go back and complete the previous %s.', 'placeholders: lesson URL', 'learndash' ),
					'<a class="learndash-link-previous-incomplete" href="' . learndash_get_step_permalink( $last_incomplete_step->ID, $course_id ) . '">' . LearnDash_Custom_Label::label_to_lower( 'lesson' ) . '</a>'
				);
			} elseif ( $last_incomplete_step->post_type === learndash_get_post_type_slug( 'quiz' ) ) {
				echo sprintf(
					// translators: placeholders: quiz URL.
					esc_html_x( 'Please go back and complete the previous %s.', 'placeholders: quiz URL', 'learndash' ),
					'<a class="learndash-link-previous-incomplete" href="' . learndash_get_step_permalink( $last_incomplete_step->ID, $course_id ) . '">' . LearnDash_Custom_Label::label_to_lower( 'quiz' ) . '</a>'
				);
			} else {
				echo esc_html__( 'Please go back and complete the previous step.', 'learndash' );
			}
		}
	}
}

 if ( $show_content ) {
	if ( ( isset( $materials ) ) && ( !empty( $materials ) ) ) : 
		?>
		<div id="learndash_quiz_materials" class="learndash_quiz_materials">
			<h4><?php printf( _x( '%s Materials', 'Quiz Materials Label', 'learndash' ), LearnDash_Custom_Label::get_label( 'quiz' ) ); ?></h4>
			<p><?php echo $materials; ?></p>
		</div>
		<?php 
	endif;
	
    echo $content;
    if ( $attempts_left ) {
        echo $quiz_content;
    } else { 
    	// var_dump('userid = '. $user_id);
    	// var_dump('quiz_id = ' . $quiz_id);
    	if(return_curved_quiz($user_id, $quiz_id) && (int)return_curved_quiz($user_id, $quiz_id) > 0 ){
	    	echo '<h2>Your quiz was curved by ' . return_curved_quiz($user_id, $quiz_id) . ' points. Your total score is ' . return_score_percentage($user_id, $quiz_id);  
	    }
    	echo '<h2>Group Data</h2>';
    	echo '<p>This shows how your group has done in aggregate.</p>';
    	$quiz_data = get_post_meta($post->ID, '_sfwd-quiz', true);
    	$pro_quizid = $quiz_data['sfwd-quiz_quiz_pro'];
    	$data = alt_ipd_join_stats_tables_join($user_ids, $pro_quizid);    	
        $a = doing_math($data); 	
        echo group_responses_printer($a);
        
		?>
			<p id="learndash_already_taken"><?php echo sprintf( esc_html_x( 'You have already taken this %1$s %2$d time(s) and may not take it again.', 'placeholders: quiz, attempts count', 'learndash' ), LearnDash_Custom_Label::label_to_lower('quiz'), $attempts_count ); ?></p>
		<?php
    }
}
