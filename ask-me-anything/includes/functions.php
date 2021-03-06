<?php
/**
 * Append all scripts and styles to the admin
 *
 * @param	NA 
 * @return	NA
 */
function adminAmaScriptsAndStyles() {
	global $post, $pagenow;
	if (!$post) {
		return;
	}

	$valid_post_types = array(
		'ama_post',
		'ama_styles',
		'ama_questions'
	);
	if (in_array($post->post_type, $valid_post_types)) {
		$ama_base_path = plugin_dir_path(__FILE__) . '/ask_me_anything.php';
		wp_enqueue_style('wpalchemy-metabox', plugins_url('../css/ama_admin_styles.css', $ama_base_path));
		wp_enqueue_style('wp-color-picker');
		wp_enqueue_script('ask_me_anything_third_party', plugins_url('../js/third_party_scripts.js', $ama_base_path));
		wp_enqueue_script('ask_me_anything_scripts', plugins_url('../js/ama_admin_scripts.js', $ama_base_path), array('wp-color-picker'), false, true);
		//Hide the preview button on our custom post types
		echo '<style>#minor-publishing-actions, #view-post-btn, #misc-publishing-actions{display:none;}</style>';	
	}
}

/**
 * Makes a dropdown list of font families
 *
 * @param	string	$field_name	 	The field name for the select box. 
 * @param	object	$this_style	 	The style object we're using (Class AmaStyle)
 * @param	object	$mb	 			The style object we're using (Class WPAlchemy_MetaBox)
 * @return	string	$markup			A list of font families as a select box
 */
function makeFontFamilySelect($field_name, $this_style, $mb){
	$font_list = $this_style->getFontStyles();
	$mb->the_field($field_name);
	$markup = '<select name="' . $mb->get_the_name() . '">';
	$font_selected = true;
	if ($mb->get_the_value() === null) {
		$font_selected = false;
	}
	foreach ($font_list as $key => $value) {
		if (is_array($value)) {
			$markup .= '<optgroup label="' . $key . '">';
			foreach ($value as $font_str => $font_name) {
				if ($font_selected === false) {
					if ($font_str === 'Open Sans') {
						$markup .= '<option value="' . $font_str . '" selected="selected">' . $font_name . '</option>';
						$font_selected = true;
						continue;
					}
				}
				$markup .= '<option value="' . $font_str . '" ' . ($mb->get_the_value() === $font_str ? 'selected="selected"' : '') . '>' . $font_name . '</option>';
			}
			$markup .= '</optgroup>'; 
		}						
	}
	$markup .= '</select>';
	return $markup;
}

/**
 * Makes a dropdown list of font styles
 *
 * @param	string	$field_name		The field name for the select box. 
 * @param	object	$mb	 			The style object we're using (Class WPAlchemy_MetaBox)
 * @return	string	$markup			A list of font styles as a select box
 */
function makeFontStyleSelect($field_name, $mb) {
	$mb->the_field($field_name);
	$types = array(
		'normal' => 'Normal',
		'bold' => 'Bold',
		'normal_italic' => 'Italic',
		'bold_italic' => 'Bold/Italic'
	);
	
	$markup = '<select name="' . $mb->get_the_name() . '">';
	foreach ($types as $key => $value) {
		$markup .= '<option value="' . $key . '" ' . ($mb->get_the_value() === $key ? 'selected="selected"' : '') . '>' . $value . '</option>';
	}
	$markup .= '</select>';
	return $markup;
}

/**
 * Get all the user defined styles
 *
 * @param	NA
 * @return	array	All the user defined style posts in array format
 */
function getUserStyles() {
	$args = array(
		'posts_per_page' => -1,
		'post_type' => 'ama_styles',
		'post_status' => 'publish'
	);
	return get_posts($args);
}

/**
 * Get all the forms defined by the user
 *
 * @param	NA
 * @return	array	All the user defined form posts in array format
 */
function getUserForms() {
	$args = array(
		'posts_per_page' => -1,
		'post_type' => 'ama_post',
		'post_status' => 'publish'
	);
	return get_posts($args);
}

/**
 * Register all widgets
 *
 * @param	NA
 * @return	NA
 */
function registerAllWidgets() {
    register_widget('AmaWidget');
}

/**
 * Trigger modal window from the client. This triggers the modal window from 
 *
 * @param	$atts		array	 		Array of attributes passed from the shorttag
 * @return	$trigger	string			Final rendered markup from the shortag
 */
function renderAmaForm($atts, $trigger = '') {
	//Mark the trigger with a data attribute so we can spawn the correct modal on click
	if ($trigger === '') {
		$trigger = '<button class="ama_trigger" data-modal-target=' . $atts['id'] . '>Ask A Question</button>';
	} else {
		$trigger = '<div class="ama_trigger" data-modal-target=' . $atts['id'] . '>' . $trigger . '</div>';
	}
	//Instantiate the new form hidden
	new AmaForm($atts['id']);
	return $trigger;
}

/**
 * Render the answered questions for the form in QA format
 *
 * @param	$atts		array	 		Array of attributes passed from the short tag
 * @return	NA	 		NA
 */
function renderAmaQA($atts) {
	//Mark the trigger with a data attribute so we can spawn the correct modal on click
	$attributes = shortcode_atts( array(
        'id' => false
    ), $atts );
	
	if ($attributes['id'] !== false) {
		$args = array(
			'posts_per_page'	=>	-1,
			'post_type'			=> 'ama_questions',
		);
		$posts = get_posts($args);

		if (!empty($posts)) {
			foreach ($posts as $question_obj) {
				$form_id = get_post_meta($question_obj->ID, 'form_id', true);
				
				//Question must be part of this form ID
				if((int)$form_id !== (int)$attributes['id']) {
					continue;
				}

				$the_question = nl2br(get_post_meta($question_obj->ID, 'whats-your-question', true));
				$date_answered = get_the_date(false ,$question_obj->ID);
				$answer = $question_obj->post_content;

				echo '
					<article style="ama_discussion_wrapper">
						<div class="ama_question_wrap">
							<h4>Question</h4>
							<p>"<em>' . $the_question . '"</em></p>
						</div>
						
						<hr>

						<div class="ama_answer_wrap">
							<h4>Answered on ' . $date_answered . '</h4>
							<p>' . $answer . '</p>
						</div>
					</article>';
			}
		}
	}
}

/**
 * Set the content type for sending an email via wp_mail() to HTML
 * @param	$post_array		Array 		An array of the post data from the user submission
 * @param	$post_id		Int 		The post ID this email is in reference to
 * @return	$result			Boolean		
 */
function questionSubmissionNotification($post_array, $post_id) {
	$headers = 'From: ' . $post_array['first-and-last-name'] . ' <' . $post_array['email'] . '>' . "\r\n";
			
	//Add line breaks and unescape commas
	$formatted_question = submissionValueFormat('whats-your-question', $post_array['whats-your-question']);
	
	$message = '
		<p>
			A new question has been submitted through ' . get_option('blogname') . '. You can answer this question directly with the following link: <a href="' . get_home_url() . '/wp-admin/post.php?post=' . $post_id . '&action=edit">' . get_home_url() . '/wp-admin/post.php?post=' . $post_id . '&action=edit</a>
		</p>
		<p>
			<h4><a href="mailto:' . $post_array['email'] . '">' . $post_array['first-and-last-name'] . '</a> Asks:</h4>
			<blockquote>
				<em>' . $formatted_question . '</em>
			</blockquote>
		</p>';

	$result = wp_mail(get_option('admin_email'), __('New Question Submitted'), $message, $headers);
		
	// Reset content-type to avoid conflicts -- http://core.trac.wordpress.org/ticket/23578
	remove_filter('wp_mail_content_type', 'setHtmlEmailType');
	return $result;
}

/**
 * Set the content type for sending an email via wp_mail() to HTML
 * @param	NA
 * @return	NA
 */
function setHtmlEmailType() {
	return 'text/html';
}

/**
 * Return formatted markup for a users form submission. Used for the edit questions page in the admin.
 * @param	NA
 * @return	NA 
 */
function formatSubmissionPost() {
	global $post;
	$html = false;

	if ($post->post_type === 'ama_questions') {
		$meta = get_post_meta($post->ID);
		$ignore_keys_arr = array(
			'form_id', 
			'_edit_lock',
			'_edit_last'
		);

		if (!empty($meta)) {
			$html = '
				<div class="box shadow_page">
					<table id="user_question_table">
						<tr>
							<td colspan="2">
								<h2>Submission Information</h2>
							</td> 
						</tr>';
			foreach ($meta as $key => $inner_arr) {
				if (!in_array($key, $ignore_keys_arr)) {
					$formatted_key = str_replace('-', ' ', $key);
					$formatted_key = ucwords($formatted_key);
					$formatted_value = submissionValueFormat($key, $inner_arr[0]);

					$html .= '
						<tr>
							<td class="field_key">' . $formatted_key . '</td>
							<td>' . $formatted_value .'</td>
						</tr>';
				}
			}
			$html .= '
					</table>
				</div>
				<h1>Your Answer</h1>';
		}
		echo $html;
	}
	return;
}


/**
 * Format submission values based on their type or key
 * @param	$key			Mixed 		The value type to format
 * @param	$value 			String 		The value to format
 * @return	$string			String 		The formatted value		
 */
function submissionValueFormat($key, $value){
	$string = '';
	switch($key){
		case 'email':
			$string = '<a href="mailto:' . $value . '">' . $value . '</a>';
		break;

		case 'whats-your-question':
			$string = nl2br($value);
			$string = str_replace("\'", "'", $string);
		break;

		default:
			//Don't know the format. Try to guess the content type. Otherwise return value unformatted
			
			//Assume we cannot match the type
			$string = $value;
			
			//URL
			if (filter_var($value, FILTER_VALIDATE_URL)) {
				$string = '<a href="' . $value . '" target="_blank">' . $value . '</a>';
			}

			//Some block of copy, probably a text area
			if(strstr($value, PHP_EOL)) {
				$string = nl2br($value);
				$string = str_replace("\'", "'", $string);
			}

			//A serialized string means an array of values was captured like a checkbox
			if (@unserialize($value)) {
				$string = implode(', ', unserialize($value));
			}
		break;
	}
	return $string;
}

/**
 * Filter function that is run after the admin updates an question post
 *
 * @param	$data 		Array 	Sanitized post data
 * @return	$data		Array 	Sanitized post data that may or may not be modifed
 */
function answerQuestionFilter($data) {
	if ($data['post_type'] === 'ama_questions' && $data['post_status'] !== 'trash') {
		//Blank content cannot be submitted as an answer. Force draft status.
		$admin_answer = trim(str_replace('&nbsp;', '', $data['post_content']));
		if ($admin_answer === '') {
			$data['post_status'] = 'draft';
		}	
	}
	return $data;
}


/**
 * Filter function to add custom admin columns for AMA questions
 *
 * @param	$columns 	Array 	An array of column names with their respective values
 * @return	$columns	Array 	Array of column names with possible new values
 */
function editAmaQuestionColumns($columns) {
	$columns = array(
		'cb' 		=> '<input type="checkbox">',
		'title' 	=> __('Email Address'),
		'q_excerpt' => __('Question Excerpt'),
		'form_name' => __('Form Name'),
		'answer_status'	=> __('Answer Status'),
		'date' 		=> __('Date')
	);
	return $columns;
}

function editAmaPostColumns($columns) {
	$columns = array(
		'cb' 		=> '<input type="checkbox">',
		'title' 	=> __('Title'),
		'style' 	=> __('Form Style'),
		'count' 	=> __('Question Count'),
		'date' 		=> __('Date'),
	);
	return $columns;
}

function manageAmaQuestionColumns($column, $post_id) {
	global $post;
	switch ($column) {
		case 'q_excerpt':
			$question_snippet = get_post_meta($post_id, 'whats-your-question', true);
			if (!empty($question_snippet)) {
				echo '<small>&ldquo;<em>' . nl2br(implode(' ', array_slice(explode(' ', $question_snippet), 0, 25))) . '...&rdquo;</em></small>';
			}
		break;
		case 'form_name':
			$form_id = get_post_meta($post_id, 'form_id', true);
			echo '<a href="' . admin_url('post.php?post=' . $form_id) . '&action=edit' . '">' . get_the_title($form_id) . '</a>';
		break;
		case 'answer_status':
			echo ($post->post_status === 'publish' ? '<div class="answered_circle yes"></div>' : '<div class="answered_circle no"></div>');
		break;
	}
}

function manageAmaFormColumns($column, $post_id) {
	global $post;
	switch ($column) {
		case 'style':
			$form_meta = get_post_meta($post_id, 'ama_form_meta', true); 
			echo ($form_meta['theme_style'] > 0 ? '<a href="' . admin_url('post.php?post=' . $form_meta['theme_style']) . '&action=edit' . '">' . get_the_title($form_meta['theme_style']) . '</a>' : 'Default');
		break;
		case 'count': 
			$query_args = array(
				'posts_per_page'   => -1,
				'post_type'        => 'ama_questions',
			); 
			$all_questions_submitted = get_posts($query_args);
			$post_count = 0;
			foreach ($all_questions_submitted as $single_question) {
				$this_questions_form_parent_id = get_post_meta($single_question->ID, 'form_id', true);
				//If form_id matches $post_id, add to the total count and print when done
				if ($post_id === (int)$this_questions_form_parent_id) {
					$post_count++;
				}
			}
			echo $post_count;
		break;
	}
}


function amaQuestionsSortableColumns( $columns ) {
	$columns['answer_status'] = 'answer_status';
	return $columns;
}

function removeCustomPostTypesFromTinyMCELinkBuilder($query){
	$key = false;

    $cpt_to_remove = array(
    	'ama_questions',
    	'ama_post',
    	'ama_styles'
    );

    foreach ($cpt_to_remove as $custom_post_type) {
    	$key = array_search($custom_post_type, $query['post_type']);
    	if($key){
    		unset($query['post_type'][$key]);
    	} 
    }
    return $query; 
}
?>