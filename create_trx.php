<?php 

require 'config.php';
require 'create_id_trx.php';

if (isset($_SERVER['HTTP_ORIGIN'])) {
    // should do a check here to match $_SERVER['HTTP_ORIGIN'] to a
    // whitelist of safe domains
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');    // cache for 1 day
}

if (isset($_GET['buy'])){
	if(isset($_GET['id']) and isset($_GET['sum'])){
		$arr_products = $_GET['id'];
		$arr_sum_buy = $_GET['sum'];
		// var_dump($arr_products);
		//checking data tersedia atau tidak
		$state = array();
		$arr_get_products = array();
		if(count($arr_products) == count($arr_sum_buy)){
			for ($i=0; $i<count($arr_products); $i++){
				$id = $arr_products[$i];
				$query = "SELECT * FROM sellerSide_stuff WHERE id = '$id'";
				$Q = mysqli_query($connect, $query);
				// var_dump(mysqli_fetch_assoc($Q));
				$fetch = mysqli_fetch_assoc($Q);
				if($fetch != NULL){
					array_push($state, true);
				}else{
					array_push($state, false);
				}

				array_push($arr_get_products, $fetch);
			}
		}else{
			array_push($state, false);
		}
		// var_dump($state);
		//mengambil data untuk prepare ke DB
		//check data sudah punya atau belum

		$state_trx_id = false;
		$trx_id = "";

		do{
			$trx_id = create_id();
			$query = "SELECT * FROM sellerSide_selling WHERE trx_id = '$trx_id'";
			$Q = mysqli_query($connect, $query);
			while($fetch = mysqli_fetch_assoc($Q)){
				if($fetch != NULL){
					$state_trx_id = true;
				}
			}
		}while($state_trx_id == true);
		$date = date("Y-m-d");
		$stuff_cost = 0;

		// var_dump($arr_get_products);
		// var_dump($state);
		$inserted_cart = array();
		for($i=0; $i<count($state); $i++){
			if($state[$i] == true){
				//insert data ke DB cart
				$product_id = $arr_products[$i];
				// echo("Data produk id = ".$product_id);
				$product_cost = $arr_get_products[$i]['price'];
				//menambhakan data beli dalam 1 produk berdasasrkan jumlah beli
				$status_selling_add = 0;
				for($j=0; $j<$arr_sum_buy[$i]; $j++){
					$query = "INSERT INTO sellerSide_cart(id, date, state_buy, state_order, count, stuff_id, buyer_id, comment) VALUES (0, '$date', 'non', 'ordered', 1, '$product_id', 3, '')";
					$Q = mysqli_query($connect, $query);
					
					if($Q){
						$inserted = mysqli_insert_id($connect);
						array_push($inserted_cart, $inserted);
						//insert data ke selling
						$query = "INSERT INTO sellerSide_selling(id, date, pay_value, count, ship_cost, pay_method, buyer_id, cart_ordered_id, trx_id) VALUES (0,'$date',0,1,0,'transfer',3,'$inserted','$trx_id')";
						$Q = mysqli_query($connect, $query);
						if($Q){
							$status_selling_add += 1;
						}
					}
				}
				if($status_selling_add == $arr_sum_buy[$i]){
					$stuff_cost += ($product_cost*$arr_sum_buy[$i]);
				}
			}
		}
		$message = array();
		if($stuff_cost != 0){
			$query = "INSERT INTO loginSys_trx_data(id, date, trx_code, ship_cost, stuff_cost, total_price, buyer_id) VALUES (0,'$date','$trx_id',0,'$stuff_cost','$stuff_cost',3)";
			$Q = mysqli_query($connect, $query);
			if($Q){
				$message['status'] = 'success';
				$message['id_trx'] = $trx_id;
			}else{
				$message['status'] = 'failed';
				$message['id_trx'] = '';
			}
		}else{
			$message['status'] = 'failed';
			$message['id_trx'] = '';
		}
		echo json_encode($message);
	}
}
// http://localhost:8080/ServAR/create_trx.php?buy=true&id[]=2&id[]=3&id[]=4&sum[]=3&sum[]=1&sum[]=7

 ?>

