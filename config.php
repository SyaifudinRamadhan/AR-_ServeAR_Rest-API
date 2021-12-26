<?php 

$host = "remotemysql.com:3306";
$username = "5Oph5rPqCY";
$password = "rpRBZqPX7T";
$db_name = "5Oph5rPqCY";

$connect = mysqli_connect($host, $username, $password, $db_name);

if(!$connect){
	echo "Koneksi gagal";
	exit();
}

 ?>