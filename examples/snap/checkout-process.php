<?php
// This is just for very basic implementation reference, in production, you should validate the incoming requests and implement your backend more securely.
// Please refer to this docs for snap popup:
// https://docs.midtrans.com/en/snap/integration-guide?id=integration-steps-overview

namespace Midtrans;

require_once dirname(__FILE__) . '/../../midtrans/Midtrans.php';
// Set Your server key
// can find in Merchant Portal -> Settings -> Access keys
Config::$serverKey = 'SB-Mid-server-d4Dv5k-5_6XU-NFnj33fgYhd';
Config::$clientKey = 'SB-Mid-client-ZGX5Z9E7g6GfWdNR';

// non-rel
printExampleWarningMessage();

// Uncomment for production environment
// Config::$isProduction = true;

// Enable sanitization
Config::$isSanitized = true;

// Enable 3D-Secure
Config::$is3ds = true;

// Uncomment for append and override notification URL
// Config::$appendNotifUrl = "https://example.com";
// Config::$overrideNotifUrl = "https://example.com";

$orderId = "62c943c4-beb4-4e99-b1fe-yuhb2309754";

// Required
$transaction_details = array(
    'order_id' => $orderId,
    'gross_amount' => 94000, // no decimal allowed for creditcard
);

// Optional
$item1_details = array(
    'id' => 'a1',
    'price' => 18000,
    'quantity' => 3,
    'name' => "Apple"
);

// Optional
$item2_details = array(
    'id' => 'a2',
    'price' => 20000,
    'quantity' => 2,
    'name' => "Orange"
);

// Optional
$item_details = array ($item1_details, $item2_details);

// Optional
// $billing_address = array(
//     'first_name'    => "Andri",
//     'last_name'     => "Litani",
//     'address'       => "Mangga 20",
//     'city'          => "Jakarta",
//     'postal_code'   => "16602",
//     'phone'         => "081122334455",
//     'country_code'  => 'IDN'
// );

// // Optional
// $shipping_address = array(
//     'first_name'    => "Obet",
//     'last_name'     => "Supriadi",
//     'address'       => "Manggis 90",
//     'city'          => "Jakarta",
//     'postal_code'   => "16601",
//     'phone'         => "08113366345",
//     'country_code'  => 'IDN'
// );

// // Optional
// $customer_details = array(
//     'first_name'    => "Andri",
//     'last_name'     => "Litani",
//     'email'         => "andri@litani.com",
//     'phone'         => "081122334455",
//     'billing_address'  => $billing_address,
//     'shipping_address' => $shipping_address
// );

// Optional, remove this to display all available payment methods
$enable_payments = array('credit_card','cimb_clicks','mandiri_clickpay','echannel');

// Fill transaction details
$transaction = array(
    // 'enabled_payments' => $enable_payments,
    'transaction_details' => $transaction_details,
    // 'customer_details' => $customer_details,
    'item_details' => $item_details,
);

$snap_token = '';
try {
    $snap_token = Snap::getSnapToken($transaction);
}
catch (\Exception $e) {
    echo $e->getMessage();
    getTransaction($orderId);
}

echo "snapToken = ".$snap_token;

function printExampleWarningMessage() {
    if (strpos(Config::$serverKey, 'your ') != false ) {
        echo "<code>";
        echo "<h4>Please set your server key from sandbox</h4>";
        echo "In file: " . __FILE__;
        echo "<br>";
        echo "<br>";
        echo htmlspecialchars('Config::$serverKey = \'<your server key>\';');
        die();
    } 
}

function getTransaction($order_id){
    //inisialisasi
    $cURL = curl_init();

    //set url
    $urlDest = "https://api.sandbox.midtrans.com/";
    $extendUrl = "v2/".$order_id."/status";
    $urlDest = $urlDest.$extendUrl;
    curl_setopt($cURL, CURLOPT_URL, $urlDest);

    //set header
    curl_setopt($cURL, CURLOPT_HTTPHEADER, array(
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: Basic U0ItTWlkLXNlcnZlci1kNER2NWstNV82WFUtTkZuajMzZmdZaGQ6'
          )
    );

    //return data as stringg
    curl_setopt($cURL, CURLOPT_RETURNTRANSFER, 1);

    //Menda[atkan putput
    $output = curl_exec($cURL);

    //close
    curl_close($cURL);

    //test hasilnya
    // echo $output;

    $data_json = json_decode($output, true);
    $pay_status = $data_json["status_code"];
    // var_dump($data_json);

    echo "\n\nStatus Pembayran : ".$pay_status."\n\n";

    if($pay_status == 201 or $pay_status == "201"){
        getTransaction($order_id);
    }
}

?>

<!DOCTYPE html>
<html>
    <body>
        <button id="pay-button">Pay!</button>
        <pre><div id="result-json">JSON result will appear here after payment:<br></div></pre> 

        <!-- TODO: Remove ".sandbox" from script src URL for production environment. Also input your client key in "data-client-key" -->
        <script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="<?php echo Config::$clientKey;?>"></script>
        <script type="text/javascript">
            document.getElementById('pay-button').onclick = function(){
                // SnapToken acquired from previous step
                snap.pay('<?php echo $snap_token?>', {
                    // Optional
                    onSuccess: function(result){
                        /* You may add your own js here, this is just example */ document.getElementById('result-json').innerHTML += JSON.stringify(result, null, 2);
                       
                        var url = 'https://5192-112-215-154-234.ngrok.io/ServAR/examples/snap/checkout-process.php';
                        window.location.replace(url);
                    },
                    // Optional
                    onPending: function(result){
                        /* You may add your own js here, this is just example */ document.getElementById('result-json').innerHTML += JSON.stringify(result, null, 2);

                        var url = 'https://5192-112-215-154-234.ngrok.io/ServAR/examples/snap/checkout-process.php';
                        // var url = 'http://localhost:8080/ServAR/examples/snap/checkout-process.php';
                        window.location.replace(url);
                    },
                    // Optional
                    onError: function(result){
                        /* You may add your own js here, this is just example */ document.getElementById('result-json').innerHTML += JSON.stringify(result, null, 2);

                        var url = 'https://5192-112-215-154-234.ngrok.io/ServAR/examples/snap/checkout-process.php';
                        // var url = 'http://localhost:8080/ServAR/examples/snap/checkout-process.php';
                        window.location.replace(url);
                    }
                });
            };
        </script>
    </body>
</html>
