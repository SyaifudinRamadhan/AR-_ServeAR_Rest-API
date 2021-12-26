<?php 

require 'config.php';

session_start();

if (isset($_SERVER['HTTP_ORIGIN'])) {
    // should do a check here to match $_SERVER['HTTP_ORIGIN'] to a
    // whitelist of safe domains
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');    // cache for 1 day
}

//Mengambil seluruh data toko dalam database
$query = "SELECT * FROM loginSys_user_sec WHERE status = 'seller'";
$arr_shops = array();

if ($connect){
	$Q = mysqli_query($connect, $query);

	while ($fetch = mysqli_fetch_assoc($Q)) {
		$arr_shops[] = $fetch;
	}
}
echo json_encode($arr_shops);
// mysqli_close($connect);
 ?>