<?php

class WPChrono {

	protected $plugin_name;
	protected $version;
	protected $current_date;
	protected $current_datetime;

	public function __construct() {

		$this->plugin_name = 'wp-chrono';
		$this->version = '1.5.3';

		$this->current_date = strtotime(current_time('Y-m-d'));
		$this->current_datetime = strtotime(current_time('Y-m-d G:i:s'));

		add_action( 'wp_enqueue_scripts', array($this, 'registerScripts') );
		add_action( 'wp_enqueue_scripts', array($this, 'registerStyles') );
		add_action('admin_init', array($this, 'registerAdminScripts'));

		$this->registerShortcodes();
		$this->registerAdminNotices();

	}

	public function registerScripts() {
		wp_enqueue_script( 'coutdowntimer_script', plugins_url( '../public/js/countdowntimer.js', __FILE__ ) );
	}

	public function registerAdminScripts() {
		wp_enqueue_script( 'my-notice-update', plugins_url( '../public/js/notice-update.js', __FILE__ ), array( 'jquery' ), '1.0', true  );
	}

	public function registerStyles() {
		 wp_enqueue_style( 'coutdowntimer_style', plugins_url( '../public/css/countdowntimer.css', __FILE__ ), array(), '20160617', 'all' );
	}

	public function registerShortcodes() {

		// Create a shortcode to display of current date
		add_shortcode('wpch-currentdate', array($this, 'currentDateShortcode'));

		// Create a shortcode to display of content on current date
		add_shortcode('wpch-ifdate', array($this,'ifDateShortcode'));

		// Create a shortcode to display of content on current date range
		add_shortcode('wpch-ifdaterange', array($this, 'ifDateRangeShortcode'));

		// Create a shortcode to display countdown timer for user selected date
		add_shortcode('wpch-countdowntimer', array($this, 'countdownTimerShortcode'));

	}

	public function registerAdminNotices() {
		
		if( !function_exists('the_field') && !get_option('wpch-notices-afterinstallmessage-dismissed' )) {
		  add_action( 'admin_notices', array($this, 'wpchInstallNotice'));
		}
		
		add_action( 'wp_ajax_wpch_install_notice_dismiss', array($this, 'wpchInstallNoticeDismissed') );

	}

	public function wpchInstallNoticeDismissed() {
		update_option( 'wpch-notices-afterinstallmessage-dismissed', 'true' );
	}
	
	public function wpchInstallNotice() {
	    ?>
	    <div class="notice notice-success wpch-install-notice is-dismissible">
	        <p><?php 
	        	_e( '<p>Hey, it\'s great to see that you are using the WPChrono plugin. How is everything going? If you can spare a few moments to rate it on WordPress.org it would help us a lot (and boost our motivation). Cheers! </p>', 'wp-chrono' );
	        	_e( '<p>- Milan, developer of WP Chrono</p>', 'wp-chrono' );
	        	_e( '<a target="_blank" href="https://wordpress.org/support/plugin/wp-chrono/reviews/#wporg-footer" class="button button-primary">Sure, take me to the review form</a> &nbsp;', 'wp-chrono' );
	        ?></p>
	    </div>
	    <?php
	}

	public function currentDateShortcode($atts) {

		$output = '';
		$displaydate_atts = shortcode_atts( array('format' => 'F jS, Y'), $atts);

		$shortcode_format = $displaydate_atts['format'];
		$output .= date_i18n($shortcode_format, $this->current_datetime);

	    return do_shortcode($output);
	}

	public function ifDateShortcode($atts, $content) {

		$output = '';
	   	if (empty($atts)) return '';

		$displaydate_atts = shortcode_atts( array('date' => ''), $atts);

		$shortcode_date = strtotime($displaydate_atts['date']);

		// Read Shortcode IF options
		$contents = explode("[else]", $content);

		// Clean contents (if there is no ELSE assign NULL)
		if ( ! isset($contents[0])) {
	   		$contents[0] = null;
		}
		if ( ! isset($contents[1])) {
	   		$contents[1] = null;
		}


		if($this->current_date == $shortcode_date){
			return do_shortcode($contents[0]);
		}
		else {
			return do_shortcode($contents[1]);
		}
	}

	public function ifDateRangeShortcode($atts, $content) {

		$output = '';
	   	if (empty($atts)) return '';

		$displaydate_atts = shortcode_atts( array('fromdate' => '', 'todate' => ''), $atts);

		$shortcode_fromdate = strtotime($displaydate_atts['fromdate']);
		$shortcode_todate = strtotime($displaydate_atts['todate']);

		// Read Shortcode IF options
		$contents = explode("[else]", $content);
		if ( ! isset($contents[0])) {
	   		$contents[0] = null;
		}
		if ( ! isset($contents[1])) {
	   		$contents[1] = null;
		}

		if ($this->checkDateInRange($shortcode_fromdate, $shortcode_todate, $this->current_datetime)) {
			return do_shortcode($contents[0]);
		}
		else {
			return do_shortcode($contents[1]);
		}
	}

	public function checkDateInRange($start_date, $end_date, $current_datetime) {

	  return (($current_datetime >= $start_date) && ($current_datetime <= $end_date));
	  
	}

	public function countdownTimerShortcode($atts, $content) {
		$output = '';
		$display_atts = shortcode_atts(
			array(
				'name' => '',
				'date' => '',
				'template' => '',
			), $atts
		);

		// Convert template attribute intro appropriate CSS class
		switch($display_atts['template']) {
			case "darkblue": $display_atts['template'] = "wpch_darkblue"; break;
			case "blue": $display_atts['template'] = "wpch_blue"; break;
			case "darkpurple": $display_atts['template'] = "wpch_darkpurple"; break;
			case "purple": $display_atts['template'] = "wpch_purple"; break;
			case "green": $display_atts['template'] = "wpch_green"; break;
			case "lightgreen": $display_atts['template'] = "wpch_lightgreen"; break;
			case "red": $display_atts['template'] = "wpch_red"; break;
			case "yellow": $display_atts['template'] = "wpch_yellow"; break;
			default: $display_atts['template'] = "wpch_default"; break;
		}



		$output = '<div id="wpch_clockdiv_'.$display_atts['name'].'" class="'. $display_atts['template'] .'">
					   <div>
					      <span class="wpch_days"></span>
					      <div class="wpch_smalltext">' .__( 'Days', 'wp-chrono' ) .'</div>
					    </div>
					    <div>
					      <span class="wpch_hours"></span>
					      <div class="wpch_smalltext">' .__( 'Hours', 'wp-chrono' ) .'</div>
					    </div>
					    <div>
					      <span class="wpch_minutes"></span>
					      <div class="wpch_smalltext">' .__( 'Minutes', 'wp-chrono' ) .'</div>
					    </div>
					    <div>
					      <span class="wpch_seconds"></span>
					      <div class="wpch_smalltext">' .__( 'Seconds', 'wp-chrono' ) .'</div>
					    </div>
					</div>
					<div id="wpch_clockdivcontent_'.$display_atts['name'].'">' . $content . '</div>
					<script>
						// initialize script
						initializeClock(\'wpch_clockdiv_'.$display_atts['name'].'\', \'wpch_clockdivcontent_'.$display_atts['name'].'\', \'' . $display_atts['date'] .' \');

					</script>';

		return $output;
	}

}