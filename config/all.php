<?php

$count = count($_POST);
$i = 1;
$no = 1;
//$n=1;
//$sql="insert into ";
$sql = "call ";
foreach ($_POST as $key => $value) {
	if ($i == 1) {
		//$sql.=$value."(null,";
		$sql .= $value . "(";
	}
	/*else if($i==2){
	}*/
	/*else if($i==($count-$no){
	}
	*/ else if ($i == $count) {
		$sql .= "'" . $value . "')";
	} else {
		$sql .= "'" . $value . "',";
	}
	$i++;
}

include("SYD_Class.php");
$coder = new sydClass();
// echo " " . $sql;
$coder->operation($sql);
// echo" ".$sql;
// echo"fields are: ".$count." loop: ".$i;
