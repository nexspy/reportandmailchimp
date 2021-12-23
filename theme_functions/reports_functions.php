<?php

/**
 * 
 * Generate report for charity region (category) for given time period. If no time given, the date range
 * starts from tomorrow to two weeks.
 * 
 * Download Url : 
 *  - /wp-json/charity/region/reports
 * 
 * Query String : 
 *  - We can send parameters given below for filtering the result
 *  - Possible filters are "from", "to" and "region".
 *  - "from" - format is 2021-01-30
 *  - "region" - this is required
 *  - 
 *  - Example
 *  - ?from=2021-07-01&to=2021-08-30&region=Chicago%20Area
 * 
 */


/**
 * Get : api key used in cron job
 *  - used during making of cron job
 */
function get_report_generator_api_key() {
    $token = get_option('mailchimp_master_rest_api_token');
    return $token;
}

/**
 * Create : csv file and download
 */
function reports_create_csv($orders, $filename)
{
    // load csv libraries
    require_once(get_stylesheet_directory() . '/vendor/autoload.php');

    // load the CSV document from a string
    $csv = League\Csv\Writer::createFromString();

    // $header = ['id', 'first name', 'last name', 'address', 'postcode', 'phone', 'region', 'status', 'pickup date'];
    $header = ['day', 'total pickups'];

    // insert the header
    $csv->insertOne($header);

    // insert all the records
    $csv->insertAll($orders);

    $csv->output("$filename.csv"); //returns the CSV document as a string
    die;
}

/**
 * Create : csv file for donar list
 */
function reports_create_csv_for_donar($orders, $filename) {
    // load csv libraries
    require_once(get_stylesheet_directory() . '/vendor/autoload.php');

    // load the CSV document from a string
    $csv = League\Csv\Writer::createFromString();

    // $header = ['id', 'first name', 'last name', 'address', 'postcode', 'phone', 'region', 'status', 'pickup date'];
    $header = ['Name', 'Address', 'Phone Number', 'Email'];

    // insert the header
    $csv->insertOne($header);

    // insert all the records
    $csv->insertAll($orders);

    $csv->output("$filename.csv"); //returns the CSV document as a string
    die;
}

/**
 * Produce : date reanges
 * 
 * # Example 1
 * date_range("2014-01-01", "2014-01-20", "+1 day", "m/d/Y");
 *
 * # Example 2. you can use even time
 * date_range("01:00:00", "23:00:00", "+1 hour", "H:i:s");
 */
function date_range($first, $last, $step = '+1 day', $output_format = 'd/m/Y' ) {

    $dates = array();
    $current = strtotime($first);
    $last = strtotime($last);

    while( $current <= $last ) {

        $dates[] = date($output_format, $current);
        $current = strtotime($step, $current);
    }

    return $dates;
}

/**
 * Convert : WC_Order objects list to simple csv array
 * @param {array} $orders
 * @return {array} $list
 */
function reports_convert_order_to_csv_data($orders, $date_from, $date_to)
{    
    $list = [];

    foreach ($orders as $order) {
        $date = $order->get_meta('_billing_pickup_date');

        if (!isset($list[$date])) {
            $list[$date] = [];
        }
        
        $list[$date][] = [
            $order->get_id(),
            $date,
        ];
    }

    // get list of dates from given range
    $date_range = date_range($date_from, $date_to, "+1 day", "Y-m-d");
    
    $datelist = [];
    foreach ($date_range as $key => $current_date) {
        $total = 0;
        foreach ($list as $key2=>$item2) {
            if ($key2 == $current_date) {
                $total = count($item2);
            }
        }
        $datelist[] = [ $current_date, $total];
    }

    return $datelist;
}

/**
 * Get : list of orders for given region and date range
 * 
 * @param {string} $date_from
 * @param {string} $date_to
 * @param {string} $region
 * @param {string} $post_status
 * @return {array} $orders_loaded
 */
function reports_get_orders_from_region($date_from, $date_to, $region, $post_status)
{
    global $wpdb;

    // handle empty region with 'None' value
    if ($region == 'None') {
        $region = '';
    }

    $orders = $wpdb->get_results("SELECT * FROM $wpdb->posts 
        WHERE post_type = 'shop_order'
        AND post_status IN ('{$post_status}')
        AND post_date BETWEEN '{$date_from}  00:00:00' AND '{$date_to} 23:59:59'
    ");

    // Loop through each WC_Order object
    // check for region match ( if region is empty, take all orders in account )
    $orders_loaded = [];
    foreach ($orders as $order) {
        $id = $order->ID;
        $temp = new WC_Order($id);
        $temp_region = $temp->get_meta('_billing_charity_region');
        $temp_region = strip_tags($temp_region);
        $temp_pickup_date = $temp->get_meta('_billing_pickup_date');

        if (empty($region) && !empty($temp_pickup_date)) {
            $orders_loaded[] = $temp;
        } else if ($temp_region == $region && !empty($temp_pickup_date)) {
            $orders_loaded[] = $temp;
        }
    }

    return $orders_loaded;
}

/**
 * Get : orders for given product ID
 */
function get_orders_ids_by_product_id( $product_id, $date_from, $date_to, $order_status = array( 'wc-completed' ) ){
    global $wpdb;

    $results = $wpdb->get_col("
        SELECT order_items.order_id
        FROM {$wpdb->prefix}woocommerce_order_items as order_items
        LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta ON order_items.order_item_id = order_item_meta.order_item_id
        LEFT JOIN {$wpdb->posts} AS posts ON order_items.order_id = posts.ID
        WHERE posts.post_type = 'shop_order'
        AND posts.post_status IN ( '" . implode( "','", $order_status ) . "' )
        AND order_items.order_item_type = 'line_item'
        AND order_item_meta.meta_key = '_product_id'
        AND order_item_meta.meta_value = '$product_id'
        AND posts.post_date BETWEEN '{$date_from}  00:00:00' AND '{$date_to} 23:59:59'
    ");

    return $results;
}

/**
 * Get : list of donars in given date (month-year) and charity
 */
function reports_get_orders_for_charity($date_from, $date_to, $charity_id, $post_status)
{
    // handle empty region with 'None' value
    if ($charity_id == 'None') {
        $charity_id = '';
    }

    $orders = get_orders_ids_by_product_id($charity_id, $date_from, $date_to, $post_status);
    
    $orders_loaded = [];

    $number_list = [];

    // check if the order has the charity
    foreach ($orders as $id) {
        $order = new WC_Order($id);

        if ($order) {
            // get customer info
            $address = $order->get_billing_address_1();
            $name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
            $phone_number = $order->get_billing_phone();
            $email = $order->get_billing_email();

            // avoid duplicate phone number orders
            if (!in_array($phone_number, $number_list)) {
                $orders_loaded[] = [$name, $address, $phone_number, $email];
            }
        }        
    }

    return $orders_loaded;
}

/**
 * Get : status in string
 */
function reports_get_all_post_status($format='string') {
    $list_status = array('wc-processing', 'wc-completed', 'wc-pending', 'wc-on-hold');
    switch($format) {
        case 'array':
            return $list_status;
            break;
        case 'string':
        default:
            return implode("','", $list_status);
    }
}

/**
 * Validation : check if token given in the url is valid
 * 
 * @return {boolean}
 */
function check_rf_token_validation() {
    $token = get_report_generator_api_key();
    $token_str = (isset($_GET['token'])) ? $_GET['token'] : '';

    return (!empty($token_str) && $token_str == $token);
}

/**
 * Route : generata report and download using filters in query string
 */
function handle_route_generate_reports_region()
{
    global $wp_query;

    // token validation
    $token_is_valid = check_rf_token_validation();

    if ($token_is_valid) {

        // filters
        $region = (isset($_GET['region'])) ? $_GET['region'] : '';
        $regionMachineName = (isset($_GET['region_machine_name'])) ? $_GET['region_machine_name'] : '';

        $date_from = (isset($_GET['from'])) ? $_GET['from'] : '';
        $date_to = (isset($_GET['to'])) ? $_GET['to'] : '';
        $mail = (isset($_GET['mail'])) ? $_GET['mail'] : '';

        if (empty($date_from)) {
            $date_from = date('Y-m-d', strtotime("tomorrow"));
        }

        if (empty($date_to)) {
            $date_to = date('Y-m-d', strtotime($date_from . ' + 14 days'));
        }

        // send report as email to admin email
        if (!empty($mail)) {
            $receiver_mail = get_mailchimp_master_email();
        
            if (!empty($receiver_mail)) {
                if (is_array($receiver_mail)) {
                    // send all mails in list
                    foreach ($receiver_mail as $mail) {
                        send_order_report_as_email($mail, $region, $date_from, $date_to);
                    }
                } else {
                    // single mail
                    send_order_report_as_email($receiver_mail, $region, $date_from, $date_to);
                }
                
            }
        }

        $regionFile = (!empty($regionMachineName)) ? $regionMachineName . '-' : '';

        $post_status = reports_get_all_post_status();

        // get orders using filters
        $orders_loaded = reports_get_orders_from_region($date_from, $date_to, $region, $post_status);

        // convert to csv ready data
        $orders_loaded = reports_convert_order_to_csv_data($orders_loaded, $date_from, $date_to);

        // create file from date values
        $filename = 'report-charity-region-' . $regionFile . $date_from . '-' . $date_to;

        // report export and download
        reports_create_csv($orders_loaded, $filename);

        die;
    } else {
        $wp_query->set_404();
        status_header(404);
        get_template_part(404);
        exit();
    }
}

/**
 * Route : generata donar report and download using filters in query string
 */
function handle_route_generate_donars_report() {
    global $wp_query;

    // token validation
    $token_is_valid = check_rf_token_validation();

    if ($token_is_valid) {

        // filters
        $filter_charity = (isset($_GET['charity'])) ? $_GET['charity'] : '';
        $filter_date = (isset($_GET['date'])) ? $_GET['date'] : '';
        $filter_date_to = (isset($_GET['dateto'])) ? $_GET['dateto'] : '';

        // calculate dates for finding orders
        $exploded_date = explode('-', $filter_date);
        $year = $exploded_date[0];
        $month = $exploded_date[1];
        $total_days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $date_from = date('Y-m-d', strtotime($filter_date . '-1'));
        $date_to = date('Y-m-d', strtotime($filter_date_to . '-' . $total_days_in_month));

        if (!empty($filter_date_to) && $filter_date != $filter_date_to) {
            $exploded_date_to = explode('-', $filter_date_to);
            $year2 = $exploded_date_to[0];
            $month2 = $exploded_date_to[1];
            $total_days_in_month2 = cal_days_in_month(CAL_GREGORIAN, $month2, $year2);
            $date_to = date('Y-m-d', strtotime($filter_date_to . '-' . $total_days_in_month2));
        }

        $can_get_orders = (!empty($filter_charity) && !empty($filter_date) && !empty($date_from) && !empty($date_to));

        if ($can_get_orders) {
            $post_status = reports_get_all_post_status('array');

            $orders_loaded = reports_get_orders_for_charity($date_from, $date_to, $filter_charity, $post_status);

            $filename = 'report-donars-' . $filter_charity . '-' . $date_from . '-' . $date_to;
            
            reports_create_csv_for_donar($orders_loaded, $filename);
        }
    } else {
        $wp_query->set_404();
        status_header(404);
        get_template_part(404);
        exit();
    }
}

/**
 * Route : used in cron job, send report as email using mailchimp
 */
function handle_route_generate_reports_mailchimp()
{
    global $wp_query;
    $receiver_mail = '';
    $regions = getRegions();

    // token validation
    $token_is_valid = check_rf_token_validation();

    if ($token_is_valid) {

        foreach ($regions as $key=>$region) {
            $field_name = 'mailchimp_donate_region_email_' . $key;
            $receiver_mail = get_option($field_name);
            
            if (!empty($receiver_mail)) {
                // single mail
                send_order_report_as_email($receiver_mail, $key);
            }
        }
    
        // use the admin mail if region specific emails are not setup
        if (empty($receiver_mail)) {
            $receiver_mail = get_mailchimp_master_email();
        
            if (!empty($receiver_mail)) {
                if (is_array($receiver_mail)) {
                    // send all mails in list
                    foreach ($receiver_mail as $mail) {
                        send_order_report_as_email($mail);
                    }
                } else {
                    // single mail
                    send_order_report_as_email($receiver_mail);
                }
                
            }
        }

    } else {
        $wp_query->set_404();
        status_header(404);
        get_template_part(404);
        exit();
    }
}

/**
 * Route : used in cron for sending reports with list of donars for given month and charity
 *  - send 'charity' and 'date' in query string ($_GET)
 */
function handle_route_generate_donars_mail() {
    global $wp_query;

    // token validation
    $token_is_valid = check_rf_token_validation();

    if ($token_is_valid) {

        // filters
        $filter_charity = (isset($_GET['charity'])) ? $_GET['charity'] : '';
        // $filter_date = (isset($_GET['date'])) ? $_GET['date'] : '';
        $filter_date = date('Y-m');

        // $mails = ['adamrwinfield@gmail.com', 'Awinfield@crownedeagle.com'];
        $mails = ['famava2142@healteas.com'];
        foreach ($mails as $mail) {
            send_donar_report_as_email($mail, $filter_charity, $filter_date);
        }

    } else {
        $wp_query->set_404();
        status_header(404);
        get_template_part(404);
        exit();
    }
}

/**
 * Filter : send orders to backend
 */
function reports_all_charity_region_results($filters)
{
    // filters
    $date_from = "2021-01-20";
    $date_to = "2021-02-20";
    $region = "Detroit Area";
    $post_status = reports_get_all_post_status();

    // get orders
    $orders_loaded = reports_get_orders_from_region($date_from, $date_to, $region, $post_status);

    return $orders_loaded;
}

/**
 * * Route : generate report from given date filters from query string
 *  - add '/wp-json/' at start of the route
 */
add_action(
    'rest_api_init',
    function () {
        register_rest_route(
            'charity/region',
            '/reports',
            array(
                'methods' => 'GET',
                'callback' => 'handle_route_generate_reports_region',
            )
        );
    }
);

/**
 * * Route : generate report with given list of donars
 *  - add '/wp-json/' at start of the route
 */
add_action(
    'rest_api_init',
    function () {
        register_rest_route(
            'charity/donar',
            '/reports',
            array(
                'methods' => 'GET',
                'callback' => 'handle_route_generate_donars_report',
            )
        );
    }
);

/**
 * * Route : used in cron for sending reports with list of donars for given month and charity
 */
add_action(
    'rest_api_init',
    function () {
        register_rest_route(
            'charity/donar',
            '/mail',
            array(
                'methods' => 'GET',
                'callback' => 'handle_route_generate_donars_mail',
            )
        );
    }
);

/**
 * * Route : used in cron to send email with the download link
 *  - add '/wp-json/' at start of the route
 */
add_action(
    'rest_api_init',
    function () {
        register_rest_route(
            'charity/region',
            '/mailchimp',
            array(
                'methods' => 'GET',
                'callback' => 'handle_route_generate_reports_mailchimp',
            )
        );
    }
);

/**
 * Custom Filter : get list of orders
 */
add_filter('reports_all_charity_region', 'reports_all_charity_region_results');
