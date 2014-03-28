<?php
/**
** A base module for [confirm]
**/

/* Shortcode handler */

add_action( 'wpcf7_init', 'wpcf7c_add_shortcode_confirm' );

function wpcf7c_add_shortcode_confirm() {
	wpcf7_add_shortcode( 'confirm', 'wpcf7c_confirm_shortcode_handler' );
}

function wpcf7c_confirm_shortcode_handler( $tag ) {
	$tag = new WPCF7_Shortcode( $tag );

	$class = wpcf7_form_controls_class( $tag->type );

	$atts = array();

	$atts['class'] = $tag->get_class_option( $class ) . " wpcf7c-elm-step1 wpcf7c-btn-confirm";
	$atts['id'] = $tag->get_option( 'id', 'id', true );
	$atts['tabindex'] = $tag->get_option( 'tabindex', 'int', true );

	$value = isset( $tag->values[0] ) ? $tag->values[0] : '';

	if ( empty( $value ) )
		$value = __( 'Confirm', 'contact-form-7-confirm' );

	$atts['type'] = 'submit';
	$atts['value'] = $value;

	$atts = wpcf7_format_atts( $atts );

	$html = sprintf( '<input %1$s />', $atts );

	return $html;
}


/* Tag generator */

add_action( 'admin_init', 'wpcf7c_add_tag_generator_confirm', 55 );

function wpcf7c_add_tag_generator_confirm() {
	if ( ! function_exists( 'wpcf7_add_tag_generator' ) )
		return;
	//eyeta_log("wpcf7c_add_tag_generator_confirm");
	wpcf7_add_tag_generator( 'confirm', __( 'Confirm button', 'contact-form-7-confirm' ),
		'wpcf7-tg-pane-confirm', 'wpcf7c_tg_pane_confirm', array( 'nameless' => 1 ) );
}

function wpcf7c_tg_pane_confirm( &$contact_form ) {
?>
<div id="wpcf7-tg-pane-confirm" class="hidden">
<form action="">
<table>
<tr>
<td><code>id</code> (<?php echo esc_html( __( 'optional', 'contact-form-7' ) ); ?>)<br />
<input type="text" name="id" class="idvalue oneline option" /></td>

<td><code>class</code> (<?php echo esc_html( __( 'optional', 'contact-form-7' ) ); ?>)<br />
<input type="text" name="class" class="classvalue oneline option" /></td>
</tr>

<tr>
<td><?php echo esc_html( __( 'Label', 'contact-form-7' ) ); ?> (<?php echo esc_html( __( 'optional', 'contact-form-7' ) ); ?>)<br />
<input type="text" name="values" class="oneline" /></td>

<td></td>
</tr>
</table>

<div class="tg-tag"><?php echo esc_html( __( "Copy this code and paste it into the form left.", 'contact-form-7' ) ); ?><br /><input type="text" name="confirm" class="tag wp-ui-text-highlight code" readonly="readonly" onfocus="this.select()" /></div>
</form>
</div>
<?php
}

