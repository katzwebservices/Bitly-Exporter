<?php /*
Plugin Name: Bitly Exporter
Plugin URI: http://www.seodenver.com/bitly-exporter/
Description: Export your Bitly link click history.
Author: Katz Web Services, Inc.
Version: 1.0.2
Author URI: http://www.katzwebservices.com

--------------------------------------------------

Copyright 2011  Katz Web Services, Inc.  (email : info@katzwebservices.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

*/
add_action('init', array('KWS_BitlyExporter','init'),1);

class KWS_BitlyExporter {

    var $version = '1.0.2';

	function init() {
		if(is_admin()) {
			global $pagenow;
			$bitlyExporter = new KWS_BitlyExporter();
			if((in_array(basename($_SERVER['PHP_SELF']), array('tools.php')) && isset($_REQUEST['page']) && $_REQUEST['page'] == 'bitlyexporter'))  {
				$plugin_dir = basename(dirname(__FILE__)).'languages';
				load_plugin_textdomain( 'bitlyexporter', 'wp-content/plugins/' . $plugin_dir, $plugin_dir );
			}
		}
	}

	function KWS_BitlyExporter() {
    	add_action('admin_menu', array(&$this, 'admin'));
	    add_filter('plugin_action_links', array(&$this, 'settings_link'), 10, 2 );
        add_action('admin_init', array(&$this, 'settings_init') );

    	if(in_array(basename($_SERVER['PHP_SELF']), array('tools.php')) && isset($_REQUEST['page']) && $_REQUEST['page'] == 'bitlyexporter')  {

	    	$this->options = get_option('bitlyexporter', array());

	        // Set each setting...
	        foreach($this->options as $key=> $value) {
	        	$this->{$key} = $value;
	        }

	        if(!empty($this->apikey)) { define('bitlyKey', $this->apikey); }
			if(!empty($this->username)) { define('bitlyLogin' , $this->username); }

            if(!defined('bitlyKey')) {
                $this->configured = false;
                return;
            }

			include_once('class.bitly.php');

	        if(in_array(basename($_SERVER['PHP_SELF']), array('tools.php')) && isset($_REQUEST['page']) && $_REQUEST['page'] == 'bitlyexporter') {
	        	$this->CheckSettings();
	        	$this->processSubmit();
	        	$this->processDownload();
	        }
		}
    }

  	function form_table($rows) {
        $content = '<table class="form-table" width="100%">';
        foreach ($rows as $row) {
            $content .= '<tr><th valign="top" scope="row" style="width:50%">';
            if (isset($row['id']) && $row['id'] != '')
                $content .= '<label for="'.$row['id'].'" style="font-weight:bold;">'.$row['label'].':</label>';
            else
                $content .= $row['label'];
            if (isset($row['desc']) && $row['desc'] != '')
                $content .= '<br/><small>'.$row['desc'].'</small>';
            $content .= '</th><td valign="top">';
            $content .= $row['content'];
            $content .= '</td></tr>';
        }
        $content .= '</table>';
        return $content;
    }

    function postbox($id, $title, $content, $padding=false) {
        ?>
            <div id="<?php echo $id; ?>" class="postbox">
                <div class="handlediv" title="Click to toggle"><br /></div>
                <h3 class="hndle"><span><?php echo $title; ?></span></h3>
                <div class="inside" <?php if($padding) { echo 'style="padding:10px; padding-top:0;"'; } ?>>
                    <?php echo $content; ?>
                </div>
            </div>
        <?php
    }

    function make_notice_box($content, $type="error") {
        $output = '';
        if($type!='error') { $output .= '<div style="background-color: rgb(255, 255, 224);border-color: rgb(230, 219, 85);-webkit-border-bottom-left-radius: 3px 3px;-webkit-border-bottom-right-radius: 3px 3px;-webkit-border-top-left-radius: 3px 3px;-webkit-border-top-right-radius: 3px 3px;border-style: solid;border-width: 1px;margin: 5px 0px 15px;padding: 0px 0.6em;">';
        } else {
            $output .= '<div style="background-color: rgb(255, 235, 232);border-color: rgb(204, 0, 0);-webkit-border-bottom-left-radius: 3px 3px;-webkit-border-bottom-right-radius: 3px 3px;-webkit-border-top-left-radius: 3px 3px;-webkit-border-top-right-radius: 3px 3px;border-style: solid;border-width: 1px;margin: 5px 0px 15px;padding: 0px 0.6em;">';
        }
        $output .= '<p style="line-height: 1; margin: 0.5em 0px; padding: 2px;">'.$content.'</div>';
        return($output);
    }

    private function CheckSettings() {
		$this->configured = bitly_v3_validate($this->username, $this->apikey);
		return $this->configured;
	}

	function configuration() {
		return $html;
    }

    function admin() {
        add_management_page('Bitly Exporter', 'Bitly Exporter', 'administrator', 'bitlyexporter', array(&$this, 'admin_page'));
    }

	function admin_page() {
        ?>
        <div class="wrap">
        <?php $this->show_rating_box('Bitly Exporter', 'bitly-exporter', $this->version); ?>
        <h2><span style="display:block; line-height:84px; padding-left:110px; background: url(<?php echo plugins_url( 'bitly.png', __FILE__ ); ?>) left center no-repeat;"><?php _e('Bitly Exporter', 'bitlyexporter'); ?></span></h2>
<?php if($this->configured) { ?>
        <div class="clear"></div>
        <div class="postbox-container" style="width:59%; margin-right:3%;">
            <div class="metabox-holder">
				<div class="meta-box-sortables">
                	<?php
                		$this->showRSSForm();
						$this->showLinkForm();
					?>
                </div>
            </div>
        </div>
        <div class="postbox-container" style="width:34%;">
            <div class="metabox-holder">
                <div class="meta-box-sortables">
                <?php
                $this->showSettingsForm();
                ?>
                </div>
            </div>
        </div>
<?php } else { ?>
	 <div class="postbox-container" style="width:50%;">
        <div class="metabox-holder">
            <div class="meta-box-sortables">
            <?php
            $this->showSettingsForm();
            ?>
            </div>
        </div>
    </div>
<?php } ?>
    </div>
    <?php
    }

    function showSettingsForm() { ?>
     <form action="options.php" method="post">
       <?php
       		$this->show_configuration_check(false);
        	wp_nonce_field('update-options');
            settings_fields('bitlyexporter_options');

$rows[] = array(
                    'id' => 'bitlyexporter_username',
                    'label' => __('Bitly Username', 'bitlyexporter'),
                    'content' => "<input type='text' name='bitlyexporter[username]' id='bitlyexporter_username' value='".esc_attr($this->username)."' size='40' style='width:95%!important;' />",
                    'desc' => ''
             );

/*
             $rows[] = array(
                    'id' => 'bitlyexporter_password',
                    'label' => __('Bitly Password (optional)', 'bitlyexporter'),
                    'content' => "<input type='text' name='bitlyexporter[password]' id='bitlyexporter_password' value='".esc_attr($this->password)."' size='40' style='width:95%!important;' />",
                    'desc' => 'The password is only required if you do not have the API key.'
             );
*/

            $rows[] = array(
                    'id' => 'bitlyexporter_apikey',
                    'label' => __('API Key', 'bitlyexporter'),
                    'desc' => 'Your Bitly API Key',
                    'content' => "<input type='text' name='bitlyexporter[apikey]' id='bitlyexporter_apikey' value='".esc_attr($this->apikey)."' size='40' style='width:95%!important;' />"
            );

            if($this->configured) { $name = 'Bitly API Settings <span style="color:green">(configured)</span>'; } else { $name = 'Bitly API Settings <span style="color:#c00">(not configured)</span>'; }
            $this->postbox('bitlyexportersettings',$name, $this->form_table($rows), false);

        ?>

            <p>
            	<input type="hidden" name="page_options" value="<?php foreach($rows as $row) { $output .= $row['id'].','; } echo substr($output, 0, -1);?>" />
            	<input type="hidden" name="action" value="update" />
            	<?php if($this->configured) { ?>
	                <input type="submit" class="button-secondary alignright action" name="save" value="<?php _e('Update Settings', 'bitlyexporter') ?>" />
	            <?php } else { ?>
	            	<input type="submit" class="button-primary action" name="save" value="<?php _e('Update Settings', 'bitlyexporter') ?>" />
	            <?php } ?>
            </p>
        </form>
    <?php
    }


    function showRSSForm() { ?>
    	<form action="<?php echo  admin_url( 'tools.php?page=bitlyexporter' ); ?>" method="post">
    <?php
    	$feed = wp_remote_get('http://bitly.com/u/'.$this->username.'.rss');
    	if(is_wp_error($feed) || $feed['response']['code'] != 200) {
    		echo '<div class="error" id="message"><h3>There was an error verifying your account details.</h3> <p>Is your bitly History Public? If not, select "Public" from "Default bitmark privacy" in your <a href="https://bitly.com/a/settings/saving">Bitly account</a>.</p><p>If you know the details are correct, you will be able to use the Export Link History functionality and ignore this notice.</p></div>';
    		return;
    	}
    ?>
		</form>
	<?php
    }

    function showLinkForm() { ?>
    	<form action="<?php echo  admin_url( 'tools.php?page=bitlyexporter' ); ?>" method="post">
       	<?php
       		if(isset($_POST['link']) && empty($_POST['link'])) {
       			echo $this->make_notice_box('<label for="bitlyexporter_link">You must enter a link.</label>', 'error');
       		}

       		$disabled = $this->configured ? '' : ' disabled="disabled"';
       		$link = isset($_POST['link']) ? $_POST['link'] : "";
       		$time = isset($_POST['time']) ? $_POST['time'] : 90;
       		$export[] = array(
                    'id' => 'bitlyexporter_link',
                    'label' => __('Bitly Link', 'bitlyexporter'),
                    'desc' => 'Use any Bitly service link, including j.mp or custom links.',
                    'content' => "<label for='bitlyexporter_link'><input type='text' name='link' id='bitlyexporter_link' {$disabled} value='".$link."' size='40' style='width:95%!important;' /> <span class='howto'>You can use either <span class='code'>http://bitly.com/example</span>&nbsp;&nbsp;<span style='color:#555; font-style:normal;'>or</span> <span class='code'>example</span></span></label>"
            );

            $export[] = array(
                    'id' => 'bitlyexporter_link',
                    'label' => __('Time Period', 'bitlyexporter'),
                    'desc' => 'How long do you want stats for?',
                    'content' => "
						<label for='bitlyexporter_time' class='howto'>
							<span>Show</span>
							<select name='time' {$disabled} id='bitlyexporter_time'>
								<option id='bitlyexporter_time_days_1095' value='1095'".selected($time, 1095, false).">Three years</option>
								<option id='bitlyexporter_time_days_730' value='730'".selected($time, 730, false).">Two years</option>
								<option id='bitlyexporter_time_days_365' value='365'".selected($time, 365, false).">One year</option>
								<option id='bitlyexporter_time_days_180' value='180'".selected($time, 180, false).">180 days</option>
								<option id='bitlyexporter_time_days_90' value='90'".selected($time, 90, false).">90 days</option>
								<option id='bitlyexporter_time_days_60' value='60'".selected($time, 60, false).">60 days</option>
								<option id='bitlyexporter_time_days_30' value='30'".selected($time, 30, false).">30 days</option>
								<option id='bitlyexporter_time_days_14' value='14'".selected($time, 14, false).">2 weeks</option>
								<option id='bitlyexporter_time_days_7' value='7'".selected($time, 7, false).">1 week</option>
							</select>
							<span>of statistics</span>
						</label>
                    "
            );

            $this->postbox('bitlyexporterexport',__('Export Link History', 'bitlyexporter'), $this->form_table($export), false);
       	?>
       		<p>
               	<?php wp_nonce_field('export_bitly_action','export_bitly_field'); ?>
               	<input type="submit" <?php echo $disabled; ?> class="button-primary" name="export" value="Get Link History" />
            </p>
       	</form>
       	<?php
       		$this->linkStatsOutput();
    }

    /**
     * Print a rating box that shows the star ratings and an upgrade message if a newer version of the plugin is available.
     *
     * Plugin data is fetched using the `plugins_api()` function, then cached for 2 hours as a transient using the `{$slug}_plugin_info` key.
     *
     * @uses plugins_api() Get the plugin data
     * @param  string $name    The display name of the plugin.
     * @param  string $slug    The WP.org directory repo slug of the plugin
     * @param  string|float|integer $version The version number of the plugin
     */
    function show_rating_box($name = '', $slug = '', $version) {
        global $wp_version;

    ?>
        <div class="<?php echo $slug; ?>-ratingbox alignright" style="padding:9px 0; max-width:400px;">
        <?php
            // Display plugin ratings

            require_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );

            // Get the cached data
            $api = get_transient( $slug.'_plugin_info' );

            // The cache data doesn't exist or it's expired.
            if (empty($api)) {

                $api = plugins_api( 'plugin_information', array( 'slug' => $slug ) );

                if ( !is_wp_error( $api ) ) {
                    // Cache for 2 hours
                    set_transient( $slug.'_plugin_info', $api, 60 * 60 * 2 );
                }
            }

            if ( !is_wp_error( $api ) ) {

                if ( !empty( $api->rating ) ) { ?>
                <p><a target="_blank" href="http://wordpress.org/support/view/plugin-reviews/<?php echo $slug; ?>?rate=5#postform" class="button button-secondary"><?php _e( 'Rate this Plugin', 'wpinterspire' ) ?></a> <strong><?php _e( '&larr; Help spread the word!', 'wpinterspire'); ?></strong></p>
                    <?php
                    if ( !empty( $api->downloaded ) ) {
                        echo sprintf( __( 'Downloaded %s times.', 'wpinterspire' ), number_format_i18n( $api->downloaded ) );
                    } ?>
                    <div class="star-holder" title="<?php echo esc_attr( sprintf( __( '(Average rating based on %s ratings)', 'wpinterspire' ), number_format_i18n( $api->num_ratings ) ) ); ?>">
                        <div class="star-rating" style="width: <?php echo esc_attr( $api->rating ) ?>px"></div>
                    </div>
                    <div><small style="display:block;"><?php
                        echo sprintf( __( 'Average rating based on %s ratings.', 'wpinterspire' ), number_format_i18n( $api->num_ratings ) ); ?></small></div>
                    <?php
                }
            } // if ( !is_wp_error($api)

            if ( isset( $api->version ) ) {
                if (
                    // A newer version is available
                    version_compare( $api->version, $version, '>' ) &&

                    // And the current version of WordPress supports it.
                    version_compare( $api->requires, $wp_version, '<=')
                ) {

                    $message = sprintf(__( '%sA newer version of %s is available: %s.%s', 'wpinterspire' ), '<a class="thickbox" title="Update '.esc_html( $name ).'" href="'.admin_url('plugin-install.php?tab=plugin-information&plugin='.$slug.'&section=changelog&TB_iframe=true&width=640&height=808').'">', esc_html($name), $api->version, '</a>');

                    // Don't use make_notice_box so can be reused in other plugins.
                    echo '<div id="message" class="updated">'.wpautop($message).'</div>';

                }
                // There's a newer version available, but the current WP install doesn't support it.
                elseif(version_compare( $api->requires, $wp_version, '>')) {
                    echo '<div id="message" class="updated">';
                    echo wpautop(sprintf(__('There is a newer version of %s available, but your current version of WordPress does not support it.

                        %sUpdate WordPress%s', 'wpinterspire'), $name, '<a class="button button-secondary" href="'.admin_url( 'update-core.php' ).'">', '</a>'));
                    echo '</div>';
                }
                else {
                    echo wpautop(sprintf( __( 'Version %s (up to date)', 'si-contact-form' ), $version ));
                }
            }   ?>
        </div>
        <?php
    }

	function processSubmit() {
 		if ( !empty($_POST) && check_admin_referer('export_bitly_action','export_bitly_field') ) {
			extract($_POST);

			$this->transientKey = 'bit_'.sha1("{$link}_{$time}");

			$data = get_transient($this->transientKey);
			if(!$data) { $data = bitly_v3_clicks_by_day($link, $time); }
			if(empty($data)) { return false; }

			foreach($data[0]['clicks'] as $key => $day) {
				$data[0]['clicks'][$key]['date'] = date('m/d/Y',$day['day_start']);
				$data[0]['clicks'][$key]['day_of_week'] = date('l',$day['day_start']);
				$data[0]['clicks'][$key]['week'] = date('W',$day['day_start']);
				$data[0]['clicks'][$key]['month'] = date('m',$day['day_start']);
				$data[0]['clicks'][$key]['year'] = date('Y',$day['day_start']);
			}
			$data[0]['time'] = $time;
			$data[0]['link'] = $link;

			set_transient($this->transientKey, $data, 60*60);
		}
    }

    function linkStatsOutput() {
    	if(empty($this->transientKey)) { return false; }
    	$data = get_transient($this->transientKey);
    	if(empty($data)) { return false; }
    	$clicks = array();
        $thead = $tbody = NULL;
    	foreach($data[0]['clicks'] as $key => $day) {
    		unset($day['day_start'], $day['month'],  $day['week'],  $day['year']);
    		if($key == 0) {
    			$cols = sizeof($day);
    			foreach($day as $k => $d) {
    				$k = str_replace('_',' ',$k);
    				$k = ucwords($k);
    				$thead .= "<th scope='col'>{$k}</th>";
    			}
    		}
    		$width = round(100/$cols, 2);
    		$tbody .= '<tr>';
	    	foreach($day as $k => $d) {
	    		if($k == 'clicks') { $clicks[] = $d; }
	    		$tbody .= "<td style='width:{$width}%'>{$d}</td>";
	    	}
	    	$tbody .= '</tr>';
    	}
    	$max = 0;
    	foreach($clicks as $click) { if($click > $max) { $max = $click; } }
    	$maxrounded = (round(($max+5)/10))*10;
    	$spread = (floor($data[0]['time'] / 15) > 1) ? floor($data[0]['time'] / 15) : 1;

		$charttitle = "Clicks+in+the+last+";
		switch($data[0]['time']) {
			case 7: $charttitle .= 'week'; break;
			case 14: $charttitle .= 'two+weeks'; break;
			case 365: $charttitle .= 'year'; break;
			case 730: $charttitle .= 'two+years'; break;
			case 1095: $charttitle .= 'three+years'; break;
			default: $charttitle .= number_format($data[0]['time']).'+days'; break;
		}

    	$downloadlink = '<h2 class="alignright"><a href="'.add_query_arg('transient', $this->transientKey).'" target="_blank">&darr; Download Data as CSV</a></h2>';
    	$chart = '<img width="600" height="300" style="text-align:center; display:block; margin:10px auto;" src="https://chart.googleapis.com/chart?chs=600x300&amp;cht=lc&amp;chxt=x,x,y,y&amp;chxr=0,'.$data[0]['time'].',0,'.$spread.'|2,0,'.$maxrounded.','.(round($maxrounded/10)).'&amp;chxp=1,50|3,50&amp;chxl=1:|Days+from+now|3:|Clicks&amp;chds=-0,'.$max.'&amp;chg=10,-1,1,1&amp;chco=3D7930&amp;chls=1,4,0&amp;chm=B,C5D4B5BB,0,0,0&amp;chtt='.$charttitle.'&amp;chd=t:'.implode(',', array_reverse($clicks)).'" />';

    	$stats = $this->processStats($clicks);

    	if($data[0]['time'] > 800) { $chart = "<p style='text-align:center; padding:10px' class='updated'>Chart not available; too much data!</p>"; }
    	echo $chart.$stats.$downloadlink.'
    	<table class="widefat">
    	<thead>'.$thead.'</thead>
    	<tbody>
    		'.$tbody.'
    	<tbody>
    	</table>';
    }
    function processStats($clicks) {
    	$count = count($clicks);
    	$max = 0;
    	$min = 1000000000000;
        $sum = 0;
    	foreach($clicks as $click) {
    		$sum = $sum + (int)$click;
    		if($min > $click) { $min = $click; }
    		if($max < $click) { $max = $click; }
    	}
    	$avg = round($sum/$count,2);
    	$avg = number_format($avg);
    	$max = number_format($max);
    	$min = number_format($min);
    	$sum = number_format($sum);
    	return "
    	<div class='wrap alignleft'><h3>Stats for this time period:</h3>
    	<ul class='ul-disc'>
    		<li>Total # of Clicks: {$sum}</li>
    		<li>Average # of Clicks: {$avg}</li>
    		<li>Max # of Clicks: {$max}</li>
    		<li>Min # of Clicks: {$min}</li>
    	</ul></div>
    	";
    }

    function processDownload() {
    	if(empty($_GET['transient'])) { return; }

    	$data = get_transient($_GET['transient']);

		if(!$data) { return false; }

		// output up to 5MB is kept in memory, if it becomes bigger
		// it will automatically be written to a temporary file
		$csv = fopen('php://temp/maxmemory:'. (5*1024*1024), 'r+');

		fputcsv($csv, array('clicks', 'timestamp', 'date', 'day of week', 'week', 'month', 'year','days since export'));

		foreach ($data[0]['clicks'] as $key => $fields) {
			$fields['id'] = $key;
			fputcsv($csv, $fields);
		}

		rewind($csv);

		// put it all in a variable
		$output = stream_get_contents($csv);

		$filename = empty($data[0]['user_hash']) ? $data[0]['global_hash'] : $data[0]['user_hash'];
		if(!empty($data[0]['time'])) { $filename .= '-'.(int)$data[0]['time'].'days'; }

		header("Content-type: application/octet-stream");
		header("Content-Disposition: attachment; filename=\"{$filename}-bitly-export.csv\"");
		die($output);
    }

    function show_configuration_check($link = true) {
    	$options = $this->options;

    	if(!isset($_GET['updated'])) {return; }

        if(!function_exists('curl_init')) { // Added 1.2.2
            $content = __('Your server does not support <code>curl_init</code>. Please call your host and ask them to enable this functionality, which is required for this awesome plugin.', 'bitlyexporter');
            echo $this->make_notice_box($content, 'error');
        } else {
            if($this->configured) {
                $content = __('Your '); if($link) { $content .= '<a href="' . admin_url( 'tools.php?page=bitlyexporter' ) . '">'; } $content .=  __('Bitly API settings', 'bitlyexporter'); if($link) { $content .= '</a>'; } $content .= __(' are configured properly');
                echo $this->make_notice_box($content, 'success');
            } else {
                $content = 'Your '; if($link) { $content .= '<a href="' . admin_url( 'tools.php?page=bitlyexporter' ) . '">'; } $content .=  __('Bitly API settings', 'bitlyexporter') ; if($link) { $content .= '</a>'; } $content .= '  are <strong>not configured properly.</strong> ';
                $content .= '<br/><br/>Find your API Key settings on <a href="http://bitly.com/a/your_api_key/" target="_blank">http://bitly.com/a/your_api_key/</a>';
                echo $this->make_notice_box($content, 'error');
            };
        }
    }

    function settings_link( $links, $file ) {
        static $this_plugin;
        if( ! $this_plugin ) $this_plugin = plugin_basename(__FILE__);
        if ( $file == $this_plugin ) {
            $settings_link = '<a href="' . admin_url( 'tools.php?page=bitlyexporter' ) . '">' . __('Settings', 'bitlyexporter') . '</a>';
            array_unshift( $links, $settings_link ); // before other links
        }
        return $links;
    }

    function settings_init() {
        register_setting( 'bitlyexporter_options', 'bitlyexporter', array(&$this, 'sanitize_settings') );
    }

    function sanitize_settings($input) {
        return $input;
    }

}