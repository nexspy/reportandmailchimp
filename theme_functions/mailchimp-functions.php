<?php

//woocommerce on order created
function action_woocommerce_order_created($order_id)
{
    $order = new WC_Order($order_id);
    

    $email = $order->get_billing_email();
    $order_data = $order->get_data(); // The Order data

    $order_billing_first_name = $order_data['billing']['first_name'];
    $order_billing_last_name = $order_data['billing']['last_name'];

    require_once(get_stylesheet_directory() . '/vendor/autoload.php');
    $mailchimp = new MailchimpTransactional\ApiClient();
    $mailchimp->setApiKey('ABCDEFGHIJKLMNOP'); //transactional mail api key
    /******send order confirmation mail **********/
    $emailTemplateConfirmation="donation-confirmation";
    $emailTemplateReminder="donation-reminder-24hrs";
    foreach ($order->get_items() as $item_id => $item) { 
        $product_name = $item->get_name();
        $product_id = $item->get_product_id();
        $product_image = get_the_post_thumbnail($product_id);
    }
	 
    if (!empty(get_field('confirmation_email', $product_id))) {
        $emailTemplateConfirmation = get_field('confirmation_email', $product_id);
    }

    if (!empty(get_field('reminder_email', $product_id))) {
        $emailTemplateReminder = get_field('reminder_email', $product_id);
    }

    $pickup_date=$order->get_meta('_billing_pickup_date');
    //remainder date is 1 day before pickup date
    $reminder_date=date('Y-m-d', strtotime('-1 day', strtotime($pickup_date)));
    $newDateFormat = date('l F jS, Y', strtotime($pickup_date));//prints Friday April 9th, 2021
    $pickup_date = $newDateFormat;
    $url = get_site_url();
    $order_cancel_link= $url."/cancel-order/".$order_id;
    //$order_cancel_link="https://donatestuffdev.wpengine.com/cancel-order/".$order_id;
    $response = $mailchimp->messages->sendTemplate([
        "template_name" => $emailTemplateConfirmation,
        "template_content" => [['sadasd']],
        "message" => [
            "text"=> "Pickup Confirmation",
            "subject"=> "Your Pickup is Successfully Scheduled for Pickup #$order_id",
            "from_email"=> sendFrom($emailTemplateConfirmation),
            "from_name"=> fromWhom($emailTemplateConfirmation),
            "to"=> [[
                "email"=> $email,
                "name" => $order_billing_first_name." ".$order_billing_last_name,
                "type" => "to"
            ]],
            "merge" => true,
            "global_merge_vars" =>[
                [
                'name' => 'SCHEDULED_DATE',
                'content' => $pickup_date,
                ],
                [
                    'name'=> 'ORDER_CANCEL_LINK',
                    'content' => $order_cancel_link,
                ],
                [
                    'name'=> 'PRODUCT_NAME',
                    'content' => $product_name,
                ],
                [
                    'name'=> 'PRODUCT_IMAGE',
                    'content' => $product_image,
                ]
            ]
        ]
    ]);
    //**************schedule reminder mail**************
    //send order Reminder mail
    $response = $mailchimp->messages->sendTemplate([
        "template_name" => $emailTemplateReminder,
        "template_content" => [['sadasd']],
        "message" => [
            "text"=> "Pickup Reminder",
            "subject"=> "Pickup Reminder: We Will Stop by Tomorrow!",
            "from_email"=> sendFrom($emailTemplateReminder),
            "from_name"=> fromWhom($emailTemplateReminder),
            "to"=> [[
                "email"=> $email,
                "name" => $order_billing_first_name." ".$order_billing_last_name,
                "type" => "to"
            ]],
            "merge" => true,
            "global_merge_vars" =>[
                [
                'name' => 'SCHEDULED_DATE',
                'content' => $pickup_date,
                ],
                [
                    'name'=> 'ORDER_CANCEL_LINK',
                    'content' => $order_cancel_link,
                ],
                [
                    'name'=> 'PRODUCT_NAME',
                    'content' => $product_name,
                ],
                [
                    'name'=> 'PRODUCT_IMAGE',
                    'content' => $product_image,
                ]
            ]
        ],
        "send_at" => $reminder_date."T17:34:22Z",
    ]);
}
add_action('woocommerce_thankyou', 'action_woocommerce_order_created', 111, 1);

//woocommerce on order status completed (Pickup Successfull)
function action_woocommerce_order_status_completed($order_id)
{     
    $emailTemplatePickupSuccessful="pickup-successful";
    $order = new WC_Order($order_id);
    foreach ($order->get_items() as $item_id => $item) {
        $product_name = $item->get_name();
        $product_id = $item->get_product_id();
        $product_image = get_the_post_thumbnail($product_id);
    }
    if (!empty(get_field('pickup_successful', $product_id))) {
        $emailTemplatePickupSuccessful = get_field('pickup_successful', $product_id);
    }
    $order_data = $order->get_data(); // The Order data
    $order_billing_first_name = $order_data['billing']['first_name'];
    $order_billing_last_name = $order_data['billing']['last_name'];

    $email = $order->get_billing_email();
    require_once(get_stylesheet_directory() . '/vendor/autoload.php');
    $mailchimp = new MailchimpTransactional\ApiClient();
    $mailchimp->setApiKey('ABCDEFGHIJKLMNOP'); //transactional mail api key
    $response = $mailchimp->messages->sendTemplate([
        "template_name" => $emailTemplatePickupSuccessful,
        "template_content" => [['sadasd']],
        "message" => [
            "text"=> "Pickup Successful",
            "subject"=> "Your donation was successfully picked up",
            "from_email"=> sendFrom($emailTemplatePickupSuccessful),
            "from_name"=> fromWhom($emailTemplatePickupSuccessful),
            "to"=> [[
                "email"=> $email,
                "name" => $order_billing_first_name." ".$order_billing_last_name,
                "type" => "to"
            ]],
            "merge" => true,
            "global_merge_vars" =>[
                [
                    'name'=> 'PRODUCT_NAME',
                    'content' => $product_name,
                ],
                [
                    'name'=> 'PRODUCT_IMAGE',
                    'content' => $product_image,
                ]
            ]
        ],
    ]);
};
add_action('woocommerce_order_status_completed', 'action_woocommerce_order_status_completed', 10, 1);

// woocommerce on order cancelled (Pickup Cancelled)
function action_woocommerce_order_status_cancelled($order_id)
{
    $emailTemplatePickupCancelled="donation-pickup-unsuccessful";
    $order = new WC_Order($order_id);
    foreach ($order->get_items() as $item_id => $item) {
        $product_id = $item->get_product_id();
    }
    if (!empty(get_field('pickup_cancelled', $product_id))) {
        $emailTemplatePickupCancelled = get_field('pickup_cancelled', $product_id);
    }
    $email = $order->get_billing_email();
    $order_data = $order->get_data(); // The Order data
    $order_billing_first_name = $order_data['billing']['first_name'];
    $order_billing_last_name = $order_data['billing']['last_name'];
    //send order cancelled mail
    require_once(get_stylesheet_directory() . '/vendor/autoload.php');
    $mailchimp = new MailchimpTransactional\ApiClient();
    $mailchimp->setApiKey('ABCDEFGHIJKLMNOP');
    $response = $mailchimp->messages->sendTemplate([
        "template_name" => $emailTemplatePickupCancelled,
        "template_content" => [['sadasd']],
        "message" => [
            "text"=> "Pickup Cancelled",
            "subject"=> "Pickup Cancelled",
            "from_email"=> sendFrom($emailTemplatePickupCancelled),
            "from_name"=> fromWhom($emailTemplatePickupCancelled),
            "to"=> [[
                "email"=> $email,
                "name" => $order_billing_first_name." ".$order_billing_last_name,
                "type" => "to"
            ]]
        ],
    ]);
};
add_action('woocommerce_order_status_cancelled', 'action_woocommerce_order_status_cancelled', 10, 1);

//woocommerce order failed (Pickup  Unsuccessful)
function action_woocommerce_order_status_failed($order_id)
{
    $emailTemplatePickupUnsuccessful="donation-pickup-unsuccessful";
    $order = new WC_Order($order_id);

    foreach ($order->get_items() as $item_id => $item) {
        $product_id = $item->get_product_id();
    }
    if (!empty(get_field('pickup_unsuccessful', $product_id))) {
        $emailTemplatePickupUnsuccessful = get_field('pickup_unsuccessful', $product_id);
    }

    $email = $order->get_billing_email();
    $order_data = $order->get_data(); // The Order data
    $order_billing_first_name = $order_data['billing']['first_name'];
    $order_billing_last_name = $order_data['billing']['last_name'];

    //send order failed mail
    require_once(get_stylesheet_directory() . '/vendor/autoload.php');
    $mailchimp = new MailchimpTransactional\ApiClient();
    $mailchimp->setApiKey('ABCDEFGHIJKLMNOP');
    $response = $mailchimp->messages->sendTemplate([
        "template_name" => $emailTemplatePickupUnsuccessful,
        "template_content" => [['sadasd']],
        "message" => [
            "text"=> "Order Failed",
            "subject"=> "Pickup Unsuccessful, Your items could not be collected",
            "from_email"=> sendFrom($emailTemplatePickupUnsuccessful),
            "from_name"=> fromWhom($emailTemplatePickupUnsuccessful),
            "to"=> [[
                "email"=> $email,
                "name" => $order_billing_first_name." ".$order_billing_last_name,
                "type" => "to"
            ]]
        ],
    ]);
};
add_action('woocommerce_order_status_failed', 'action_woocommerce_order_status_failed', 10, 1);

// for cancelling order
$request_cancel_order=$_SERVER['REQUEST_URI'];
$request_values = explode("/", $request_cancel_order);
$url = get_site_url();
if ($request_values[1]=='cancel-order') {
    function action_woocommerce_before_main_content(){
        $request_cancel_order=$_SERVER['REQUEST_URI'];
        $request_values = explode("/", $request_cancel_order);
        $order_id_cancel=$request_values[2];
        $order = new WC_Order($order_id_cancel);
        $order->update_status('cancelled');
        //echo "<script>window.location.href='/order-cancelled-successfully/';</script>";
        header("Location: ".$url."/order-cancelled-successfully/");
    };
    add_action('init', 'action_woocommerce_before_main_content', 10, 2);
}

// Function to check string ($haystack, $needle)
function startsWith ($string, $startString) {
    $len = strlen($startString);
    return (substr($string, 0, $len) === $startString);
}

// Function to determine from email address
function sendFrom($template) {
    $fromEmail = "info@donatestuff.com";

    if($template) {
        if (startsWith($template, 'sr-')) { // SimpleRecycling emails begin with 'sr-'
            $fromEmail = 'info@simplerecycling.com';
        }
    }
    return $fromEmail;
}

// Function to determine from email address
function fromWhom($template) {
    $fromWhom = "The DonateStuff.com Team";

    if($template) {
        if (startsWith($template, 'sr-')) { // SimpleRecycling emails begin with 'sr-'
            $fromWhom = 'The SimpleRecycling.com Team';
        }
    }elseif($template) {
        if (startsWith($template, 'dav-')) { // DAV emails begin with 'dav-'
            $fromWhom = 'The DAV Team';
        }
    }

    return $fromWhom;
}

?>