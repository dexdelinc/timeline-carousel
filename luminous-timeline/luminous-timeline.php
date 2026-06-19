<?php
/**
 * Plugin Name:       Luminous Timeline
 * Plugin URI:        https://example.com/luminous-timeline
 * Description:        An interactive process timeline with a two-image crossfade carousel. Manage steps, images and styling from the admin, then place it anywhere with the [luminous_timeline] shortcode.
 * Version:           1.4.0
 * Requires at least: 5.5
 * Requires PHP:      7.2
 * Author:            Luminous
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       luminous-timeline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

define( 'LLE_VERSION', '1.4.0' );
define( 'LLE_OPTION', 'luminous_timeline_settings' );

/* -------------------------------------------------------------------------
 * Defaults
 * ---------------------------------------------------------------------- */
function lle_default_steps() {
	return array(
		array(
			'heading' => 'Discovery & Consultation',
			'desc'    => "We begin by understanding your home, your goals, and how you want your outdoor spaces to feel after dark—balancing aesthetics, functionality, and long-term vision.",
			'image'   => '',
		),
		array(
			'heading' => 'Custom Lighting Design',
			'desc'    => "We translate your vision into a tailored lighting plan—mapping fixtures, beam angles, and layers of light to highlight architecture, trees, and pathways with intention.",
			'image'   => '',
		),
		array(
			'heading' => 'Precision Installation',
			'desc'    => "Our technicians install every fixture with care—running concealed wiring, setting clean lines, and protecting your landscape so the system disappears by day and shines at night.",
			'image'   => '',
		),
		array(
			'heading' => 'Night time Fine-Tuning',
			'desc'    => "Once the sun sets, we walk the property with you—aiming, dimming, and adjusting each fixture in real conditions until every glow lands exactly where it should.",
			'image'   => '',
		),
		array(
			'heading' => 'Continued Care',
			'desc'    => "Seasons change and gardens grow. We return for ongoing maintenance, adjustments, and upgrades—keeping your lighting flawless year after year.",
			'image'   => '',
		),
	);
}

function lle_defaults() {
	return array(
		'title'      => 'The Luminous Lighting Experience',
		'intro'      => "Exceptional outdoor lighting should feel effortless. Our process is designed to be clear, collaborative, and thoughtfully executed—delivering a refined result from the first conversation to the final nighttime adjustment.",
		'active_w'   => 70,   // %
		'inactive_w' => 30,   // %
		'gap'        => 28,   // px
		'height'     => 420,  // px
		'dwell'      => 5,    // seconds on each step
		'autoplay'   => 1,    // 1 = auto-rotate, 0 = manual only
		'transition' => 'slide', // 'slide' or 'fade'
		'gold'       => '#c2a15c',
		'ink'        => '#1c2a39',
		'bg'         => '#f3f4f6',
		'steps'      => lle_default_steps(),
	);
}

function lle_get() {
	$saved = get_option( LLE_OPTION, array() );
	if ( ! is_array( $saved ) ) {
		$saved = array();
	}
	$o = array_merge( lle_defaults(), $saved );
	if ( empty( $o['steps'] ) || ! is_array( $o['steps'] ) ) {
		$o['steps'] = lle_default_steps();
	}
	return $o;
}

register_activation_hook( __FILE__, function () {
	if ( false === get_option( LLE_OPTION, false ) ) {
		add_option( LLE_OPTION, lle_defaults() );
	}
} );

/* -------------------------------------------------------------------------
 * Admin menu + assets
 * ---------------------------------------------------------------------- */
add_action( 'admin_menu', function () {
	add_menu_page(
		__( 'Luminous Timeline', 'luminous-timeline' ),
		__( 'Luminous Timeline', 'luminous-timeline' ),
		'manage_options',
		'luminous-timeline',
		'lle_render_admin_page',
		'dashicons-images-alt2',
		58
	);
} );

add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( 'toplevel_page_luminous-timeline' !== $hook ) {
		return;
	}
	wp_enqueue_media(); // Media Library picker.
} );

/* -------------------------------------------------------------------------
 * Save handler
 * ---------------------------------------------------------------------- */
function lle_handle_save() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return false;
	}
	if ( ! isset( $_POST['lle_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lle_nonce'] ) ), 'lle_save' ) ) {
		return false;
	}

	$in = wp_unslash( $_POST ); // Slashes added by WP; we sanitize below.
	$o  = lle_defaults();

	$o['title'] = isset( $in['title'] ) ? sanitize_text_field( $in['title'] ) : '';
	$o['intro'] = isset( $in['intro'] ) ? sanitize_textarea_field( $in['intro'] ) : '';

	$o['active_w']   = max( 10, min( 90, intval( $in['active_w'] ?? 70 ) ) );
	$o['inactive_w'] = max( 10, min( 90, intval( $in['inactive_w'] ?? 30 ) ) );
	$o['gap']        = max( 0, min( 120, intval( $in['gap'] ?? 28 ) ) );
	$o['height']     = max( 160, min( 900, intval( $in['height'] ?? 420 ) ) );
	$o['dwell']      = max( 1, min( 60, intval( $in['dwell'] ?? 5 ) ) );
	$o['autoplay']   = empty( $in['autoplay'] ) ? 0 : 1;
	$o['transition'] = ( isset( $in['transition'] ) && 'fade' === $in['transition'] ) ? 'fade' : 'slide';

	$o['gold'] = lle_build_color( sanitize_hex_color( $in['gold'] ?? '' ) ? $in['gold'] : '#c2a15c', isset( $in['gold_a'] ) ? intval( $in['gold_a'] ) : 100 );
	$o['ink']  = lle_build_color( sanitize_hex_color( $in['ink'] ?? '' ) ? $in['ink'] : '#1c2a39', isset( $in['ink_a'] ) ? intval( $in['ink_a'] ) : 100 );
	$o['bg']   = lle_build_color( sanitize_hex_color( $in['bg'] ?? '' ) ? $in['bg'] : '#f3f4f6', isset( $in['bg_a'] ) ? intval( $in['bg_a'] ) : 100 );

	$steps = array();
	if ( ! empty( $in['steps'] ) && is_array( $in['steps'] ) ) {
		foreach ( $in['steps'] as $row ) {
			$heading = isset( $row['heading'] ) ? sanitize_text_field( $row['heading'] ) : '';
			$desc    = isset( $row['desc'] ) ? sanitize_textarea_field( $row['desc'] ) : '';
			$image   = isset( $row['image'] ) ? esc_url_raw( trim( $row['image'] ) ) : '';
			if ( '' === $heading && '' === $desc && '' === $image ) {
				continue; // Skip empty rows.
			}
			$steps[] = array(
				'heading' => $heading,
				'desc'    => $desc,
				'image'   => $image,
			);
		}
	}
	if ( empty( $steps ) ) {
		$steps = lle_default_steps();
	}
	$o['steps'] = array_values( $steps );

	update_option( LLE_OPTION, $o );
	return true;
}

/* -------------------------------------------------------------------------
 * Admin page
 * ---------------------------------------------------------------------- */
function lle_render_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$saved_ok = lle_handle_save();
	$o        = lle_get();
	?>
	<div class="wrap lle-admin">
		<h1><?php esc_html_e( 'Luminous Timeline', 'luminous-timeline' ); ?></h1>

		<?php if ( $saved_ok ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved.', 'luminous-timeline' ); ?></p></div>
		<?php endif; ?>

		<p class="lle-shortcode-hint">
			<?php esc_html_e( 'Place this anywhere (Avada Code Block, Text element, Gutenberg, widgets):', 'luminous-timeline' ); ?>
			<code>[luminous_timeline]</code>
		</p>

		<form method="post" action="">
			<?php wp_nonce_field( 'lle_save', 'lle_nonce' ); ?>

			<h2 class="title"><?php esc_html_e( 'Heading', 'luminous-timeline' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="lle-title"><?php esc_html_e( 'Section title', 'luminous-timeline' ); ?></label></th>
					<td><input name="title" id="lle-title" type="text" class="regular-text" value="<?php echo esc_attr( $o['title'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="lle-intro"><?php esc_html_e( 'Intro paragraph', 'luminous-timeline' ); ?></label></th>
					<td><textarea name="intro" id="lle-intro" rows="3" class="large-text"><?php echo esc_textarea( $o['intro'] ); ?></textarea></td>
				</tr>
			</table>

			<h2 class="title"><?php esc_html_e( 'Steps', 'luminous-timeline' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Each step shows as a large active image; the next step shows as the smaller image. Use the arrows to reorder.', 'luminous-timeline' ); ?></p>

			<div id="lle-steps">
				<?php foreach ( $o['steps'] as $i => $s ) : ?>
					<?php lle_render_step_row( $i, $s ); ?>
				<?php endforeach; ?>
			</div>

			<p><button type="button" class="button button-secondary" id="lle-add"><?php esc_html_e( '+ Add step', 'luminous-timeline' ); ?></button></p>

			<h2 class="title"><?php esc_html_e( 'Layout & style', 'luminous-timeline' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Active image width', 'luminous-timeline' ); ?></th>
					<td><input name="active_w" type="number" min="10" max="90" value="<?php echo esc_attr( $o['active_w'] ); ?>" class="small-text"> %</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Inactive image width', 'luminous-timeline' ); ?></th>
					<td><input name="inactive_w" type="number" min="10" max="90" value="<?php echo esc_attr( $o['inactive_w'] ); ?>" class="small-text"> %
						<p class="description"><?php esc_html_e( 'Tip: keep the two widths adding up to about 100.', 'luminous-timeline' ); ?></p></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Gap between images', 'luminous-timeline' ); ?></th>
					<td><input name="gap" type="number" min="0" max="120" value="<?php echo esc_attr( $o['gap'] ); ?>" class="small-text"> px</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Image height', 'luminous-timeline' ); ?></th>
					<td><input name="height" type="number" min="160" max="900" value="<?php echo esc_attr( $o['height'] ); ?>" class="small-text"> px</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Transition', 'luminous-timeline' ); ?></th>
					<td>
						<select name="transition">
							<option value="slide" <?php selected( 'slide', $o['transition'] ); ?>><?php esc_html_e( 'Slide', 'luminous-timeline' ); ?></option>
							<option value="fade" <?php selected( 'fade', $o['transition'] ); ?>><?php esc_html_e( 'Fade (crossfade)', 'luminous-timeline' ); ?></option>
						</select>
						<p class="description"><?php esc_html_e( 'How the images change between steps.', 'luminous-timeline' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Auto-rotate', 'luminous-timeline' ); ?></th>
					<td>
						<label><input name="autoplay" type="checkbox" value="1" <?php checked( ! empty( $o['autoplay'] ) ); ?>> <?php esc_html_e( 'Automatically advance through the steps', 'luminous-timeline' ); ?></label>
						<p class="description"><?php esc_html_e( 'Uncheck to make it manual only — visitors click a step (or the small image) to change it.', 'luminous-timeline' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Auto-advance speed', 'luminous-timeline' ); ?></th>
					<td><input name="dwell" type="number" min="1" max="60" value="<?php echo esc_attr( $o['dwell'] ); ?>" class="small-text"> <?php esc_html_e( 'seconds per step (only used when auto-rotate is on)', 'luminous-timeline' ); ?></td>
				</tr>
				<?php lle_render_color_row( __( 'Accent colour', 'luminous-timeline' ), 'gold', $o['gold'], __( 'active step number & progress line', 'luminous-timeline' ) ); ?>
				<?php lle_render_color_row( __( 'Heading colour', 'luminous-timeline' ), 'ink', $o['ink'] ); ?>
				<?php lle_render_color_row( __( 'Background colour', 'luminous-timeline' ), 'bg', $o['bg'], __( 'lower the opacity for a see-through background', 'luminous-timeline' ) ); ?>
			</table>

			<?php submit_button( __( 'Save changes', 'luminous-timeline' ) ); ?>
		</form>
	</div>

	<?php // Template for new rows (index placeholder __i__). ?>
	<script type="text/html" id="lle-row-tpl">
		<?php lle_render_step_row( '__i__', array( 'heading' => '', 'desc' => '', 'image' => '' ) ); ?>
	</script>

	<style>
		.lle-admin .lle-shortcode-hint{background:#fff;border:1px solid #dcdcde;border-left:4px solid #c2a15c;padding:10px 14px;display:inline-block;border-radius:4px;}
		.lle-admin .lle-shortcode-hint code{font-size:13px;background:#f6f7f7;padding:2px 6px;border-radius:3px;}
		.lle-row{display:flex;gap:16px;align-items:flex-start;background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:16px;margin:0 0 12px;max-width:900px;}
		.lle-row-order{display:flex;flex-direction:column;gap:4px;padding-top:2px;}
		.lle-row-order button{width:28px;height:24px;cursor:pointer;}
		.lle-row-main{flex:1;min-width:0;}
		.lle-row-main input.lle-heading-in{width:100%;font-weight:600;margin:0 0 8px;}
		.lle-row-main textarea{width:100%;}
		.lle-img{flex:0 0 150px;text-align:center;}
		.lle-img-preview{width:150px;height:90px;border:1px dashed #c3c4c7;border-radius:6px;background:#f0f0f1 center/cover no-repeat;margin:0 0 8px;}
		.lle-img .button{width:100%;margin:0 0 4px;}
		.lle-del{color:#b32d2e;cursor:pointer;background:none;border:0;padding:6px 0 0;}
		.lle-del:hover{color:#8a2424;}
		.lle-cswatch{display:inline-block;width:34px;height:34px;border-radius:6px;border:1px solid #c3c4c7;vertical-align:middle;margin-right:8px;background-image:linear-gradient(45deg,#ccc 25%,transparent 25%),linear-gradient(-45deg,#ccc 25%,transparent 25%),linear-gradient(45deg,transparent 75%,#ccc 75%),linear-gradient(-45deg,transparent 75%,#ccc 75%);background-size:10px 10px;background-position:0 0,0 5px,5px -5px,-5px 0;}
		.lle-chex{vertical-align:middle;width:48px;height:34px;padding:2px;}
		.lle-alpha-wrap{display:inline-flex;align-items:center;gap:8px;margin-left:14px;font-size:13px;color:#50575e;vertical-align:middle;}
		.lle-calpha{vertical-align:middle;}
		.lle-cval{min-width:38px;display:inline-block;}
	</style>

	<script>
	( function () {
		var list = document.getElementById( 'lle-steps' );
		var tpl  = document.getElementById( 'lle-row-tpl' ).innerHTML;
		var idx  = <?php echo (int) count( $o['steps'] ); ?>;

		document.getElementById( 'lle-add' ).addEventListener( 'click', function () {
			var html = tpl.replace( /__i__/g, idx++ );
			var temp = document.createElement( 'div' );
			temp.innerHTML = html.trim();
			list.appendChild( temp.firstElementChild );
		} );

		list.addEventListener( 'click', function ( e ) {
			var t = e.target;
			var row = t.closest ? t.closest( '.lle-row' ) : null;
			if ( ! row ) { return; }

			if ( t.classList.contains( 'lle-del' ) ) {
				row.parentNode.removeChild( row );
				return;
			}
			if ( t.classList.contains( 'lle-up' ) ) {
				var prev = row.previousElementSibling;
				if ( prev ) { list.insertBefore( row, prev ); }
				return;
			}
			if ( t.classList.contains( 'lle-down' ) ) {
				var next = row.nextElementSibling;
				if ( next ) { list.insertBefore( next, row ); }
				return;
			}
			if ( t.classList.contains( 'lle-clear' ) ) {
				row.querySelector( '.lle-img-url' ).value = '';
				row.querySelector( '.lle-img-preview' ).style.backgroundImage = '';
				return;
			}
			if ( t.classList.contains( 'lle-pick' ) ) {
				e.preventDefault();
				var frame = wp.media( { title: 'Select image', button: { text: 'Use image' }, multiple: false, library: { type: 'image' } } );
				frame.on( 'select', function () {
					var a = frame.state().get( 'selection' ).first().toJSON();
					var url = ( a.sizes && a.sizes.large ) ? a.sizes.large.url : a.url;
					row.querySelector( '.lle-img-url' ).value = url;
					row.querySelector( '.lle-img-preview' ).style.backgroundImage = "url('" + url + "')";
				} );
				frame.open();
			}
		} );
	} )();
	</script>

	<script>
	( function () {
		function rgba( hex, a ) {
			hex = hex.replace( '#', '' );
			if ( hex.length === 3 ) { hex = hex[0]+hex[0]+hex[1]+hex[1]+hex[2]+hex[2]; }
			var r = parseInt( hex.substr(0,2), 16 ), g = parseInt( hex.substr(2,2), 16 ), b = parseInt( hex.substr(4,2), 16 );
			return 'rgba(' + r + ',' + g + ',' + b + ',' + ( a / 100 ) + ')';
		}
		function sync( name ) {
			var hexEl = document.querySelector( '.lle-chex[data-name="' + name + '"]' );
			var aEl   = document.querySelector( '.lle-calpha[data-name="' + name + '"]' );
			if ( ! hexEl || ! aEl ) { return; }
			var a = parseInt( aEl.value, 10 );
			var sw = document.querySelector( '.lle-cswatch[data-for="' + name + '"]' );
			if ( sw ) { sw.style.background = rgba( hexEl.value, a ); }
			var val = aEl.parentNode.querySelector( '.lle-cval' );
			if ( val ) { val.textContent = a + '%'; }
		}
		document.querySelectorAll( '.lle-chex, .lle-calpha' ).forEach( function ( el ) {
			el.addEventListener( 'input', function () { sync( el.getAttribute( 'data-name' ) ); } );
			sync( el.getAttribute( 'data-name' ) );
		} );
	} )();
	</script>
	<?php
}

/**
 * Render a single repeater row in the admin.
 *
 * @param int|string $i Index (or "__i__" placeholder for the JS template).
 * @param array      $s Step data.
 */
function lle_render_step_row( $i, $s ) {
	$heading = isset( $s['heading'] ) ? $s['heading'] : '';
	$desc    = isset( $s['desc'] ) ? $s['desc'] : '';
	$image   = isset( $s['image'] ) ? $s['image'] : '';
	$preview = $image ? "background-image:url('" . esc_url( $image ) . "');" : '';
	?>
	<div class="lle-row">
		<div class="lle-row-order">
			<button type="button" class="button lle-up" title="Move up">&#9650;</button>
			<button type="button" class="button lle-down" title="Move down">&#9660;</button>
		</div>
		<div class="lle-row-main">
			<input type="text" class="lle-heading-in" name="steps[<?php echo esc_attr( $i ); ?>][heading]" value="<?php echo esc_attr( $heading ); ?>" placeholder="<?php esc_attr_e( 'Step heading', 'luminous-timeline' ); ?>">
			<textarea rows="3" name="steps[<?php echo esc_attr( $i ); ?>][desc]" placeholder="<?php esc_attr_e( 'Short description', 'luminous-timeline' ); ?>"><?php echo esc_textarea( $desc ); ?></textarea>
			<button type="button" class="lle-del"><?php esc_html_e( 'Remove step', 'luminous-timeline' ); ?></button>
		</div>
		<div class="lle-img">
			<div class="lle-img-preview" style="<?php echo esc_attr( $preview ); ?>"></div>
			<input type="hidden" class="lle-img-url" name="steps[<?php echo esc_attr( $i ); ?>][image]" value="<?php echo esc_attr( $image ); ?>">
			<button type="button" class="button lle-pick"><?php esc_html_e( 'Select image', 'luminous-timeline' ); ?></button>
			<button type="button" class="button-link lle-clear"><?php esc_html_e( 'Clear', 'luminous-timeline' ); ?></button>
		</div>
	</div>
	<?php
}

/* -------------------------------------------------------------------------
 * Colour helpers (support hex + rgba with opacity)
 * ---------------------------------------------------------------------- */

/**
 * Split a stored colour into a hex value + opacity percent (for the form fields).
 *
 * @param string $color Stored colour (hex, #rrggbbaa, rgb() or rgba()).
 * @return array{hex:string,alpha:int}
 */
function lle_split_color( $color ) {
	$color = trim( (string) $color );
	$hex   = '#000000';
	$alpha = 100;

	if ( preg_match( '/^#([0-9a-f]{6})$/i', $color, $m ) ) {
		$hex = '#' . strtolower( $m[1] );
	} elseif ( preg_match( '/^#([0-9a-f]{3})$/i', $color, $m ) ) {
		$h   = strtolower( $m[1] );
		$hex = '#' . $h[0] . $h[0] . $h[1] . $h[1] . $h[2] . $h[2];
	} elseif ( preg_match( '/^#([0-9a-f]{8})$/i', $color, $m ) ) {
		$hex   = '#' . strtolower( substr( $m[1], 0, 6 ) );
		$alpha = (int) round( hexdec( substr( $m[1], 6, 2 ) ) / 255 * 100 );
	} elseif ( preg_match( '/^rgba?\(\s*([\d.]+)\s*,\s*([\d.]+)\s*,\s*([\d.]+)\s*(?:,\s*([\d.]+)\s*)?\)$/i', $color, $m ) ) {
		$hex   = sprintf( '#%02x%02x%02x', min( 255, (int) $m[1] ), min( 255, (int) $m[2] ), min( 255, (int) $m[3] ) );
		$alpha = ( isset( $m[4] ) && '' !== $m[4] ) ? (int) round( ( (float) $m[4] ) * 100 ) : 100;
	}

	return array(
		'hex'   => $hex,
		'alpha' => max( 0, min( 100, $alpha ) ),
	);
}

/**
 * Build a clean stored colour from a hex value + opacity percent.
 * Returns hex when fully opaque, otherwise an rgba() string. Rebuilding from
 * parsed numbers prevents any CSS injection through the colour fields.
 *
 * @param string $hex      Hex colour (#rgb or #rrggbb).
 * @param int    $alpha_pc Opacity 0-100.
 * @return string
 */
function lle_build_color( $hex, $alpha_pc ) {
	$hex = ltrim( trim( (string) $hex ), '#' );
	if ( 3 === strlen( $hex ) ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}
	if ( ! preg_match( '/^[0-9a-fA-F]{6}$/', $hex ) ) {
		$hex = 'c2a15c';
	}
	$alpha_pc = max( 0, min( 100, (int) $alpha_pc ) );

	if ( 100 === $alpha_pc ) {
		return '#' . strtolower( $hex );
	}
	$r = hexdec( substr( $hex, 0, 2 ) );
	$g = hexdec( substr( $hex, 2, 2 ) );
	$b = hexdec( substr( $hex, 4, 2 ) );
	$a = rtrim( rtrim( sprintf( '%.2f', $alpha_pc / 100 ), '0' ), '.' );
	if ( '' === $a ) {
		$a = '0';
	}
	return "rgba($r,$g,$b,$a)";
}

/**
 * Render a colour picker + opacity slider row in the admin.
 *
 * @param string $label Field label.
 * @param string $name  Field name (also used for the "{name}_a" opacity field).
 * @param string $color Current stored colour.
 * @param string $desc  Optional description.
 */
function lle_render_color_row( $label, $name, $color, $desc = '' ) {
	$c = lle_split_color( $color );
	?>
	<tr>
		<th scope="row"><?php echo esc_html( $label ); ?></th>
		<td>
			<span class="lle-cswatch" data-for="<?php echo esc_attr( $name ); ?>" style="background:<?php echo esc_attr( $color ); ?>"></span>
			<input type="color" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $c['hex'] ); ?>" class="lle-chex" data-name="<?php echo esc_attr( $name ); ?>">
			<span class="lle-alpha-wrap"><?php esc_html_e( 'Opacity', 'luminous-timeline' ); ?>
				<input type="range" min="0" max="100" value="<?php echo (int) $c['alpha']; ?>" name="<?php echo esc_attr( $name ); ?>_a" class="lle-calpha" data-name="<?php echo esc_attr( $name ); ?>">
				<span class="lle-cval"><?php echo (int) $c['alpha']; ?>%</span>
			</span>
			<?php if ( $desc ) : ?><p class="description"><?php echo esc_html( $desc ); ?></p><?php endif; ?>
		</td>
	</tr>
	<?php
}

/* -------------------------------------------------------------------------
 * Shortcode + frontend
 * ---------------------------------------------------------------------- */
add_shortcode( 'luminous_timeline', 'lle_shortcode' );

function lle_shortcode( $atts ) {
	$o     = lle_get();
	$steps = $o['steps'];
	$n     = count( $steps );
	if ( $n < 1 ) {
		return '';
	}

	static $instance = 0;
	$instance++;
	$uid = 'lle' . $instance;

	$images = array();
	foreach ( $steps as $s ) {
		$images[] = isset( $s['image'] ) ? $s['image'] : '';
	}

	$style = sprintf(
		'--active-w:%d%%;--inactive-w:%d%%;--gap:%dpx;--img-h:%dpx;--lle-dwell:%ds;--lle-gold:%s;--lle-ink:%s;--lle-bg:%s;',
		(int) $o['active_w'],
		(int) $o['inactive_w'],
		(int) $o['gap'],
		(int) $o['height'],
		(int) $o['dwell'],
		$o['gold'],
		$o['ink'],
		$o['bg']
	);

	$fx = ( 'fade' === $o['transition'] ) ? 'fade' : 'slide';

	ob_start();
	echo lle_assets_once(); // CSS + JS + fonts (printed only once per page).
	?>
	<section class="lle-section lle--<?php echo esc_attr( $fx ); ?>" data-lle data-autoplay="<?php echo (int) $o['autoplay']; ?>" style="<?php echo esc_attr( $style ); ?>" aria-label="<?php echo esc_attr( $o['title'] ); ?>">
		<div class="lle-inner">

			<div class="lle-head">
				<?php if ( '' !== trim( (string) $o['title'] ) ) : ?>
					<h2 class="lle-title"><?php echo esc_html( $o['title'] ); ?></h2>
				<?php endif; ?>
				<?php if ( '' !== trim( (string) $o['intro'] ) ) : ?>
					<p class="lle-intro"><?php echo nl2br( esc_html( $o['intro'] ) ); ?></p>
				<?php endif; ?>
			</div>

			<ol class="lle-steps" role="tablist" aria-label="<?php esc_attr_e( 'Process steps', 'luminous-timeline' ); ?>" aria-orientation="vertical">
				<?php foreach ( $steps as $i => $s ) : ?>
					<li class="lle-step<?php echo 0 === $i ? ' is-active' : ''; ?>" data-step>
						<button class="lle-step-btn" role="tab" id="<?php echo esc_attr( $uid . '-tab-' . $i ); ?>" aria-selected="<?php echo 0 === $i ? 'true' : 'false'; ?>" tabindex="<?php echo 0 === $i ? '0' : '-1'; ?>">
							<span class="lle-num"><?php echo (int) ( $i + 1 ); ?></span>
							<span class="lle-heading"><?php echo esc_html( $s['heading'] ); ?></span>
						</button>
						<div class="lle-panel">
							<span class="lle-track"><span class="lle-progress"></span></span>
							<p class="lle-desc"><?php echo nl2br( esc_html( $s['desc'] ) ); ?></p>
						</div>
					</li>
				<?php endforeach; ?>
			</ol>

			<div class="lle-right">
				<div class="lle-stage">
					<div class="lle-belt">
						<?php
						for ( $k = 0; $k < $n; $k++ ) :
							$big   = $images[ $k ];
							$small = $images[ ( $k + 1 ) % $n ];
							?>
							<div class="lle-frame<?php echo 0 === $k ? ' is-shown' : ''; ?>" aria-hidden="true">
								<div class="lle-cell lle-cell--big"<?php echo $big ? ' style="background-image:url(\'' . esc_url( $big ) . '\')"' : ''; ?>></div>
								<div class="lle-cell lle-cell--small"<?php echo $small ? ' style="background-image:url(\'' . esc_url( $small ) . '\')"' : ''; ?>></div>
							</div>
						<?php endfor; ?>
					</div>
				</div>
			</div>

		</div>
	</section>
	<?php
	return ob_get_clean();
}

/**
 * Print the structural CSS, JS and fonts exactly once per page.
 */
function lle_assets_once() {
	static $done = false;
	if ( $done ) {
		return '';
	}
	$done = true;

	$css = lle_css();
	$js  = lle_js();

	$out  = '<link rel="preconnect" href="https://fonts.googleapis.com">';
	$out .= '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
	$out .= '<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">';
	$out .= "<style id='lle-css'>{$css}</style>";
	$out .= "<script id='lle-js'>{$js}</script>";
	return $out;
}

function lle_css() {
	return <<<'CSS'
.lle-section{
  --lle-bg:#f3f4f6;--lle-ink:#1c2a39;--lle-ink-soft:#5b6573;--lle-muted:#aeb4bc;
  --lle-gold:#c2a15c;--lle-circle-off:#e5e8eb;--lle-circle-off-ink:#b2b8c0;--lle-line:#e2e5e8;
  --lle-dwell:5s;--active-w:70%;--inactive-w:30%;--gap:28px;--img-h:420px;
  --lle-display:'Poppins',system-ui,Segoe UI,Roboto,Arial,sans-serif;
  --lle-body:'Inter',system-ui,Segoe UI,Roboto,Arial,sans-serif;
  background:var(--lle-bg);font-family:var(--lle-body);color:var(--lle-ink-soft);
  padding:64px 24px;-webkit-font-smoothing:antialiased;
}
.lle-section *,.lle-section *::before,.lle-section *::after{box-sizing:border-box;}
.lle-section h2,.lle-section p,.lle-section ol,.lle-section li{margin:0;padding:0;}
.lle-section ol{list-style:none;}
.lle-inner{
  max-width:1180px;margin:0 auto;display:grid;gap:40px 48px;
  grid-template-columns:minmax(240px,1fr) minmax(0,1.7fr);
  grid-template-areas:"head stage" "steps stage";align-items:start;
  animation:lle-rise .6s ease both;
}
@keyframes lle-rise{from{opacity:0;transform:translateY(14px);}to{opacity:1;transform:none;}}
.lle-head{grid-area:head;min-width:0;}
.lle-steps{grid-area:steps;min-width:0;margin-top:6px;}
.lle-right{grid-area:stage;align-self:start;min-width:0;}
.lle-title{font-family:var(--lle-display);color:var(--lle-ink);font-weight:700;font-size:clamp(26px,2.4vw,34px);line-height:1.15;letter-spacing:-.01em;margin-bottom:14px;}
.lle-intro{font-size:14px;line-height:1.65;max-width:42ch;}
.lle-step{position:relative;}
.lle-step-btn{display:flex;align-items:center;gap:14px;width:100%;background:none;border:0;cursor:pointer;padding:11px 0;text-align:left;font-family:inherit;color:inherit;border-radius:8px;}
.lle-step-btn:focus-visible{outline:2px solid var(--lle-gold);outline-offset:3px;}
.lle-num{flex:0 0 auto;width:30px;height:30px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-family:var(--lle-display);font-weight:600;font-size:13px;background:var(--lle-circle-off);color:var(--lle-circle-off-ink);transition:background .35s ease,color .35s ease,box-shadow .35s ease;}
.lle-heading{font-family:var(--lle-display);font-weight:600;font-size:15.5px;color:var(--lle-muted);letter-spacing:.005em;transition:color .35s ease;}
.lle-step.is-active .lle-num{background:var(--lle-gold);color:#fff;box-shadow:0 6px 16px -6px rgba(194,161,92,.8);}
.lle-step.is-active .lle-heading{color:var(--lle-ink);}
.lle-panel{position:relative;padding-left:44px;max-height:0;opacity:0;overflow:hidden;transition:max-height .45s ease,opacity .35s ease,margin .45s ease;}
.lle-step.is-active .lle-panel{max-height:280px;opacity:1;margin:2px 0 6px;}
.lle-track{position:absolute;left:14px;top:0;bottom:16px;width:2px;background:var(--lle-line);border-radius:2px;}
.lle-progress{position:absolute;inset:0;width:100%;background:var(--lle-gold);border-radius:2px;transform:scaleY(0);transform-origin:top;}
.lle-progress.is-running{animation:lleFill var(--lle-dwell) linear forwards;}
.lle-progress.is-static{transform:scaleY(1);}
.lle-section.is-paused .lle-progress.is-running{animation-play-state:paused;}
@keyframes lleFill{from{transform:scaleY(0);}to{transform:scaleY(1);}}
.lle-desc{font-size:13.5px;line-height:1.6;padding-bottom:14px;}
.lle-stage{position:relative;width:100%;height:var(--img-h);overflow:hidden;background:transparent;}
.lle-belt{display:flex;height:100%;}
.lle-frame{flex:0 0 100%;display:flex;gap:var(--gap);height:100%;}
.lle-cell{position:relative;height:100%;border-radius:14px;overflow:hidden;background:#0d1410;background-size:cover;background-position:center;box-shadow:0 26px 50px -26px rgba(20,30,45,.55);}
.lle-cell--big{flex:0 0 calc(var(--active-w) - var(--gap));min-width:0;}
.lle-cell--small{flex:0 0 var(--inactive-w);min-width:0;cursor:pointer;filter:brightness(.72) saturate(.92);}
.lle--slide .lle-belt{transform:translateX(calc(var(--i,0) * -100%));transition:transform .55s cubic-bezier(.6,.01,.18,1);will-change:transform;}
.lle--fade .lle-belt{position:relative;}
.lle--fade .lle-frame{position:absolute;inset:0;opacity:0;transition:opacity .6s ease;pointer-events:none;}
.lle--fade .lle-frame.is-shown{opacity:1;pointer-events:auto;}
@media (max-width:860px){
  .lle-section{padding:48px 18px;}
  .lle-inner{grid-template-columns:minmax(0,1fr);grid-template-areas:"head" "stage" "steps";gap:28px;}
  .lle-stage{height:clamp(220px,60vw,360px);--gap:14px;}
}
@media (prefers-reduced-motion:reduce){
  .lle-inner{animation:none;}
  .lle--slide .lle-belt{transition:none;}
  .lle--fade .lle-frame{transition:none;}
  .lle-progress.is-running{animation:none;transform:scaleY(1);}
}
CSS;
}

function lle_js() {
	return <<<'JS'
(function(){
  function init(root){
    if(!root || root.dataset.lleReady) return;
    root.dataset.lleReady = "1";

    var steps  = Array.prototype.slice.call(root.querySelectorAll('[data-step]'));
    var tabs   = Array.prototype.slice.call(root.querySelectorAll('[role="tab"]'));
    var belt   = root.querySelector('.lle-belt');
    if(!belt || !tabs.length) return;
    var frames = Array.prototype.slice.call(belt.querySelectorAll('.lle-frame'));
    if(!frames.length) return;

    var count    = tabs.length;
    var slide    = root.className.indexOf('lle--slide') !== -1;
    var reduce   = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var autoplay = root.getAttribute('data-autoplay') !== '0';
    var current  = 0, paused = false, pos = 0, wrapT = null;
    var SLIDE_MS = 600;

    // For slide, clone the first frame and append it so the loop from the
    // last step back to the first slides seamlessly (no blank gap).
    if(slide){
      var clone = frames[0].cloneNode(true);
      clone.className = 'lle-frame lle-clone';
      clone.setAttribute('aria-hidden','true');
      belt.appendChild(clone);
    }

    function setBelt(animate){
      if(reduce) animate = false;
      belt.style.transition = animate ? '' : 'none';
      belt.style.setProperty('--i', pos);
      if(!animate){ void belt.offsetWidth; belt.style.transition = ''; }
    }
    function paintFade(){
      frames.forEach(function(f, idx){ f.classList.toggle('is-shown', idx === current); });
    }
    function paintTabs(){
      tabs.forEach(function(t, idx){
        var on = idx === current;
        t.setAttribute('aria-selected', on ? 'true' : 'false');
        t.tabIndex = on ? 0 : -1;
        steps[idx].classList.toggle('is-active', on);
      });
    }
    function restartTimer(){
      steps.forEach(function(s){
        var p = s.querySelector('.lle-progress');
        if(p){ p.classList.remove('is-running','is-static'); p.style.animation = 'none'; }
      });
      var active = steps[current] && steps[current].querySelector('.lle-progress');
      if(!active) return;
      if(reduce || !autoplay){ active.classList.add('is-static'); return; }
      active.style.animation = '';
      void active.offsetWidth;
      active.classList.add('is-running');
    }
    function afterPaint(){ paintTabs(); restartTimer(); }

    function commitSlide(){
      clearTimeout(wrapT);
      setBelt(true);
      afterPaint();
      if(pos === count){   // landed on the appended clone -> snap home after the slide
        wrapT = setTimeout(function(){
          if(pos === count){ pos = 0; setBelt(false); }
        }, SLIDE_MS + 40);
      }
    }

    // viaNext = move forward by one (auto-play, next-image click, arrow forward);
    // this uses the clone so the wrap from last->first is seamless.
    function setActive(i, focus, viaNext){
      if(slide){
        if(viaNext){
          if(pos >= count){ clearTimeout(wrapT); pos = 0; setBelt(false); }  // snap off the clone first
          pos = pos + 1; current = pos % count;
        }
        else { current = ((i % count) + count) % count; pos = current; }
        commitSlide();
      } else {
        current = ((i % count) + count) % count;
        paintFade();
        afterPaint();
      }
      if(focus){ tabs[current].focus(); }
    }

    // AUTO-PLAY: advance when the gold timer fills
    root.addEventListener('animationend', function(e){
      if(paused) return;
      if(!e.target.classList || !e.target.classList.contains('lle-progress')) return;
      if(e.animationName !== 'lleFill') return;
      if(steps[current].querySelector('.lle-progress') === e.target){ setActive(current + 1, false, true); }
    });

    // CLICK the small (next) image -> advance one step
    Array.prototype.slice.call(root.querySelectorAll('.lle-cell--small')).forEach(function(c){
      c.addEventListener('click', function(){ setActive(current + 1, false, true); });
    });

    // CLICK / KEYBOARD on the timeline steps
    tabs.forEach(function(t, i){
      t.addEventListener('click', function(){ setActive(i, false, false); });
      t.addEventListener('keydown', function(e){
        var k = e.key;
        if(k === 'ArrowDown' || k === 'ArrowRight'){ e.preventDefault(); setActive(current + 1, true, true); }
        else if(k === 'ArrowUp' || k === 'ArrowLeft'){ e.preventDefault(); setActive(current - 1, true, false); }
        else if(k === 'Home'){ e.preventDefault(); setActive(0, true, false); }
        else if(k === 'End'){ e.preventDefault(); setActive(count - 1, true, false); }
      });
    });

    // pause on hover / keyboard focus
    root.addEventListener('mouseenter', function(){ paused = true; root.classList.add('is-paused'); });
    root.addEventListener('mouseleave', function(){ paused = false; root.classList.remove('is-paused'); });
    root.addEventListener('focusin',  function(){ paused = true; root.classList.add('is-paused'); });
    root.addEventListener('focusout', function(e){ if(!root.contains(e.relatedTarget)){ paused = false; root.classList.remove('is-paused'); } });

    if(slide){ pos = 0; setBelt(false); } else { paintFade(); }
    afterPaint();
  }
  function boot(){ document.querySelectorAll('[data-lle]').forEach(init); }
  if(document.readyState === 'loading'){ document.addEventListener('DOMContentLoaded', boot); }
  else { boot(); }
})();
JS;
}
