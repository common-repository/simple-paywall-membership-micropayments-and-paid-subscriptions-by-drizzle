<?php

class Drizzle_Admin {

  const API_HOST = 'app.getdrizzle.com';

  const API_URL = 'https://app.getdrizzle.com/wp-api/v1';
  //const API_URL = 'http://localhost:8021/wp-api/v1';

  const API_PORT = 80;

  const NONCE = 'drizzle-update-key';

  private static $initiated = false;

  private static $notices   = array();

  public static function init() {

  if ( ! self::$initiated ) {

      self::init_hooks();

    }

  if ( isset( $_POST['action'] ) && $_POST['action'] == 'enter-key' ) {

      self::enter_api_key();

    }

  if ( isset( $_GET['action'] ) ) {
     if ( $_GET['action'] == 'delete-key' ) {
        if ( isset( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], self::NONCE ) )  self::deactivate_key( self::get_api_key() );        }
	}

  // security check!
  $key = self::get_api_key();
  if ( $key ) {
      if ( isset($_GET['drizzle_hide_para']) && !empty($_GET['drizzle_hide_para']) ) {

		  $para = $_GET['drizzle_hide_para'];

		  $post_ids = $_GET['post'];

		  if ( !empty($post_ids) ) {

				   if ( $para == 'unhide' ) {
						$unmark = self::drizzle_node_hide_para($post_ids, $para, 'unmark');

						foreach ( $post_ids as $postId ) {

							$valueOp = json_decode(get_option('drizzle_posts_options'));

							if ( !empty( $valueOp ) ){

								$valueOp = array_diff($valueOp, array($postId));

								$valueOp = array_values($valueOp);

								update_option( 'drizzle_posts_options', json_encode($valueOp));

							}
						}

				   }  else {
						$mark = self::drizzle_node_hide_para($post_ids, $para, 'mark');

							foreach ( $post_ids as $postId ) {

							$valueOp = json_decode(get_option('drizzle_posts_options'));

							if( !empty( $valueOp ) ) {

								$valueOp[] = $postId;

								$option = add_option( 'drizzle_posts_options', json_encode($valueOp), '', 'yes' );

								if ( $option != true ){

									$upd = update_option('drizzle_posts_options', json_encode($valueOp), 'yes');
								 }
							  } else {
								$valueOp[] = $postId;

								$option = add_option( 'drizzle_posts_options', json_encode($valueOp), '', 'yes' );

								if($option != true){

									$upd = update_option('drizzle_posts_options', json_encode($valueOp), 'yes');
								 }
							 }
						 }
				   }
			}
		wp_redirect( admin_url('edit.php'));

		exit();

	} // END of drizzle_hide_para

	if ( isset( $_GET['drizzle_subscriptions_plans']) && !empty($_GET['drizzle_subscriptions_plans'] ) ) {

			$plan_id = $_GET['drizzle_subscriptions_plans'];

		   	$post_ids = $_GET['post'];

			if($plan_id == '_none') {

				$plan_id = '';
			}

			if ( !empty($post_ids) ) {

				foreach ( $post_ids as $postId ) {

						$post = get_post($postId);

						$postURL = get_permalink($postId);

						$result = self::_set_drizzle_plans( $key, $postURL, $plan_id, $postId );

						$response = json_decode($result[1]);

						if ( $response->status != 'valid' ) {

							$error =  "There is some issue in setting plan for $postId .";

						} else {

							$message = "Plan is Assign to post $postId.";

						}
					}

			}

			wp_redirect( admin_url('edit.php'));

			exit();

		}	// END of  $_GET[drizzle_subscriptions_plans]

	if ( isset( $_GET['drizzle_wall_types']) && !empty($_GET['drizzle_wall_types'] ) ) {

			$WallType = $_GET['drizzle_wall_types'];

		   	$post_ids = $_GET['post'];

			if ( !empty ( $WallType ) ) {

				if ( !empty($post_ids) ) {

					foreach ( $post_ids as $postId ) {

						$updated_post = get_post($postId);

						$query = Drizzle_Admin::build_query(array(
						  'key' 		=> $key,
						  'url' 		=> get_permalink($updated_post),
						  'title' 		=> get_the_title($updated_post),
						  'content' 	=> $updated_post->post_content,
						  'id' 			=> "$updated_post->ID",
						  "$WallType"	=> "1"
						));


						$response = self::http_post($query, 'create-wall');


						$response = json_decode($response[1]);

						if ( $response->status != 'valid' ) {

						 	$error =  "There is some issue in setting wall type for $updated_post->ID .";

						} else {

							 $message = "wall type is Assign to post $updated_post->ID.  $WallType";
						}
					}
				}
			}

			wp_redirect( admin_url('edit.php'));

			exit();

		}	// END of  $_GET[drizzle_wall_types]

	}  // END OF KEY

  } // END of ini();

 public static function drizzle_node_hide_para( $post_ids, $para, $op ) {
    $content = '';

	$drizzlecontent = '';

	$key = self::get_api_key();

	foreach ( $post_ids as $post_id ) {

		$post = get_post($post_id);

		$string = $post->post_content;


		if ( isset( $post->post_content ) && !empty( $post->post_content ) ) {

			// remove paywall tag from content

			$s1_paywall =  str_replace('[paywall]', "", $string);

			$s2_paywall = str_replace('[/paywall]', "", $s1_paywall);

			$s3_paywall =  str_replace('<p>[paywall]</p>', "", $s2_paywall);

			$pureString_paywall = str_replace('<p>[/paywall]</p>', "", $s3_paywall);

			// remove drizzle tag from content

			$s1 =  str_replace('[drizzle]', "", $pureString_paywall);

			$s2 = str_replace('[/drizzle]', "", $s1);

			$s3 =  str_replace('<p>[drizzle]</p>', "", $s2);

			$pureString = str_replace('<p>[/drizzle]</p>', "", $s3);

			$pureString = wpautop($pureString);

			// Changes double line-breaks in the text into HTML paragraphs (<p>...</p>).


			if ( $op == 'mark' ) {

				if ( self::isHTML( $string ) ) {

					$contentArray = self::TrimPara($pureString, $para);

					$content = $contentArray['first_part'] . '[drizzle]' . $contentArray['second_part'] . '[/drizzle]';

					$drizzlecontent = $contentArray['second_part'];

				} else {

					$stringHalf = round(strlen($pureString) / 2);

					$firstHalf = self::plainStrig($pureString, $stringHalf);

					$secondHalf = str_replace($firstHalf, "", $pureString);

					$content = $firstHalf . '[drizzle]' . $secondHalf . '[/drizzle]';

					$drizzlecontent = $secondHalf;
				}

				$update_post = array(
				'ID' 			=> $post->ID,
				'post_content'	=> $content
				);

				$updatedpost = wp_update_post( $update_post, true );

				if ( is_wp_error( $updatedpost ) ) {

					$errors = $updatedpost->get_error_messages();

					foreach ($errors as $error) {

						 $error;
					}
				} else {

					self::save_post($post->ID);

				}

			} elseif ( $op == 'unmark' ) {

			 		$update_post = array(
					'ID' 			=> $post->ID,
					'post_content' 	=> $pureString
					);

					$updatedpost = wp_update_post( $update_post, true );

					if ( is_wp_error($updatedpost) ) {

						$errors = $updatedpost->get_error_messages();

						foreach ( $errors as $error ) {

								 $error;
						}
					} else {
						$message = 'post '.$post->post_title.' is suucess fully unmark !';

					}
				}

			} // isset( $post->post_content ) && !empty( $post->post_content )

	 } // foreach ( $post_ids as $post_id )

 } // drizzle_node_hide_para();

 public static function plainStrig( $code, $limit = 300 ) {

  if ( strlen($code) <= $limit ) {

	 return $code;
	}
	$html = substr($code, 0, $limit);

	preg_match_all ( "#<([a-zA-Z]+)#", $html, $result );

	foreach($result[1] AS $key => $value) {

		if ( strtolower($value) == 'br' ) {

			unset($result[1][$key]);
		}
	}
	$openedtags = $result[1];

	preg_match_all ( "#</([a-zA-Z]+)>#iU", $html, $result );

	$closedtags = $result[1];

	foreach($closedtags AS $key => $value) {

		if ( ($k = array_search($value, $openedtags)) === FALSE ) {

			continue;
		} else  {

			unset($openedtags[$k]);
		}
	}
	if ( empty($openedtags) ) {

		if ( strpos($code, ' ', $limit) == $limit ) {

			return $html;
		} else {
			return substr($code, 0, strpos($code, ' ', $limit));
		}
	}
	$position = 0;

	$close_tag = '';

	foreach($openedtags AS $key => $value) {

		$p = strpos($code, ('</' . $value . '>'), $limit);

		if ( $p === FALSE )	{

			$code .= ('</' . $value . '>');
		} elseif ( $p > $position ) {

			$close_tag = '</' . $value . '>';

			$position = $p;
		}
	}
	if ( $position == 0 ) {

		return $code;
	}
	return substr($code, 0, $position) . $close_tag;
 }

	/**

	*	Helper function for hide number para.

	*/

 public static function TrimPara( $text, $number_para ) {
  //#<p([^>])*>#
  $number_para = $number_para+1;

  $html = htmlspecialchars($text);

  $parts = preg_split('#&lt;p([^&lt;])#', $html);

  $cntto3p = "";

  $contAF3p = "";

  if ( !empty ( $parts[0] ) && !empty ( $parts[1] ) ) {

	    $initial_content = $parts[0]; // initial content if $parts[0] is not empty

	   $cntto3p .= html_entity_decode($initial_content);

  		for ($i = 1; $i < count( $parts ); $i++) {

		   if ( $i < $number_para ) {

			 $cntto3p .=  '<p'. html_entity_decode($parts[$i]); //

			} else {

			 $contAF3p  .= '<p'. html_entity_decode($parts[$i]);

			}

	 	 }

 	 } else {

		$parts = preg_split('#<p()#', $text);

		for ( $i = 1; $i < count( $parts ); $i++ ) {

			   if ($i < $number_para) {

				  $cntto3p .= '<p' . $parts[$i];

				} else {

				 $contAF3p  .= '<p' . $parts[$i];

				}

		}

 	 }

  $content = array('first_part' => $cntto3p, 'second_part' => $contAF3p);

  return $content;

 }


 public static function isHTML( $text ) {

  $processed = htmlentities($text);

  if ($processed == $text) return false;

	  return true;
 }

  /**

   * Initializes WordPress hooks

   */

  private static function init_hooks() {

    self::$initiated = true;

    add_action( 'admin_init', array( 'Drizzle_Admin', 'admin_init' ) );

    add_action( 'admin_menu', array( 'Drizzle_Admin', 'admin_menu' ) );

    add_action( 'admin_enqueue_scripts', array( 'Drizzle_Admin', 'load_resources' ) );

	if ( $key = self::get_api_key() ) {

    add_filter( 'manage_posts_columns', array( 'Drizzle_Admin', 'drizzle_columns_head' ) );

    add_action( 'manage_posts_custom_column', array( 'Drizzle_Admin', 'drizzle_columns_content' ), 10, 2);

	//add_action( 'bulk_actions-edit-post', array( 'Drizzle_Admin', 'drizzle_admin_wall_types' ) );

	add_action( 'bulk_actions-edit-post', array( 'Drizzle_Admin', 'drizzle_admin_select_para' ) );

	//add_action( 'bulk_actions-edit-post', array( 'Drizzle_Admin', 'drizzle_admin_subscriptions_plans' ) );

	// Post meta box for drizzle
		if ( is_admin() ) {
			add_action( 'add_meta_boxes', array( 'Drizzle_Admin', 'drizzle_register_meta_boxes') );
			add_action( 'save_post', array( 'Drizzle_Admin', 'drizzle_save_meta_box'), 10, 2 );
		}
	}

 }

/**
 * Register meta box(es).
 */
 public static function drizzle_register_meta_boxes() {
    add_meta_box( 'meta_drizzle_config', __( 'Drizzle Configuration', 'drizzleconfiguration' ), array( 'Drizzle_Admin', 'drizzle_config_display_callback'), 'post','side' );
}

/**
 * Meta box display callback.
 *
 * @param WP_Post $post Current post object.
 */
 public static function drizzle_config_display_callback( $post ) {
	//Add nonce for security and authentication.
    wp_nonce_field( basename( __FILE__ ), 'drizzle_post_action_nonce' );

    // Plan listing start
    /*$planList   = '';
    $planList .=  "<p>
    <strong>Subscription</strong>
    <select id='drizzle_plan' name='drizzle_plan' style='min-width:200px;'>";
	$plansname[''] = 'Assign Plan';
	$plansname['_none'] = 'Remove Plan';
	$plans = self::_get_drizzle_plans();
	$plans = json_decode($plans[1]);
	if ( $plans->status != 'invalid' ) {
		if (is_object ($plans) ) {
			$plans = $plans->plans;
		}
		if(is_array($plans)) {
			foreach ( $plans as $key1 => $plan ) {
				$plansname[$plan->_id] = $plan->name;
			}
	    }
	}
	$selectedPlan = esc_attr( get_post_meta( $post->ID, 'drizzle_plan', true ) );
	foreach ( $plansname as $plan => $plan_id ) {
		$planList .= sprintf
			(
				'<option value="%s" %s>%s</option>',
				$plan,
				$plan == $selectedPlan ? ' selected="selected"':'',
				$plan_id
			);
		}
	$planList .= '</select> </p>';
	// Plan listing end */

	$wallTypes = array(
	   'Regular paywall'	   		  => '',
	   'Adblock wall'		   		  => 'adblock',
	   'Lead gen wall' 		   		  => 'leadGeneration',
	   'Paywall with Social Virality' => 'enableSocialVirality',
	   'Disable Drizzle'	   		  => 'disabled',
	   //'is Video' 				=> 'isVideo',
	  );

	 $selectedWalltype = esc_attr( get_post_meta( $post->ID, 'drizzle_wall_types', true ) );
	 $wallTypeListing  = '<p>
	 <strong>Paywall type</strong>
	 <select id="drizzle_wall_types" name="drizzle_wall_types" style="min-width:200px;">';
		foreach ( $wallTypes as $label => $walltype ) {
			$wallTypeListing .= sprintf
				(
					'<option value="%s" %s>%s</option>',
						$walltype,
						$walltype == $selectedWalltype ? ' selected="selected"':'',
						$label
				);
		}
	$wallTypeListing .='</select></p>';


	//echo $planList.$wallTypeListing;
	echo $wallTypeListing;

}


 /**
 * Get subscription plans.
 */
 public static function _get_drizzle_plans() {

  $base_url = get_site_url();

  $key = self::get_api_key();

  return self::http_post(build_query( array( 'key' => $key, 'url' => $base_url ) ), 'get-plans' );

 }

/**

 * Set plans to individual contents.

 */
 public static function _set_drizzle_plans( $key, $url, $planId, $post_id ) {

  return self::http_post(build_query( array( 'key' => $key, 'url' => $url, 'planId' => $planId, 'id' => $post_id) ), 'set-plan' );

}

 /**

 * Get Wall plans to individual contents.

 */
 public static function _get_drizzle_wall_plans() {

  $base_url = get_site_url();

  $key = self::get_api_key();

  return self::http_post(build_query( array('key' => $key, 'url' => $base_url) ), 'get-walls');

}

 /**

 * Start of div and Select list for wall types

 */

 public static function drizzle_admin_wall_types( $actions ) {
	$WallTypes = array(
	   'Wall Type' 	  			=> '',
	   'Adblock'				=> 'adblock',
	   'lead Generation'  	 	=> 'leadGeneration',
	   'Disable Drizzle'	    => 'disabled',
	   #'disable Social Virality'=> 'disableSocialVirality',
	   #'is Video' 				=> 'isVideo',
	  );
	  	$selectwallType = '';
		$selectwallType .= '<div style="float: right;margin-top:10px;">

								<select name="drizzle_wall_types">';

									$current_v = isset($_GET['drizzle_wall_types'])? $_GET['drizzle_wall_types']:'';

									foreach ( $WallTypes as $label => $walltype ) {

										$selectwallType .= sprintf
											(
												'<option value="%s"%s>%s</option>',

													$walltype,

													$walltype == $current_v? ' selected="selected"':'',

													$label
											);
										}

			$selectwallType .='</select>';

	echo $selectwallType;

	return $actions;
 }

  /**

 * Middle Select list for parahide

 */


 public static function drizzle_admin_select_para( $actions ) {

    $values = array(
	   'Drizzle' 	  			  => '',
	   'Unhide'					  => 'unhide',
	   'Hide from 1st paragraph'  => 1,
	   'Hide from 2nd paragraph'  => 2,
	   'Hide from 3rd paragraph'  => 3,
	   'Hide from 4th paragraph'  => 4,
	   'Hide from 5th paragraph'  => 5,
	   'Hide from 6th paragraph'  => 6,
	   'Hide from 7th paragraph'  => 7,
	   'Hide from 8th paragraph'  => 8,
	   'Hide from 9th paragraph'  => 9,
	   'Hide from 10th paragraph' => 10,
	  );
        $selectOp ='';

        $selectOp .= '<style> .alignleft { float:none!important; } </style>
        <div style="float: right;margin-top:10px;">
         <select name="drizzle_hide_para">';


						$current_v = isset($_GET['drizzle_hide_para'])? $_GET['drizzle_hide_para']:'';

							foreach ( $values as $label => $value ) {

								$selectOp .= sprintf
									(
										'<option value="%s"%s>%s</option>',
										$value,

										$value == $current_v? ' selected="selected"':'',

										$label
									);
							 }

    $selectOp .='</select>
    <input type="submit" value="Update" class="button action" id="doaction" />

	</div>
    ';

    echo $selectOp;

	return $actions;

 }

 /**

 * End of div  for subscription plans

 */


 public static function drizzle_admin_subscriptions_plans( $actions ) {

	$plansname[''] = 'Assign Plan';

	$plansname['_none'] = 'Remove Plan';

	$plans = self::_get_drizzle_plans();

	$plans = json_decode($plans[1]);

	if ( $plans->status != 'invalid' ) {

		if (is_object ($plans) ) {

		$plans = $plans->plans;

		}

		if(is_array($plans)) {

			foreach ( $plans as $key1 => $plan ) {

				$plansname[$plan->_id] = $plan->name;
			}
	    }
	}
	$selectPlan ='';

	$selectPlan .= '<style> .alignleft { float:none!important; } </style>';

	$selectPlan .= '<select name="drizzle_subscriptions_plans">';

						$current_v = isset($_GET['drizzle_subscriptions_plans'])? $_GET['drizzle_subscriptions_plans']:'';

						foreach ( $plansname as $plan => $plan_id ) {

							$selectPlan .= sprintf
								(
									'<option value="%s"%s>%s</option>',

									$plan,

									$plan == $current_v? ' selected="selected"':'',

									$plan_id
								);
							}

	$selectPlan .='</select>

				<input type="submit" value="Update" class="button action" id="doaction" />

	</div>';

	echo $selectPlan;

	return $actions;
 }



  public static function drizzle_columns_head($defaults) {

      $defaults['drizzle_status'] = 'Drizzle';

     // $defaults['drizzle_subscription'] = 'Subscription';

      return $defaults;

  }

 public static function drizzle_columns_content( $column_name, $post_ID ) {

	$valueOp = json_decode( get_option( 'drizzle_posts_options' ) );

    if ( $column_name == 'drizzle_status' ) {

            if ( !empty( $valueOp ) && in_array( $post_ID, $valueOp ) ) {

			  	echo '<span style="color:green">Active</span>';

			} else {

              echo 'Inactive';

		    }
      }

    /*if ( $column_name == 'drizzle_subscription' ) {

            $drizzle_sub_status = '---';

			$getwallPlans = self::_get_drizzle_wall_plans();

		    $wallPlans = json_decode($getwallPlans[1]);

		    if($wallPlans->status == 'valid') {

				$wallPlans = $wallPlans->walls;

				if ( !empty( $wallPlans ) ) {

					foreach ( $wallPlans as $plan ) {

						if( $plan->externalId == $post_ID ) {

							$drizzle_sub_status = '<strong style="color:green">'.$plan->planName.'</strong>';
						}

					}

				}
		    }
		echo $drizzle_sub_status;
      }*/

  }

 public static function admin_init() {

    load_plugin_textdomain( 'drizzle' );

    add_action( 'save_post', array( 'Drizzle_Admin', 'save_post' ) );

  }

  public static function admin_menu() {

    self::load_menu();

  }

  public static function load_menu() {

    add_options_page( __('Simple paywall', 'paywall'), __('Simple paywall', 'paywall'), 'manage_options', 'drizzle-key-config', array( 'Drizzle_Admin', 'display_configuration_page' ) );

  }

  public static function load_resources() {

    global $hook_suffix;

    if ( in_array( $hook_suffix, array(

      'post.php',

      'post-new.php',

    ) ) ) {

      wp_register_script( 'drizzle_quicktags.js', plugin_dir_url( __FILE__ ) . '_inc/quicktags.js', array('jquery','quicktags'), DRIZZLE_VERSION );

      wp_enqueue_script( 'drizzle_quicktags.js' );

    }

  }


  private static function parse_shortcode_atts( $content, $shortcode ) {


    //Returns a sting consisting of all registered shortcodes.

    $pattern = get_shortcode_regex();

    //Checks the post content to see if any shortcodes are present.

    $shortcodes = preg_match_all( '/'. $pattern .'/s', $content, $matches );

    //Check to see which key our Attributes are sotred in.

    $shortcode_key = array_search( $shortcode, $matches[2] );

    //Create an new array of atts for our shortcode only.

    $shortcode_atts[] = $matches[3][$shortcode_key];

    //Ensure we don't have an empty strings

    $shortcode_atts = array_filter( $shortcode_atts );

    if ( ! empty( $shortcode_atts ) ) {

      //Pull out shortcode attributes based on the above key.

      $shortcode_atts = shortcode_parse_atts( implode( ',', $shortcode_atts ) );

    }

    $shortcode_atts[ "content" ] = $matches[5][$shortcode_key];

    return $shortcode_atts;

  }



  public static function save_post( $post_id ) {

    if ( wp_is_post_revision( $post_id ) ) {

      return;

    }

    $updated_post = get_post($post_id);

    if( !has_shortcode( $updated_post->post_content, 'drizzle') ) {

      delete_post_meta( $post_id, '_drizzle_encrypted_content' );

      return;

    }

    $key = self::get_api_key();

    if (!$key) {

      return;

    }

    $shortcode_atts = self::parse_shortcode_atts( $updated_post->post_content, 'drizzle' );

    if (!array_key_exists('content', $shortcode_atts)) {

      return;

    }


    $content = trim(trim($shortcode_atts[ 'content' ]), '\n');

	//convert content with double line break to paragraph (<p>....</p>).

	$content = wpautop($content);

    if (!$content) {

      return;

    }


	// Wall type save
	$drizzle_walltypes_new_value = ( isset( $_POST['drizzle_wall_types'] ) ? sanitize_html_class( $_POST['drizzle_wall_types'] ) : '' );
	$drizzle_walltype_meta_key = 'drizzle_wall_types';
	$walltype_meta_value = get_post_meta( $post_id, $drizzle_walltype_meta_key, true );

	if ( $drizzle_walltypes_new_value && '' == $walltype_meta_value ) {
		add_post_meta( $post_id, $drizzle_walltype_meta_key, $drizzle_walltypes_new_value, true );
	} elseif ( $drizzle_walltypes_new_value && $drizzle_walltypes_new_value != $walltype_meta_value ) {
		update_post_meta( $post_id, $drizzle_walltype_meta_key, $drizzle_walltypes_new_value );
	} elseif ( '' == $drizzle_walltypes_new_value && $walltype_meta_value ) {
		delete_post_meta( $post_id, $drizzle_walltype_meta_key, $walltype_meta_value );
	}

    $query = Drizzle_Admin::build_query(array(
      'key' 	=> $key,
      'url' 	=> get_permalink($updated_post),
      'title' 	=> get_the_title($updated_post),
      'content' => $content,
      'id' 		=> "$post_id",
	  "$drizzle_walltypes_new_value"	=> "1"
    ));

    $response = self::http_post($query, 'create-wall');

    if ( empty( $response[1] ) ) {

      return;

    }

    $response = json_decode( $response[1] );

    if ( ! $response ) {

      return;

    }

    if ( $response->status == 'valid') {

      update_post_meta( $post_id, '_drizzle_encrypted_content', '1' );

    }

  }


/**
 * Save meta box content.
 *
 * @param int $post_id Post ID
 */
 public static function drizzle_save_meta_box( $post_id, $post ) {
	$key = self::get_api_key();
	$postURL = get_permalink($post_id);

  /* Verify the nonce before proceeding. */
  if ( !isset( $_POST['drizzle_post_action_nonce'] ) || !wp_verify_nonce( $_POST['drizzle_post_action_nonce'], basename( __FILE__ ) ) )
    return $post_id;

  /* Get the post type object. */
  $post_type = get_post_type_object( $post->post_type );

  /* Check if the current user has permission to edit the post. */
  if ( !current_user_can( $post_type->cap->edit_post, $post_id ) )
    return $post_id;

  // Plan save
  /*$drizzle_plan_new_value = ( isset( $_POST['drizzle_plan'] ) ? sanitize_html_class( $_POST['drizzle_plan'] ) : '' );
  $drizzle_plan_meta_key = 'drizzle_plan';
  $plan_meta_value = get_post_meta( $post_id, $drizzle_plan_meta_key, true );

  if ( $drizzle_plan_new_value && '' == $plan_meta_value ) {

    add_post_meta( $post_id, $drizzle_plan_meta_key, $drizzle_plan_new_value, true );
    if($drizzle_plan_new_value == '_none') {
		$drizzle_plan_new_value = '';
	}
    $result = self::_set_drizzle_plans( $key, $postURL, $drizzle_plan_new_value, $post_id);

  } elseif ( $drizzle_plan_new_value && $drizzle_plan_new_value != $plan_meta_value ) {

    update_post_meta( $post_id, $drizzle_plan_meta_key, $drizzle_plan_new_value );
    if($drizzle_plan_new_value == '_none') {
		$drizzle_plan_new_value = '';
	}
    $result = self::_set_drizzle_plans( $key, $postURL, $drizzle_plan_new_value, $post_id);

  } elseif ( '' == $drizzle_plan_new_value && $plan_meta_value ) {
    delete_post_meta( $post_id, $drizzle_plan_meta_key, $plan_meta_value );
    if($drizzle_plan_new_value == '_none') {
		$drizzle_plan_new_value = '';
	}
    $result = self::_set_drizzle_plans( $key, $postURL, $drizzle_plan_new_value, $post_id);
  }*/

}

  private static function bail_on_activation( $message, $deactivate = true ) {

  ?>

<!doctype html>

<html>

<head>

  <meta charset="<?php bloginfo( 'charset' ); ?>">

  <style>

  * {

  text-align: center;

  margin: 0;

  padding: 0;

  font-family: "Lucida Grande",Verdana,Arial,"Bitstream Vera Sans",sans-serif;

  }

  p {

  margin-top: 1em;

  font-size: 18px;

  }

  </style>

</head>

<body>

  <p><?php echo $message; ?></p>

</body>

</html>

<?php

    exit;

  }

  /**

   * Attached to activate_{ plugin_basename( __FILES__ ) } by register_activation_hook()

   * @static

   */

  public static function plugin_activation() {

    if ( version_compare( $GLOBALS['wp_version'], DRIZZLE__MINIMUM_WP_VERSION, '<' ) ) {

      load_plugin_textdomain( 'drizzle' );

      $message = '<strong>'

	  				.sprintf(esc_html__( 'Paywall (by Drizzle) %s requires WordPress %s or higher.' , 'drizzle'), DRIZZLE_VERSION, DRIZZLE__MINIMUM_WP_VERSION ).

				'</strong> '

				.sprintf(__('Please <a href="%1$s">upgrade WordPress</a> to a current version.', 'drizzle'), 'https://codex.wordpress.org/Upgrading_WordPress');

      Drizzle_Admin::bail_on_activation( $message );

    }

  }

  /**

   * Removes all connection options

   * @static

   */

  public static function plugin_deactivation( ) {

    return self::deactivate_key( self::get_api_key() );

  }

  public static function deactivate_key( $key ) {

    $response = self::http_post( Drizzle_Admin::build_query( array( 'key' => $key, 'blog' => get_option('home') ) ), 'deactivate' );

    if ( $response[1] != 'deactivated' )

      return 'failed';

    delete_option( 'drizzle_api_key' );

    return $response[1];

  }

  public static function get_api_key() {

    return get_option('drizzle_api_key');

  }

  public static function enter_api_key() {

    if ( function_exists('current_user_can') && !current_user_can('manage_options') )

      die(__('Cheatin&#8217; uh?', 'drizzle'));

    if ( !wp_verify_nonce( $_POST['_wpnonce'], self::NONCE ) ) {

      return false;

    }

    $new_key = esc_attr( $_POST['key'] );

    $old_key = Drizzle_Admin::get_api_key();

    if ( empty( $new_key ) ) {

      if ( !empty( $old_key ) ) {

        delete_option( 'drizzle_api_key' );

        self::$notices[] = 'new-key-empty';

      }

    }

    elseif ( $new_key != $old_key ) {

      self::save_key( $new_key );

    }

    return true;

  }

  public static function save_key( $api_key ) {

    $key_status = self::verify_key( $api_key );

    if ( $key_status == 'valid' ) {

      update_option( 'drizzle_api_key', $api_key );

      self::$notices['status'] = 'new-key-valid';

    } elseif ( in_array( $key_status, array( 'invalid', 'failed' ) ) )

      self::$notices['status'] = 'new-key-'.$key_status;

  }

 public static function check_key_status( $key ) {

    return Drizzle_Admin::http_post( Drizzle_Admin::build_query( array( 'key' => $key, 'blog' => get_option('home') ) ), 'verify-key' );

  }

 public static function verify_key( $key ) {

    $response = self::check_key_status( $key );

    if ( $response[1] != 'valid' && $response[1] != 'invalid' )

      return 'failed';

    return $response[1];

  }

 public static function view( $name, array $args = array() ) {

    foreach ( $args AS $key => $val ) {

      $$key = $val;

    }

 load_plugin_textdomain( 'drizzle' );

    $file = DRIZZLE__PLUGIN_DIR . 'views/'. $name . '.php';

    include( $file );

  }

 public static function display_configuration_page() {

    if ( !current_user_can( 'manage_options' ) )  {

      wp_die( __( 'You do not have sufficient permissions to access this page.' ) );

    }

    $api_key      = self::get_api_key();

    $is_key_valid = $api_key && self::verify_key($api_key) == 'valid';

    Drizzle_Admin::view( 'config', compact( 'api_key', 'is_key_valid' ) );

  }

 public static function get_page_url( $page = 'config' ) {

    $args = array( 'page' => 'drizzle-key-config' );

    if ( $page == 'delete_key' )

      $args = array( 'page' => 'drizzle-key-config', 'view' => 'config', 'action' => 'delete-key', '_wpnonce' => wp_create_nonce( self::NONCE ) );

    $url = add_query_arg( $args, admin_url( 'options-general.php' ) );

   return $url;

  }

  /**

   * Essentially a copy of WP's build_query but one that doesn't expect pre-urlencoded values.

   *

   * @param array $args An array of key => value pairs

   * @return string A string ready for use as a URL query string.

   */

  public static function build_query( $args ) {

    return _http_build_query( $args, '', '&' );

  }

  /**

   * Log debugging info to the error log.

   *

   * Enabled when WP_DEBUG_LOG is enabled (and WP_DEBUG, since according to

   * core, "WP_DEBUG_DISPLAY and WP_DEBUG_LOG perform no function unless

   * WP_DEBUG is true), but can be disabled via the drizzle_debug_log filter.

   *

   * @param mixed $drizzle_debug The data to log.

   */

  public static function log( $drizzle_debug ) {

    if ( apply_filters( 'drizzle_debug_log', defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) ) {

      error_log( print_r( compact( 'drizzle_debug' ), true ) );

    }

  }

  /**

   * Make a POST request to the Drizzle API.

   *

   * @param string $request The body of the request.

   * @param string $path The path for the request.

   * @return array A two-member array consisting of the headers and the response body, both empty in the case of a failure.

   */

  public static function http_post( $request, $path ) {

    $drizzle_ua = sprintf( 'WordPress/%s | Paywall/%s', $GLOBALS['wp_version'], constant( 'DRIZZLE_VERSION' ) );

    $content_length = strlen( $request );

    //$api_key   = self::get_api_key();

    $host      = self::API_HOST;

    $http_host = self::API_URL;

    $http_args = array(

      'body' => $request,

      'headers' => array(

        'Content-Type' => 'application/x-www-form-urlencoded; charset=' . get_option( 'blog_charset' ),

        'Host' => $host,

        'User-Agent' => $drizzle_ua,

      ),

      'httpversion' => '1.0',

      'timeout' => 15

    );

    $drizzle_url = "{$http_host}/{$path}";

    // Check if SSL requests were disabled fewer than X hours ago.

    $ssl_disabled = get_option( '_drizzle_ssl_disabled' );

    if ( $ssl_disabled && $ssl_disabled < ( time() - 60 * 60 * 24 ) ) { // 24 hours

      $ssl_disabled = false;

      delete_option( '_drizzle_ssl_disabled' );

    }

    $ssl_failed = false;


    if ( !$ssl_disabled ) {

      $response = wp_remote_post( $drizzle_url, $http_args );

      Drizzle_Admin::log( compact( 'drizzle_url', 'http_args', 'response' ) );

      if ( is_wp_error( $response ) ) {

        $ssl_failed = true;

      }

    }

    if ( $ssl_disabled || $ssl_failed ) {

      $http_args['sslverify'] = false;

      $response = wp_remote_post( $drizzle_url, $http_args );

      Drizzle_Admin::log( compact( 'drizzle_url', 'http_args', 'response' ) );

    }

    if ( $ssl_failed ) {

      // The request failed when using SSL but succeeded without it. Disable SSL for future requests.

      update_option( '_drizzle_ssl_disabled', time() );

    }

    if ( is_wp_error( $response ) ) {

      return array('', '');

    }

    $simplified_response = array( $response['headers'], $response['body'] );

    return $simplified_response;

  }



}

