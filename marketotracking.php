<?php
/**
 * Plugin Name: Wordpress Marketo Lead Tracking
 * Plugin URI: http://www.rmlsoftwaresolutions.com/wordpress-plugins
 * Description: Attaches Marketo tracking code to website. Lets you map any form field to a specific field in marketo, including custom fields. NOTE: This is not a forms plugin. You use it in conjunction with your existing forms solution, or you can also use it with custom forms.
 * Version: 0.5
 * Author: Robert Lester
 * Author URI: http://www.rmlsoftwaresolutions.com
 * License: GPL2
 * Copyright 2014  Robert M Lester Jr  (email : rob@rmlsoftwaresolutions.com)
 */
/*
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

function wmi_scripts() {
	wp_enqueue_script( 'fieldmap.js', plugins_url() . '/marketo-lead-tracking/fieldmap.js', array(), '1.0.0', false );
}

function detect_fields($page) {
	$dom = new DOMDocument();
	$html = file_get_contents('example.html');
	
	$names = array();
	
	@$dom->loadHTML($html);
	
	$in = $dom->getElementsByTagName('input');
	$sel = $dom->getElementsByTagName('select');
	$ta = $dom->getElementsByTagName('textarea');
	
	for ($i; $i < $in->length; $i++) {
		$attr = $in->item($i)->getAttribute('name');
		$names[] = $attr;
	}
	for ($i; $i < $sel->length; $i++) {
		$attr = $sel->item($i)->getAttribute('name');
		$names[] = $attr;
	}
	for ($i; $i < $ta->length; $i++) {
		$attr = $ta->item($i)->getAttribute('name');
		$names[] = $attr;
	}
	
	return json_encode($names);
}

add_action( 'wp_enqueue_scripts', 'wmi_scripts' );
add_action( 'admin_enqueue_scripts', 'wmi_scripts' );
add_action( 'admin_menu', 'wmi_admin_menu');
add_action( 'admin_init', 'wmi_register_settings' );
add_action( 'wp_head', 'wmi_call_code');

function wmi_call_code() { 
	$j = get_option('wmi-mkto-field-map');
	if ($j) {
		$fields = json_decode($j);
		$output = '';
		foreach ($fields as $map) {
			foreach ($map as $k=>$v) {
				if (strpos($k, '*') !== false) {
					$emailfield = $v;
					$k = str_replace('*', '', $k);
				}
				$sendval = $_POST[$v];
				if ($sendval) $output .= $k . ': decodeURIComponent("' . rawurlencode($sendval) . '"),';
				//if ($sendval) $output .= $k . ': "' . $sendval . '",';
			}
		}
		$em = $_POST[$emailfield];
		$h = hash('sha1', get_option('wmi-mkto-api-key') . $em);
		$track = get_option('wmi-mkto-tracking');
		if ($em != '' || $track) {
		?>
			<script type="text/javascript">
				document.write(unescape("%3Cscript src='//munchkin.marketo.net/munchkin.js' type='text/javascript'%3E%3C/script%3E"));
			</script>
			<script type="text/javascript">
				Munchkin.init("<?php echo get_option('wmi-mkto-account-id'); ?>");
			</script>
		<?php }
		if ($em != '') {
			?>
				<script type="text/javascript">
					mktoMunchkinFunction("associateLead",{<?php echo rtrim($output, ','); ?>},"<?php echo $h; ?>");
				</script>
			<?php
		}
	}
}

function wmi_admin_menu() {
	add_options_page('Wordpress Marketo Lead Tracking', 'Marketo Leads', 'administrator', 'marketo-leads.php', 'wmi_options_page');
}

function wmi_register_settings() {
	register_setting( 'wmi-settings-group-api', 'wmi-mkto-account-id' );
	register_setting( 'wmi-settings-group-api', 'wmi-mkto-api-key' );
	register_setting( 'wmi-settings-group-api', 'wmi-mkto-field-map' );
	register_setting( 'wmi-settings-group-api', 'wmi-mkto-tracking' );
}

function wmi_options_page() { 

?>
	<div class="wrap">
		<h2>Wordpress Marketo Lead Tracking</h2>
		
		<form method="post" action="options.php">
			<?php settings_fields( 'wmi-settings-group-api' ); ?>
			<?php do_settings_sections( 'wmi-settings-group-api' ); ?>
			<style>
				#wmi-field-mapping tr th, #wmi-field-mapping tr td {
					padding: 3px 0;
				}
			</style>
			<table class="form-table">
			<thead>
				<tr valign="top">
				<th scope="row">Marketo Account ID:</th>
				<td><input type="text" name="wmi-mkto-account-id" value="<?php echo get_option('wmi-mkto-account-id'); ?>" /></td>
				</tr>
				<tr valign="top">
				<th scope="row">Munchkin API Key:</th>
				<td><input type="text" name="wmi-mkto-api-key" value="<?php echo get_option('wmi-mkto-api-key'); ?>" /></td>
				</tr>
				<tr valign="top">
				<th scope="row">Track Lead Activity:</th>
				<?php
					$track = get_option('wmi-mkto-tracking');
					if ($track) $track = 'checked';
				?>
				<td><input type="checkbox" name="wmi-mkto-tracking" <?php echo $track; ?> /></td>
			</thead>
			<tbody id="wmi-field-mapping">
			<tr><th style="margin: 0; padding: 0 0 10px;"><h3 style="padding: 0; margin: 0;">Field Mapping</h3></th></tr>
				<?php 
					$json = get_option('wmi-mkto-field-map');
					$fields = json_decode($json);
					if ($fields) {
						foreach ($fields as $map) {
							foreach ($map as $k=>$v) {
								$output = '<tr valign="top">';
								$output .= '<th id="mktocell" scope="row"><input type="text" id="mkto-' . $k . '" value="' . $k . '" /></th>';
								$output .= '<td id="fieldcell"><input type="text" id="field=' . $v . '" value="' . $v . '" /><a href="#" id="wmi-btn-del" class="button" style="margin-left: 10px;">Delete</a></td>';
								$output .= '</tr>';
								echo $output;
							}
						}
					}
				?>
				
			</tbody>
				<tr valign="top">
					<td colspan="2"><a href="#" id="wmi-btn-add" class="button button-primary">Add</a></td>
				</tr>
				<tr valign="top">
					<th scope="row">Autodetect fields on page:</th>
					<td>
					<select id="detectpages">
						<?php
							$posts = get_posts(array( 'posts_per_page'=>10000, 'post_status'=>'publish', 'post_type'=>'any' ));
							foreach ($posts as $post) {
								echo '<option value="' . get_permalink($post->ID) . '">' . $post->post_title . '</option>';
							}
						?>
					</select>
					<a href="#" id="wmi-btn-detect" class="button">Detect</a></td>
				</tr>
				<tr>
					<td colspan="2">For the "Local Field Name" you must enter the actual "name" attribute of the text box in the form from which you want to send the data to Marketo. Also, for this plugin to work, your forms must post.</td>
				</tr>
			</table>
			
			<?php submit_button(); ?>
			<input type="hidden" name="wmi-mkto-field-map" id="wmi-mkto-field-map" value="<?php echo get_option('wmi-mkto-field-map'); ?>" />
		</form>
	</div>

<?php 
} 

?>