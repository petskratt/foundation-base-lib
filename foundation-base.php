<?php

if ( ! defined( 'ABSPATH' ) ) exit;

/* ======================================
	Adapting Thematic HTML to Foundation
   ====================================== */

if (!defined('GOOGLE_JQUERY_VERSION') ) define('GOOGLE_JQUERY_VERSION', '2.1.3');
if (!defined('GOOGLE_JQUERY_VERSION_IE8') ) define('GOOGLE_JQUERY_VERSION_IE8', '1.11.2');

// use jQuery from Google Hosted Libraries and de-register jQuery for IE8 support

add_action('init', 'krt_google_jquery');

function krt_google_jquery() {

    if (!defined('GOOGLE_JQUERY') || GOOGLE_JQUERY === true ) {
    	if (!is_admin() && !in_array($GLOBALS['pagenow'], array('wp-login.php', 'wp-register.php')) ) {
    		wp_deregister_script('jquery-migrate');
    		wp_deregister_script('jquery');

            // jQuery is loaded in footer using IE conditionals
    		if ( !IE8_F5_SUPPORT ) {
    			wp_register_script('jquery', '//ajax.googleapis.com/ajax/libs/jquery/'.GOOGLE_JQUERY_VERSION.'/jquery.min.js', false, GOOGLE_JQUERY_VERSION, true);
    			wp_enqueue_script('jquery');
    		} else {
        		wp_register_script('jquery', '', false, false, true);
    		}
    	}
	}
}


// script manager template for registering and enqueuing files

add_action('wp_enqueue_scripts', 'foundationbase_script_manager');

function foundationbase_script_manager() {

    if (!is_admin() && !in_array($GLOBALS['pagenow'], array('wp-login.php', 'wp-register.php')) ) {
    	// wp_register_script template ( $handle, $src, $deps, $ver, $in_footer );

    	// registers modernizr script, stylesheet local path, no dependency, no version, loads in header
    	wp_register_script('modernizr-js', get_stylesheet_directory_uri() . '/lib/foundation/js/vendor/modernizr.js', false, false, false);
        wp_register_script('app-js', get_stylesheet_directory_uri() . '/js/app.min.js', array('jquery'), false, true);

    	// enqueue the scripts for use in theme
    	wp_enqueue_script ('modernizr-js');

    	//always enqueue this last, helps with conflicts
    	wp_enqueue_script ('app-js');
	}

}

// header modifications with and without IE8 support

if ( !IE8_F5_SUPPORT ) {

	// add no-js class to html - if not overwriting entire function
	add_filter('thematic_html_class', 'krt_add_html_class_nojs');

	function krt_add_html_class_nojs($classes) {
		if ( $classes ) {
			$classes .= " ";
		}
		$classes .= 'no-js';
		return $classes;
	}
} else {

	// HTML and HEAD overrides for IE8 support

	function childtheme_override_html( $class_att = 'no-js' ) {
			$html_class = apply_filters( 'thematic_html_class' , $class_att );
	?>
<!--[if lt IE 9]> <html class="<?php if ( $html_class ) { echo( $html_class . ' ' ); } ?>lt-ie10 lt-ie9" <?php language_attributes() ?> xmlns:fb="http://ogp.me/ns/fb#"> <![endif]-->
<!--[if IE 9]> <html class="<?php if ( $html_class ) { echo( $html_class . ' ' ); } ?>lt-ie10" <?php language_attributes() ?> xmlns:fb="http://ogp.me/ns/fb#"> <![endif]-->
<!--[if gt IE 9]><!--> <html class="<?php 	if ( $html_class ) { echo $html_class; } ?>" <?php language_attributes() ?> xmlns:fb="http://ogp.me/ns/fb#"> <!--<![endif]-->
<?php
	}

	function childtheme_override_head() {
		?>
<head>
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
	<?php
	}

	add_action('wp_head', 'krt_ie8_conditional_header', 8);

	function krt_ie8_conditional_header () {
	?>
<!--[if lt IE 9]>
	<link rel="stylesheet" href="<?php echo get_stylesheet_directory_uri(); ?>/library/ie8-f5/ie8.css">
	<script src="<?php echo get_stylesheet_directory_uri(); ?>/library/ie8-f5/ie8-head.min.js"></script>
<![endif]-->
<?php
	}

    if (!defined('GOOGLE_JQUERY') || GOOGLE_JQUERY === true ) {
    	add_action('wp_footer', 'krt_ie8_conditional_footer_abovescripts', 1);
    }

	function krt_ie8_conditional_footer_abovescripts () {
	?>
<!--[if lt IE 9]>
	<script type='text/javascript' src='//ajax.googleapis.com/ajax/libs/jquery/<?= GOOGLE_JQUERY_VERSION_IE8 ?>/jquery.min.js?ver=<?= GOOGLE_JQUERY_VERSION_IE8 ?>'></script>
<![endif]-->
<!--[if gte IE 9]><!--> <script type='text/javascript' src='//ajax.googleapis.com/ajax/libs/jquery/<?= GOOGLE_JQUERY_VERSION ?>/jquery.min.js?ver=<?= GOOGLE_JQUERY_VERSION ?>'></script> <!--<![endif]-->
<?php
	}

	add_action('wp_footer', 'krt_ie8_conditional_footer_belowscripts', 20);

	function krt_ie8_conditional_footer_belowscripts () {
	?>
<!--[if lt IE 9]>
	<script src="<?php echo get_stylesheet_directory_uri(); ?>/library/ie8-f5/ie8.js"></script>
<![endif]-->
<?php
	}

}


// kill scripts for Thematic drop downs, combined and reloaded by the script manager (dropdowns-js)

function childtheme_override_head_scripts() {
	// silence
}




// add favicon to site, add 16x16 "favicon.ico" image to child themes main folder

function childtheme_add_favicon() { ?>
  <link rel="apple-touch-icon-precomposed" sizes="144x144"
  href="<?php echo get_stylesheet_directory_uri(); ?>/favicons/apple-touch-icon-144x144-precomposed.png">
  <link rel="apple-touch-icon-precomposed" sizes="114x114"
  href="<?php echo get_stylesheet_directory_uri(); ?>/favicons/apple-touch-icon-114x114-precomposed.png">
  <link rel="apple-touch-icon-precomposed" sizes="72x72"
  href="<?php echo get_stylesheet_directory_uri(); ?>/favicons/apple-touch-icon-72x72-precomposed.png">
  <link rel="apple-touch-icon-precomposed"
  href="<?php echo get_stylesheet_directory_uri(); ?>/favicons/apple-touch-icon-precomposed.png">
  <link rel="shortcut icon"
  href="<?php echo get_stylesheet_directory_uri(); ?>/favicons/favicon.ico" type="image/x-icon" />
<?php }

add_action('wp_head', 'childtheme_add_favicon');

// remove vcard from elements - to avoid Foundation vcard styling

add_filter('thematic_postmeta_authorlink', 'krt_remove_vcard');
add_filter('thematic_list_comments_arg', 'foundationbase_list_comments_arg');

function krt_remove_vcard($s) {
	return str_replace( 'vcard', '' , $s );
}

function foundationbase_list_comments_arg() {
	$content = array(
		'type' => 'comment',
		'callback' => 'foundationbase_comments'
	);
	return $content;
}

function foundationbase_comments($comment, $args, $depth) {
	$GLOBALS['comment'] = $comment;
	$GLOBALS['comment_depth'] = $depth;
?>

	   	<li id="comment-<?php comment_ID() ?>" <?php comment_class() ?>>

			<?php
	// action hook for inserting content above #comment
	thematic_abovecomment();
?>

			<article id="comment-body-<?php comment_ID() ?>" class="comment-body">
				<footer class="comment-utility">
					<div class="comment-author"><?php thematic_commenter_link() ?></div><!-- .comment-author -->

						<?php thematic_commentmeta( true ); ?>

						<?php
	if ( $comment->comment_approved == '0' ) {
		echo "\t\t\t\t\t" . '<span class="unapproved">';
		_e( 'Your comment is awaiting moderation', 'thematic' );
		echo ".</span>\n";
	}
?>
				</footer><!-- .comment-utility -->

		        <div class="comment-content">
		    		<?php comment_text() ?>
				</div><!-- .comment-content -->

				<?php // echo the comment reply link with help from Justin Tadlock http://justintadlock.com/ and Will Norris http://willnorris.com/

	if( $args['type'] == 'all' || get_comment_type() == 'comment' ) {
		comment_reply_link( array_merge( $args, array(
					'add_below'  => 'comment-body',
					'reply_text' => __( 'Reply','thematic' ),
					'login_text' => __( 'Log in to reply.','thematic' ),
					'depth'      => $depth,
					'before'     => '<div class="comment-replylink">',
					'after'      => '</div>'
				)));
	}
?>

			</article><!-- .comment-body -->

			<?php
	// action hook for inserting content above #comment
	thematic_belowcomment()
?>

<?php }

// add Orbit slider support to NextGEN Gallery

add_filter('ngg_render_template', 'ngg_orbit_template', 10, 2);

function ngg_orbit_template( $path, $template_name = false) {
	if ($template_name == 'gallery-orbit')
		$path = dirname( __FILE__ ) . '/gallery-orbit.php';
	return $path;
}



// Adds flex-video class to oEmbed videos so they are responsive
// except for non-video embeds (as suggested by http://www.t-gk.net/2013/04/foundation-and-wordpress-embeds/ )

add_filter('embed_oembed_html', 'krt_oembed_filter', 90, 3 );

function krt_oembed_filter( $html, $url, $attr ) {

    if (!defined('FLEXVIDEO_ASPECT') ) define('FLEXVIDEO_ASPECT', 'widescreen');

    $provider = wp_oembed_get( $url, $attr );

    if ( strpos($provider, 'twitter.com') !== false ||
         strpos($provider, 'instagram.com') !== false ||
         strpos($provider, 'photobucket.com') !== false ||
         strpos($provider, 'polldaddy.com') !== false ||
         strpos($provider, 'scribd.com') !== false ||
         strpos($provider, 'smugmug.com') !== false
    ) return $html;



    if ( strpos($provider, 'vimeo.com') !== false )
        return '<div class="flex-video vimeo ' . FLEXVIDEO_ASPECT . '">'. $html .'</div>';

    return '<div class="flex-video ' . FLEXVIDEO_ASPECT . '">'. $html .'</div>';

}