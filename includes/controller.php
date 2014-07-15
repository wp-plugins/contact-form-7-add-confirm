<?php
/**
 *
 *
 * Created by PhpStorm.
 * Author: Eyeta Co.,Ltd.(http://www.eyeta.jp)
 * 
 */


add_action( 'init', 'wpcf7c_control_init', 10 );
function wpcf7c_control_init() {

	wpcf7c_ajax_json_echo();

	// キャプチャ用フックの差替え
	remove_filter( 'wpcf7_validate_captchar', 'wpcf7_captcha_validation_filter', 10 );
	add_filter( 'wpcf7_validate_captchar', 'wpcf7c_captcha_validation_filter', 10, 2 );

}


function wpcf7c_ajax_json_echo() {
	if (isset($_POST['_wpcf7c'])) switch($_POST["_wpcf7c"]) {
		case "step1":
	//		$result = apply_filters( 'wpcf7_before_send_mail', $result );
			if(WPCF7_VERSION >= 3.9) {
				add_filter( 'wpcf7_acceptance', 'wpcf7c_acceptance_filter', 11, 1 );
			} else {
				add_action("wpcf7_before_send_mail", "wpcf7c_before_send_mail_step1", 10, 2);
			}

			//$items = apply_filters( 'wpcf7_ajax_json_echo', $items, $result );
			add_filter("wpcf7_ajax_json_echo", "wpcf7c_ajax_json_echo_step1", 10, 3);

			// flamingo対策
			remove_action( 'wpcf7_submit', 'wpcf7_flamingo_submit');

			// Contact Form DB対策
			//remove_action( 'wpcf7_before_send_mail', 'wpcf7_flamingo_submit');
			global $wp_filter, $merged_filters;
			if($wp_filter["wpcf7_before_send_mail"]) {
				foreach($wp_filter["wpcf7_before_send_mail"] as $priority => $actions) {
					foreach($actions as $key => $action) {
						if(is_array($action["function"])) {
							if(is_object($action["function"][0])) {
								if(get_class($action["function"][0]) == "CF7DBPlugin") {
									if($action["function"][1] == "saveFormData") {
										unset($wp_filter["wpcf7_before_send_mail"][$priority][$key]);
										if ( empty($wp_filter["wpcf7_before_send_mail"][$priority]) )
											unset($wp_filter["wpcf7_before_send_mail"][$priority]);
										unset($merged_filters["wpcf7_before_send_mail"]);
									}
								}
							}
						}
					}
				}
			}

			break;
		case "step2":
			//$items = apply_filters( 'wpcf7_ajax_json_echo', $items, $result );
			add_filter("wpcf7_ajax_json_echo", "wpcf7c_ajax_json_echo_step2", 10, 3);


			break;
	}


	return;
}

/**
 * 3.9からフックが変わったため、
 * acceptanceの機能を利用してメール送信をスキップする。
 *
 * @param $accepted
 * @return bool
 */
function wpcf7c_acceptance_filter($accepted) {
	global $wpcf7_confflag;

	if($accepted == false) {
		// すでにエラー
		return $accepted;
	}

	// これが呼ばれるときは必ずSTEP1
	$wpcf7_confflag = true;
	return false;
}

function wpcf7c_before_send_mail_step1(&$cls) {
	//eyeta_log("wpcf7c_before_send_mail_step1");
	$cls->skip_mail = true;
}

function wpcf7c_ajax_json_echo_step1($items, $result) {
	global $wpcf7_confflag;

	$flag = false;
	if(WPCF7_VERSION >= 3.9) {
		$flag = $wpcf7_confflag;
	} else {
		$flag = $result['mail_sent'];
	}

	if($flag) {
		if(!isset($items["onSubmit"]) || $items["onSubmit"] == null) {
			$items["onSubmit"] = array("wpcf7c_step1('" . $_POST['_wpcf7_unit_tag'] . "');");
		} else {
			$items["onSubmit"][] = "wpcf7c_step1('" . $_POST['_wpcf7_unit_tag'] . "');";
		}

		// オプションによる追加チェック
		$form = WPCF7_ContactForm::get_current();
		$on_confirm = $form->additional_setting( 'on_confirm', false );
		if ( ! empty( $on_confirm ) ) {
			foreach($on_confirm as $key => $on_confirm_func) {
				$items["onSubmit"][] = wpcf7_strip_quote( $on_confirm_func );
			}
		}


		$items["message"] = "";
		$items["mailSent"] = false;

		unset($items['captcha']);

	} else {
		// フィルタ指定があればエラー時にアンカーまでスクロールさせる
		$result = false;
		if(apply_filters( 'wpcf7c_input_error_scroll', $result )) {
			if(!isset($items["onSubmit"]) || $items["onSubmit"] == null) {
				$items["onSubmit"] = array("wpcf7c_scroll('" . $_POST['_wpcf7_unit_tag'] . "');");
			} else {
				$items["onSubmit"][] = "wpcf7c_scroll('" . $_POST['_wpcf7_unit_tag'] . "');";
			}
		}
	}

	return $items;
}

/*
 * captcha対策
 */

function wpcf7c_captcha_validation_filter( $result, $tag ) {
	$tag = new WPCF7_Shortcode( $tag );

	$type = $tag->type;
	$name = $tag->name;

	$captchac = '_wpcf7_captcha_challenge_' . $name;

	$prefix = isset( $_POST[$captchac] ) ? (string) $_POST[$captchac] : '';
	$response = isset( $_POST[$name] ) ? (string) $_POST[$name] : '';

	if ( 0 == strlen( $prefix ) || ! wpcf7_check_captcha( $prefix, $response ) ) {
		$result['valid'] = false;
		$result['reason'][$name] = wpcf7_get_message( 'captcha_not_match' );
	}

	if(0 != strlen( $prefix ) && $_POST["_wpcf7c"] == "step1") {
	} else if ( 0 != strlen( $prefix )) {
		wpcf7_remove_captcha( $prefix );
	}

	return $result;
}


function wpcf7c_ajax_json_echo_step2($items, $result) {
	//eyeta_log("wpcf7c_ajax_json_echo_step1");
	$flag = false;
	if(WPCF7_VERSION >= 3.9) {
		$flag = $items['mailSent'];
	} else {
		$flag = $result['mail_sent'];
	}

	if($flag) {
		if(!isset($items["onSubmit"]) || $items["onSubmit"] == null) {
			$items["onSubmit"] = array("wpcf7c_step2('" . $_POST['_wpcf7_unit_tag'] . "');");
		} else {
			$items["onSubmit"][] = "wpcf7c_step2('" . $_POST['_wpcf7_unit_tag'] . "');";
		}
	}

	return $items;
}
