<?php 

function create_id(){
	$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$random_string = '';
		  
	for ($i = 0; $i < 15; $i++) {
		 $index = rand(0, strlen($characters) - 1);
		 $random_string .= $characters[$index];
	}
	return $random_string;
}

 ?>