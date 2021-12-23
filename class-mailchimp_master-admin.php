<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://donatestuffdev.com
 * @since      1.0.0
 *
 * @package    Mailchimp_master
 * @subpackage Mailchimp_master/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Mailchimp_master
 * @subpackage Mailchimp_master/admin
 * @author     Donatestuff <donatestuff@gmail.com>
 */
class Mailchimp_master_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

        // Add custom pages for plugin
		add_action('admin_menu', array( $this, 'addPluginAdminMenu' ), 9);

		add_action('admin_init', array( $this, 'registerAndBuildFields' ));

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Mailchimp_master_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Mailchimp_master_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/mailchimp_master-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * An instance of this class should be passed to the run() function
		 * defined in Mailchimp_master_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Mailchimp_master_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/mailchimp_master-admin.js', array( 'jquery' ), $this->version, false );

	}

    /**
     * Add custom pages for plugin
     */
	public function addPluginAdminMenu() {
        add_menu_page(  $this->plugin_name, 'Charities Pickup Export', 'administrator', $this->plugin_name,
            array( $this, 'displayPluginAdminDashboard' ), 'dashicons-chart-bar', 27
        );

        add_submenu_page( $this->plugin_name, 'Settings', 'Settings', 'administrator', $this->plugin_name.'-settings',
            array( $this, 'displayPluginSettingsPage' )
        );

        add_submenu_page( $this->plugin_name, 'Manual Export', 'Manual Export', 'administrator', $this->plugin_name.'-export-csv',
            array( $this, 'displayPluginManualExportPage' )
        );
	}

	/**
	 * Get : list of regions
	 * 
	 * @return array $regions
	 */
	public function getRegions() {
		$regions = [];

		$terms = get_terms( array(
			'taxonomy' => 'charity_region',
			'hide_empty' => false,
		) );

		foreach ($terms as $term) {
			$keyname = str_replace(' ', '_', $term->name);
			$keyname = strtolower($keyname);
			$regions[$keyname] = $term->name;
		}
		
		return $regions;
	}

	/**
	 * Page : main page of this plugin
	 */
	public function displayPluginAdminDashboard() {
	    require_once 'partials/'.$this->plugin_name.'-admin-display.php';

        $admin_email = get_mailchimp_master_email();
        if (is_array($admin_email)) {
            $admin_email = implode(',', $admin_email);
        }
        $date_range = get_report_csv_date_range();
        $dev_mode = (get_mailchimp_master_dev_status()) ? 'Enabled' : 'Disabled';

        echo '<div class="pickup-box">';
        echo '  <p>The report will be sent to <span>' . $admin_email . '</span></p>';
        echo '  <p>The csv will be <span>' . $date_range . '</span> dates apart.</p>';
        echo '  <p>Debug mode is <span>' . $dev_mode . '</span>. Disable debug mode for production. When debugger mode is enabled, all mails will be sent to the admin emails list.</p>';
        echo '</div>';
    }

    /**
     * Page : settings page
     */
    public function displayPluginSettingsPage() {
        echo '<div class="pickup-box">';

        require_once 'partials/'.$this->plugin_name.'-settings-display.php';

		$filters = [];
        $filters['from'] = (isset($_GET['from'])) ? $_GET['from'] : '';
        $filters['to'] = (isset($_GET['to'])) ? $_GET['to'] : '';
		$filters['region'] = (isset($_GET['region'])) ? $_GET['region'] : '';

        echo '</div>';
    }

    /**
     * Page : manual export page
     */
    public function displayPluginManualExportPage() {
        echo '<div class="pickup-box">';

        require_once 'partials/'.$this->plugin_name.'-manual-export-display.php';

        $regions = $this->getRegions();
		array_unshift($regions, 'none');

        echo '<p>Date format should be in YYYY-mm-dd.</p>';

		echo '<input type="date" id="txt-from-date" placeholder="from-date" />';

		echo '<input type="date" id="txt-to-date" placeholder="to-date" />';

		echo '<select id="sel-charity-region">';
		foreach ($regions as $key=>$region) {
			if ($region == 'none') {
				$key = 'none';
			}
			echo '<option value="' . $key . '">' . $region . '</option>';
		}
		echo '</select>';

		echo '<a href="#" id="btn-export" class="btn-mailchimp">Export Report</a>';

		echo '<div class="mailchimp-result-area"></div>';

        echo '</div>';
    }

	public function registerAndBuildFields() {
        /**
         * First, we add_settings_section. This is necessary since all future settings must belong to one.
         * Second, add_settings_field
         * Third, register_setting
         */
        add_settings_section(
        // ID used to identify this section and with which to register options
            'mailchimp_master_general_section',
            // Title to be displayed on the administration page
            '',
            // Callback used to render the description of the section
            array( $this, 'mailchimp_master_display_general_account' ),
            // Page on which to add this section of options
            'mailchimp_master_general_settings'
        );

        unset($args);
        $args = array (
            'type'      => 'input',
            'subtype'   => 'text',
            'id'    => 'mailchimp_donate_admin_mail',
            'name'      => 'mailchimp_donate_admin_mail',
            'required' => 'true',
            'get_options_list' => '',
            'value_type'=>'normal',
            'wp_data' => 'option'
        );
        add_settings_field(
            'mailchimp_donate_admin_mail',
            'Admin Email',
            array( $this, 'mailchimp_master_render_settings_field' ),
            'mailchimp_master_general_settings',
            'mailchimp_master_general_section',
            $args
        );
        unset($args);
		$args = array (
            'type'      => 'input',
            'subtype'      => 'checkbox',
            'id'    => 'mailchimp_master_dev_mode_status',
            'name'      => 'mailchimp_master_dev_mode_status',
            'required' => 'false',
            'get_options_list' => '',
            'value_type'=>'normal',
            'wp_data' => 'option'
        );
        add_settings_field(
            'mailchimp_master_dev_mode_status',
            'Dev Mode',
            array( $this, 'mailchimp_master_render_settings_field' ),
            'mailchimp_master_general_settings',
            'mailchimp_master_general_section',
            $args
        );
        unset($args);
		$args = array (
            'type'      => 'input',
            'subtype'   => 'text',
            'id'    => 'mailchimp_donate_date_range',
            'name'      => 'mailchimp_donate_date_range',
            'required' => 'true',
            'get_options_list' => '',
            'value_type'=>'normal',
            'wp_data' => 'option'
        );
        add_settings_field(
            'mailchimp_donate_date_range',
            'Date Range',
            array( $this, 'mailchimp_master_render_settings_field' ),
            'mailchimp_master_general_settings',
            'mailchimp_master_general_section',
            $args
        );
        unset($args);

        // store fields
        $fields = ['mailchimp_donate_admin_mail', 'mailchimp_master_dev_mode_status', 'mailchimp_donate_date_range'];

        $regions = $this->getRegions();
        foreach ($regions as $key=>$region) {
            
            $field_name = 'mailchimp_donate_date_range_' . $key;
            echo $field_name; die;
            
            unset($args);
            $args = array (
                'type'      => 'input',
                'subtype'   => 'text',
                'id'    => $field_name,
                'name'      => $field_name,
                'required' => 'true',
                'get_options_list' => '',
                'value_type'=>'normal',
                'wp_data' => 'option'
            );
            add_settings_field(
                $field_name,
                'Region ' . $key,
                array( $this, 'mailchimp_master_render_settings_field' ),
                'mailchimp_master_general_settings',
                'mailchimp_master_general_section',
                $args
            );
            $fields[] = $field_name;
        }

        foreach ($fields as $field) {
            register_setting(
                'mailchimp_master_general_settings',
                $field
            );
        }

    }

	public function mailchimp_master_render_settings_field($args) {
        if($args['wp_data'] == 'option'){
            $wp_data_value = get_option($args['name']);
        } elseif($args['wp_data'] == 'post_meta'){
            $wp_data_value = get_post_meta($args['post_id'], $args['name'], true );
        }

        switch ($args['type']) {

            case 'input':
                $value = ($args['value_type'] == 'serialized') ? serialize($wp_data_value) : $wp_data_value;
                if($args['subtype'] != 'checkbox'){
                    $prependStart = (isset($args['prepend_value'])) ? '<div class="input-prepend"> <span class="add-on">'.$args['prepend_value'].'</span>' : '';
                    $prependEnd = (isset($args['prepend_value'])) ? '</div>' : '';
                    $step = (isset($args['step'])) ? 'step="'.$args['step'].'"' : '';
                    $min = (isset($args['min'])) ? 'min="'.$args['min'].'"' : '';
                    $max = (isset($args['max'])) ? 'max="'.$args['max'].'"' : '';
                    if(isset($args['disabled'])){
                        // hide the actual input bc if it was just a disabled input the informaiton saved in the database would be wrong - bc it would pass empty values and wipe the actual information
                        echo $prependStart.'<input type="'.$args['subtype'].'" id="'.$args['id'].'_disabled" '.$step.' '.$max.' '.$min.' name="'.$args['name'].'_disabled" size="40" disabled value="' . esc_attr($value) . '" /><input type="hidden" id="'.$args['id'].'" '.$step.' '.$max.' '.$min.' name="'.$args['name'].'" size="40" value="' . esc_attr($value) . '" />'.$prependEnd;
                    } else {
                        echo $prependStart.'<input type="'.$args['subtype'].'" id="'.$args['id'].'" "'.$args['required'].'" '.$step.' '.$max.' '.$min.' name="'.$args['name'].'" size="40" value="' . esc_attr($value) . '" />'.$prependEnd;
                    }
                    /*<input required="required" '.$disabled.' type="number" step="any" id="'.$this->twilio_donate.'_cost2" name="'.$this->twilio_donate.'_cost2" value="' . esc_attr( $cost ) . '" size="25" /><input type="hidden" id="'.$this->twilio_donate.'_cost" step="any" name="'.$this->twilio_donate.'_cost" value="' . esc_attr( $cost ) . '" />*/

                } else {
                    $checked = ($value) ? 'checked' : '';
                    echo '<input type="'.$args['subtype'].'" id="'.$args['id'].'" "'.$args['required'].'" name="'.$args['name'].'" size="40" value="1" '.$checked.' />';
                }
                break;
            case 'region_email':
                $value = ($args['value_type'] == 'serialized') ? serialize($wp_data_value) : $wp_data_value;
                echo '<input type="text" value="' . esc_attr($value) . '" />';
                break;
//            case 'checkbox':
//                echo '<input type="checkbox" id="subscribeNews" name="subscribe" value="newsletter">';
//                break;
            default:
                break;
        }
    }

}
