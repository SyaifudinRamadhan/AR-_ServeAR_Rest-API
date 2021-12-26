<?php 

$host = "remotemysql.com:3306";
$username = "5Oph5rPqCY";
$password = "rpRBZqPX7T";
$db_name = "5Oph5rPqCY";

$connect = mysqli_connect($host, $username, $password, $db_name);

if(!$connect){
	mysqli_close($connect);
	echo "Koneksi gagal";
	exit();
}

if (isset($_SERVER['HTTP_ORIGIN'])) {
    // should do a check here to match $_SERVER['HTTP_ORIGIN'] to a
    // whitelist of safe domains
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');    // cache for 1 day
}

$arr_products = array();

if ($connect){
	if(isset($_GET['unique_key'])){

		$key_store = $_GET['unique_key'];
		$query = "SELECT * FROM sellerSide_stuff WHERE seller_id = (SELECT fk_id_user_id FROM loginSys_user_sec WHERE unique_key = '$key_store')";
		$Q = mysqli_query($connect, $query);

		while ($fetch = mysqli_fetch_assoc($Q)) {
			// code...
			$arr_products[] = $fetch;
		}
		//mendapatkan data komentar
		for ($i=0; $i<count($arr_products); $i++){
			$comments = array();
			$id_prd = $arr_products[$i]['id'];
			$query = "SELECT comment FROM sellerSide_cart WHERE stuff_id = '$id_prd'";
			$Q = mysqli_query($connect, $query);
			while ($fetch = mysqli_fetch_assoc($Q)) {
				// code...
				$comments[] = $fetch['comment'];
			}
			$comments_str = "";
			for ($j=0; $j<count($comments); $j++){
				if($comments[$j] != ""){
					$comments_str = $comments_str.$comments[$j]." \n\n ";
				}
			}
			$arr_products[$i]['comments'] = $comments_str;
		}

	}
}
echo json_encode($arr_products);
 ?>