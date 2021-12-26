<?php 


namespace Midtrans;

require 'config.php';
require_once dirname(__FILE__) . '/midtrans/Midtrans.php';
// Set Your server key
// can find in Merchant Portal -> Settings -> Access keys
$CLIENT_KEY = "SB-Mid-client-ZGX5Z9E7g6GfWdNR";
$SERVER_KEY = "SB-Mid-server-d4Dv5k-5_6XU-NFnj33fgYhd";

$snap_token  = "";
Config::$serverKey = $SERVER_KEY;
Config::$clientKey = $CLIENT_KEY;

// non-relevant function only used for demo/example purpose
printExampleWarningMessage();

// Enable sanitization
Config::$isSanitized = true;

// Enable 3D-Secure
Config::$is3ds = true;

$trx_id = "";
$count_in_product = array();
$product_view = array();
$checkout = -1;
$pay_state = "";
$loading = false;
$loop = 0;


if (isset($_SERVER['HTTP_ORIGIN'])) {
    // should do a check here to match $_SERVER['HTTP_ORIGIN'] to a
    // whitelist of safe domains
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');    // cache for 1 day
}

function update_transaction($trx_id, $change_cost, $connect){

	$query_select = "SELECT * FROM loginSys_trx_data WHERE trx_code = '$trx_id'";
	$Q = mysqli_query($connect, $query_select);
	$last_trx = array();
	while($fetch = mysqli_fetch_assoc($Q)){
		$last_trx[] = $fetch;
	}

	$new_cost = $last_trx[0]['stuff_cost'] + $change_cost;

	$query = "UPDATE loginSys_trx_data SET stuff_cost='$new_cost',total_price='$new_cost' WHERE trx_code = '$trx_id'";
	$Q = mysqli_query($connect, $query);
	if(mysqli_affected_rows($connect) > 0){
		return "success";
	}else{
		return "failed";
	}
}

function min_cart($ID_products, $connect, $trx_id){
	//ambil ID setiap cartnya
	$status_del = "failed";
	$query_select = "SELECT * FROM sellerSide_selling WHERE cart_ordered_id IN (SELECT id FROM sellerSide_cart WHERE stuff_id = '$ID_products') AND trx_id = '$trx_id'";
	$Q = mysqli_query($connect, $query_select);
	$arr_cart_selling = array();
	while($fetch = mysqli_fetch_assoc($Q)){
		$arr_cart_selling[] = $fetch;
	}
	//proses menghapus hanya 1 item;
	//hapus data selling dulu
	$ID_cart_for_del_sell = $arr_cart_selling[0]['id'];
	$ID_cart_for_del_cart = $arr_cart_selling[0]['cart_ordered_id'];
	
	$query_1 = "DELETE FROM sellerSide_selling WHERE id = '$ID_cart_for_del_sell'";
	$query_2 = "DELETE FROM sellerSide_cart WHERE id = '$ID_cart_for_del_cart'";
	// var_dump($query_2);
	$Q1 = mysqli_query($connect, $query_1);
	// var_dump($query_1);
	// var_dump(mysqli_affected_rows($connect));
	if(mysqli_affected_rows($connect) > 0){
		// var_dump("masuk hapus");
		$Q2 = mysqli_query($connect, $query_2);
		if(mysqli_affected_rows($connect) > 0){
			$status_del = "success";
		}
	}
	//update data transaksi
	if($status_del == "success"){
		$product = array();
		$query = "SELECT price FROM sellerSide_stuff WHERE id = '$ID_products'";
		$Q = mysqli_query($connect, $query);
		while($fetch = mysqli_fetch_assoc($Q)){
			$product[] = $fetch;
		}
		
		$status_del = update_transaction($trx_id, -1*((int)$product[0]['price']), $connect);
	}
	
}

function add_cart($ID_products, $connect, $trx_id){
	$date = date("Y-m-d");

	$query_select = "SELECT * FROM sellerSide_selling WHERE cart_ordered_id IN (SELECT id FROM sellerSide_cart WHERE stuff_id = '$ID_products') AND trx_id = '$trx_id'";
	$Q = mysqli_query($connect, $query_select);
	$arr_cart = array();
	while($fetch = mysqli_fetch_assoc($Q)){
		$arr_cart[] = $fetch;
	}

	//tembahkan data ke cart dulu
	$query = "INSERT INTO sellerSide_cart(id, date, state_buy, state_order, count, stuff_id, buyer_id, comment) VALUES (0, '$date', 'non', 'ordered', 1, '$ID_products', 3, '')";
	$Q = mysqli_query($connect, $query);
	if($Q){
		//tambhakan ke selling
		$inserted = mysqli_insert_id($connect);
		$query = "INSERT INTO sellerSide_selling(id, date, pay_value, count, ship_cost, pay_method, buyer_id, cart_ordered_id, trx_id) VALUES (0,'$date',0,1,0,'transfer',3,'$inserted','$trx_id')";
		$Q = mysqli_query($connect, $query);
		if($Q){
			//update data transaksi
			$product = array();
			$query = "SELECT price FROM sellerSide_stuff WHERE id = '$ID_products'";
			$Q = mysqli_query($connect, $query);
			while($fetch = mysqli_fetch_assoc($Q)){
				$product[] = $fetch;
			}
			
			$status = update_transaction($trx_id, ((int)$product[0]['price']), $connect);
		}
	}
	// $url = 'http://localhost:8080/ServAR/checkout_system.php?trx_id='.$trx_id;
	// echo '<script type="text/JavaScript"> window.location.replace("'.$url.'"); </script>';
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

    // echo "\n\nStatus Pembayran : ".$pay_status."\n\n";
    // $loop += 1;
    if($pay_status == 201 or $pay_status == "201"){
    // 	if($loop == 0){
    // 		echo '<div class="alert alert-dark container">
				//   <strong>Pending!</strong> Pembayaran ditunda / menunggu pembayaran.
				// </div>';
    // 	}
        getTransaction($order_id);
    }else{
    	$pay_state = $data_json["status_code"];
    	if($pay_state == "200"){
    		echo '<div class="alert alert-success container">
				  <strong>Success !</strong> Pembayaran sukses dilakukan.
				</div>';
			}else{
				echo '<div class="alert alert-danger container">
				  <strong>Gagal !</strong> Pembayaran gagal, ada masalah teknis.
				</div>';
			}
    	// echo $pay_state;
    }
}

if (isset($_GET['trx_id'])){
	
	//ambil data cart terkait trx id ini
	$trx_id = $_GET['trx_id'];
	$query = "SELECT cart_ordered_id FROM sellerSide_selling WHERE trx_id = '$trx_id'";
	$Q = mysqli_query($connect, $query);
	$ID_selling = array();
	while($fetch = mysqli_fetch_assoc($Q)){
		$ID_selling[] = $fetch;
	}

	$cart_data = array();

	for($i=0; $i<count($ID_selling); $i++){
		$sell_id = $ID_selling[$i]['cart_ordered_id'];
		$query = "SELECT * FROM sellerSide_cart WHERE id = '$sell_id'";
		$Q = mysqli_query($connect, $query);
		
		if($Q){
			while($fetch = mysqli_fetch_assoc($Q)){
				$cart_data[] = $fetch;
			}
		}
	}

	// var_dump($cart_data);
	//prepare data untuk view html

	$ID_products = array();
	$index = 0;
	for ($i=0; $i<count($cart_data); $i++){
		if($i==0){
			$checkout = 0;
			array_push($ID_products, $cart_data[$i]['stuff_id']);
			$query = "SELECT * FROM sellerSide_stuff WHERE id = '$ID_products[$index]'";
			$Q = mysqli_query($connect, $query);
			while($fetch = mysqli_fetch_assoc($Q)){
				$product_view [] = $fetch;
			}
			//hitung jumlah beli
			$count = 0;
			for ($j=0; $j<count($cart_data); $j++){
				if($ID_products[$index] == $cart_data[$j]['stuff_id']){
					$count += 1;
				}
			}
			array_push($count_in_product, $count);
		}else{
			$same = 0;
			for($j=0; $j<count($ID_products); $j++){
				if($cart_data[$i]['stuff_id'] == $ID_products[$j]){
					$same += 1;
				}
			}
			if($same==0){
				$index += 1;
				array_push($ID_products, $cart_data[$i]['stuff_id']);
				$query = "SELECT * FROM sellerSide_stuff WHERE id = '$ID_products[$index]'";
				$Q = mysqli_query($connect, $query);
				while($fetch = mysqli_fetch_assoc($Q)){
					$product_view [] = $fetch;
				}
				//hitung jumlah beli
				$count = 0;
				for ($j=0; $j<count($cart_data); $j++){
					if($ID_products[$index] == $cart_data[$j]['stuff_id']){
						$count += 1;
					}
				}
				array_push($count_in_product, $count);
			}
		}
	}

	if(isset($_GET['min'])){
		min_cart($_GET['min'], $connect, $trx_id);
		$url = 'https://serv-ar-restapi.herokuapp.com/checkout_system.php?trx_id='.$trx_id;
		echo '<script type="text/JavaScript"> window.location.replace("'.$url.'"); </script>';
	}

	else if (isset($_GET['plus'])){
		add_cart($_GET['plus'], $connect, $trx_id);
		$url = 'https://serv-ar-restapi.herokuapp.com/checkout_system.php?trx_id='.$trx_id;
		echo '<script type="text/JavaScript"> window.location.replace("'.$url.'"); </script>';
	}

	else if (isset($_GET['checkout'])){
		//proses register data ke midtrans
		$checkout = 1;
		$query_select = "SELECT * FROM loginSys_trx_data WHERE trx_code = '$trx_id'";
		$Q = mysqli_query($connect, $query_select);
		$last_trx = array();
		while($fetch = mysqli_fetch_assoc($Q)){
			$last_trx[] = $fetch;
		}
		$price = $last_trx[0]['total_price'];
		// $trx_id = $last_trx[0]['trx_code'];

		$transaction_details = array(
		    'order_id' => $trx_id,
		    'gross_amount' => $price, // no decimal allowed for creditcard
		);

		$transaction = array(
		    // 'enabled_payments' => $enable_payments,
		    'transaction_details' => $transaction_details,
		    // 'customer_details' => $customer_details,
		    // 'item_details' => $item_details,
		);

		$loading = true;
		
		try {
		    $snap_token = Snap::getSnapToken($transaction);
		}
		catch (\Exception $e) {
		    // echo $e->getMessage();
		    getTransaction($trx_id);
		    $checkout = 2;
		    
		}

		$loading = false;
	}


	if(isset($_POST['cmdsbm'])){

		$id_product = $_POST['id_product'];
		$comment = htmlspecialchars($_POST['comment']);
		$all_comment = array();

		//cari id tabel cart  dengan comment kosong
		$query = "SELECT * FROM sellerSide_cart WHERE stuff_id = '$id_product' AND comment = ''";
		$Q = mysqli_query($connect, $query);
		while($fetch = mysqli_fetch_assoc($Q)){
			$all_comment[] = $fetch;
		} 

		if(count($all_comment) > 0){
			$f_id = $all_comment[0]['id'];
			$query = "UPDATE sellerSide_cart SET comment='$comment' WHERE id = '$f_id'";
			mysqli_query($connect, $query);
		}

	}else if (isset($_POST['review'])){

		$id_product = $_POST['id_product'];
		$review_val = $_POST['review'];

		//insert data dulu ke dalam tabel
		$query = "INSERT INTO sellerSide_rating_data(id, value, stuff_id) VALUES (0,'$review_val','$id_product')";
		$Q = mysqli_query($connect, $query);
		if($Q){
			//hitung rat  rata nilai semunayanya
			$query = "SELECT * FROM sellerSide_rating_data WHERE stuff_id = '$id_product'";
			$Q = mysqli_query($connect, $query);
			$all_review = array();
			while ($fetch = mysqli_fetch_assoc($Q)) {
				// code...
				$all_review[] = $fetch;
			}

			$sum_review = 0;
			for ($i=0; $i < count($all_review); $i++) { 
				// code...
				$sum_review += (int)$all_review[$i]['value'];
			}

			$avg_review = round($sum_review/count($all_review));

			//update data rating stuff
			$query = "UPDATE sellerSide_stuff SET quality='$avg_review' WHERE id = '$id_product'";
			$Q = mysqli_query($connect, $query);
		}		

	}

}
	// var_dump($product_view);
	// var_dump($count_in_product)


// echo "snapToken = ".$snap_token;

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

 ?>

 <!DOCTYPE html>
 <html>
 <head>
 	<meta charset="utf-8">
 	<title>ServAR | Web Pembayran</title>
 	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
 	<script type="text/javascript"
            src="https://app.sandbox.midtrans.com/snap/snap.js"
            data-client-key="<?php echo $CLIENT_KEY ?>"></script>

 </head>
 <body>

<div class="container justify-content-center text-center mt-5">
 		<h5>Periksa Belanjaan Kamu Sebelum Di Checkout !!!</h5>


<?php 

if($trx_id != ""){

	for ($i=0; $i<count($product_view); $i++) {

	?>
 		<div class="card mb-3 mt-2" style="width: 100%;">
			<div class="row g-0" style="flex-wrap:unset;">
				
				<div class="col-md-8">
				    <div class="card-body">
				     <h5 class="card-title"><?php echo $product_view[$i]["name"] ?></h5>
				     <div class="card-text">Rp.<?php echo $product_view[$i]["price"] ?>,00</div>
				     <div class="card-text">Jumlah : <?php echo $count_in_product[$i] ?> produk</div>
				      	
				      <?php if($checkout == 0) { ?>	

				      	<a type="submit" name="cart" class="btn btn-primary mt-5" <?php echo "href='?trx_id=".$trx_id."&plus=".$product_view[$i]['id']."'" ?>>+</a>
				        <a type="submit" name="cart" class="btn btn-danger mt-5"<?php echo "href='?trx_id=".$trx_id."&min=".$product_view[$i]['id']."'" ?>>-</a>
				     <?php } else if ($checkout == 2) { ?>

				     		<div class="d-flex justify-content-center">
								
				     			<form class="me-3 mt-2" method="post">
									<div class="form-floating">
									  <input type="hidden" name="id_product" value="<?php echo $product_view[$i]['id'] ?>">
									  <textarea class="form-control" placeholder="Leave a comment here" id="floatingTextarea2" name="comment" style="height: 100px"></textarea>
									  <label for="floatingTextarea2">Comments</label>
									</div>
									<button type="submit" name="cmdsbm" class="btn btn-danger mt-2">Kirim</button>
								</form>

								<form method="post">
									
									<h5>Rating : </h5>
									<input type="hidden" name="id_product" value="<?php echo $product_view[$i]['id'] ?>">
									<button type="submit" name="review" value="1" class="btn btn-outline-warning">1</button>
									<button type="submit" name="review" value="2" class="btn btn-outline-warning">2</button>
									<button type="submit" name="review" value="3" class="btn btn-outline-warning">3</button>
									<button type="submit" name="review" value="4" class="btn btn-outline-warning">4</button>
									<button type="submit" name="review" value="5" class="btn btn-outline-warning">5</button>

								</form>
							</div>

				    <?php } ?>

				     </div>
				 </div>
			</div>
		</div>
 	
 <?php 

 	}
 }	
  
  ?>


<?php if($loading == false){ ?>
	<?php if ($checkout == 0){?>
		<input type='hidden' id='snap_token' value="<?php echo $snap_token ?>"/>
	  
	  <a type="submit" name="checkout" class="btn btn-danger" <?php echo "href='?trx_id=".$trx_id."&checkout=yes'" ?> >Checkout Sekarang</a>

	<?php }else if ($checkout == 1){ ?>

	  <button id="pay-button" class="btn btn-danger">Bayar Sekarang</button>

	<?php } else{ ?>

		<h4>ID Transaksi : <?php echo $trx_id ?></h4>
		<h3 id="pay-statuses">Terimakasih Telah Berbelanja !!! :)</h3>

	<?php } ?>
<?php } else if ($loading == true) { ?>

	<h3>Harap Tunggu ...</h3>

<?php } ?>


  </div>

 	
 </body>

	<script type="text/javascript">
			
			var url = 'https://serv-ar-restapi.herokuapp.com/checkout_system.php?trx_id='+'<?php echo $trx_id ?>'+'&checkout=yes';
            console.log(url);
            document.getElementById('pay-button').onclick = function(){
                // SnapToken acquired from previous step
                snap.pay('<?php echo $snap_token?>', {
                    // Optional
                    onSuccess: function(result){
                        // /* You may add your own js here, this is just example */ document.getElementById('result-json').innerHTML += JSON.stringify(result, null, 2);
                       
                        // var url = 'https://5192-112-215-154-234.ngrok.io/ServAR/examples/snap/checkout-process.php';
                        var url = 'https://serv-ar-restapi.herokuapp.com/checkout_system.php?trx_id='+'<?php echo $trx_id ?>'+'&checkout=yes';
                        console.log(url);
                        window.location.replace(url);
                    },
                    // Optional
                    onPending: function(result){
                        // /* You may add your own js here, this is just example */ document.getElementById('result-json').innerHTML += JSON.stringify(result, null, 2);

                        // var url = 'https://5192-112-215-154-234.ngrok.io/ServAR/examples/snap/checkout-process.php';
                        var url = 'https://serv-ar-restapi.herokuapp.com/checkout_system.php?trx_id='+'<?php echo $trx_id ?>'+'&checkout=yes';
                        console.log(url);
                        window.location.replace(url);
                    },
                    // Optional
                    onError: function(result){
                        // /* You may add your own js here, this is just example */ document.getElementById('result-json').innerHTML += JSON.stringify(result, null, 2);

                        // var url = 'https://5192-112-215-154-234.ngrok.io/ServAR/examples/snap/checkout-process.php';
                        // var url = 'http://localhost:8080/ServAR/examples/snap/checkout-process.php';
                        var url = 'https://serv-ar-restapi.herokuapp.com/checkout_system.php?trx_id='+'<?php echo $trx_id ?>'+'&checkout=yes';
                        console.log(url);
                        window.location.replace(url);
                    }
                });
            };
        </script>


 <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js" integrity="sha384-7+zCNj/IqJ95wo16oMtfsKbZ9ccEh31eOz1HGyDuCQ6wgnyJNSYdrPa03rtR1zdB" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.min.js" integrity="sha384-QJHtvGhmr9XOIpI6YVutG+2QOK9T+ZnN4kzFN1RtK3zEFEIsxhlmWl5/YESvpZ13" crossorigin="anonymous"></script>

 </html>