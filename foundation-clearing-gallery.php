<?php

if ( ! defined( 'ABSPATH' ) ) exit;

// Replace WP standard gallery shortcode with Foundation's block grid + Clearing lightbox

remove_shortcode('gallery', 'gallery_shortcode');
add_shortcode('gallery', 'gallery_shortcode_clearing');

// Following is from /wp-includes/media.php, adjusted for Clearing syntax

/**
 * The Gallery shortcode.
 *
 * This implements the functionality of the Gallery Shortcode for displaying
 * WordPress images on a post.
 *
 * @since 2.5.0
 *
 * @param array $attr {
 *     Attributes of the gallery shortcode.
 *
 *     @type string $order      Order of the images in the gallery. Default 'ASC'. Accepts 'ASC', 'DESC'.
 *     @type string $orderby    The field to use when ordering the images. Default 'menu_order ID'.
 *                              Accepts any valid SQL ORDERBY statement.
 *     @type int    $id         Post ID.
 *     @type string $itemtag    HTML tag to use for each image in the gallery.
 *                              Default 'dl', or 'figure' when the theme registers HTML5 gallery support.
 *     @type string $icontag    HTML tag to use for each image's icon.
 *                              Default 'dt', or 'div' when the theme registers HTML5 gallery support.
 *     @type string $captiontag HTML tag to use for each image's caption.
 *                              Default 'dd', or 'figcaption' when the theme registers HTML5 gallery support.
 *     @type int    $columns    Number of columns of images to display. Default 3.
 *     @type string $size       Size of the images to display. Default 'thumbnail'.
 *     @type string $ids        A comma-separated list of IDs of attachments to display. Default empty.
 *     @type string $include    A comma-separated list of IDs of attachments to include. Default empty.
 *     @type string $exclude    A comma-separated list of IDs of attachments to exclude. Default empty.
 *     @type string $link       What to link each image to. Default empty (links to the attachment page).
 *                              Accepts 'file', 'none'.
 * }
 * @return string HTML content to display gallery.
 */
function gallery_shortcode_clearing( $attr ) {
	$post = get_post();

	static $instance = 0;
	$instance++;

	if ( ! empty( $attr['ids'] ) ) {
		// 'ids' is explicitly ordered, unless you specify otherwise.
		if ( empty( $attr['orderby'] ) ) {
			$attr['orderby'] = 'post__in';
		}
		$attr['include'] = $attr['ids'];
	}

	/**
	 * Filter the default gallery shortcode output.
	 *
	 * If the filtered output isn't empty, it will be used instead of generating
	 * the default gallery template.
	 *
	 * @since 2.5.0
	 *
	 * @see gallery_shortcode()
	 *
	 * @param string $output The gallery output. Default empty.
	 * @param array  $attr   Attributes of the gallery shortcode.
	 */
	$output = apply_filters( 'post_gallery', '', $attr );
	if ( $output != '' ) {
		return $output;
	}

	$atts = shortcode_atts( array(
		'order'      => 'ASC',
		'orderby'    => 'menu_order ID',
		'id'         => $post ? $post->ID : 0,
		'itemtag'    => 'li',
		'icontag'    => 'img',
		'captiontag' => 'figcaption',
		'columns'    => 3,
		'size'       => 'thumbnail',
		'include'    => '',
		'exclude'    => '',
		'link'       => ''
	), $attr, 'gallery' );

	$id = intval( $atts['id'] );

	if ( ! empty( $atts['include'] ) ) {
		$_attachments = get_posts( array( 'include' => $atts['include'], 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $atts['order'], 'orderby' => $atts['orderby'] ) );

		$attachments = array();
		foreach ( $_attachments as $key => $val ) {
			$attachments[$val->ID] = $_attachments[$key];
		}
	} elseif ( ! empty( $atts['exclude'] ) ) {
		$attachments = get_children( array( 'post_parent' => $id, 'exclude' => $atts['exclude'], 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $atts['order'], 'orderby' => $atts['orderby'] ) );
	} else {
		$attachments = get_children( array( 'post_parent' => $id, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $atts['order'], 'orderby' => $atts['orderby'] ) );
	}

	if ( empty( $attachments ) ) {
		return '';
	}

	if ( is_feed() ) {
		$output = "\n";
		foreach ( $attachments as $att_id => $attachment ) {
			$output .= wp_get_attachment_link( $att_id, $atts['size'], true ) . "\n";
		}
		return $output;
	}

	$itemtag = tag_escape( $atts['itemtag'] );
	$captiontag = tag_escape( $atts['captiontag'] );
	$icontag = tag_escape( $atts['icontag'] );
	$valid_tags = wp_kses_allowed_html( 'post' );
	if ( ! isset( $valid_tags[ $itemtag ] ) ) {
		$itemtag = 'li';
	}
	if ( ! isset( $valid_tags[ $captiontag ] ) ) {
		$captiontag = 'figcaption';
	}
	if ( ! isset( $valid_tags[ $icontag ] ) ) {
		$icontag = 'img';
	}

	$columns = intval( $atts['columns'] );
	$itemwidth = $columns > 0 ? floor(100/$columns) : 100;
	$float = is_rtl() ? 'right' : 'left';

	$selector = "gallery-{$instance}";

	$size_class = sanitize_html_class( $atts['size'] );
	$output = "<ul id='$selector' class='clearing-thumbs small-block-grid-{$columns} gallery galleryid-{$id} gallery-size-{$size_class}' data-clearing>";

	$i = 0;
	foreach ( $attachments as $id => $attachment ) {

        // Clearing syntax:
        // <ul class="clearing-thumbs" data-clearing>
        // <li><a href="path/to/your/img"><img data-caption="caption here..." src="path/to/your/img-th"></a></li>
        // </ul>

		$orientation = '';
		if ( isset( $image_meta['height'], $image_meta['width'] ) ) {
			$orientation = ( $image_meta['height'] > $image_meta['width'] ) ? 'portrait' : 'landscape';
		}

		$attr = ( trim( $attachment->post_excerpt ) ) ? array( 'aria-describedby' => "$selector-$id" ) : array();
		$attr['class'] = "gallery-icon {$orientation}";
		if ( $captiontag && trim($attachment->post_excerpt) ) {
			$attr['data-caption'] = wptexturize($attachment->post_excerpt);
        }


        // always link images to fullres files

		$image_output = wp_get_attachment_link( $id, $atts['size'], false, false, false, $attr );

		$image_meta  = wp_get_attachment_metadata( $id );

		$output .= "<{$itemtag} class='gallery-item'>";
		$output .= $image_output;
		if ( $captiontag && trim($attachment->post_excerpt) ) {
        	$output .= "
    		<{$captiontag} class='wp-caption-text gallery-caption' id='$selector-$id'>
    		" . wptexturize($attachment->post_excerpt) . "
    		</{$captiontag}>";
		}
		$output .= "</{$itemtag}>";
	}

	$output .= "
		</ul>\n";

	return $output;
}
