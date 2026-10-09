<?php 
$name=$_POST['qry'];
include("SYD_Class.php");
$coder=new sydClass();
$res= $coder->search(str_replace("^"," ",$name));
while($row=$res->fetch_array(MYSQLI_ASSOC)){
	foreach ($row as $key => $value) {
		echo"$value,";
	}
}
?>
