<?php
class sydClass{
	public $db;
	public $result;
	public  $db_name="mental_health_support"; 

		function startsWith ($string, $startString) { 
        $len = strlen($startString); 
        return (substr($string, 0, $len) === $startString); 
    } 
	public function connection(){
		try{
			$this->db=new mysqli();
			$this->db->connect("localhost","root","");
			$this->db->select_db($this->db_name);
		}
		catch(Exception $ex){
			echo $ex.getMessage();
		}
	}
	public function operation($qry){
		$this->connection();
		try{
			$ok=$this->db->query($qry);
			while($row=$ok->fetch_array(MYSQLI_NUM)){					
				echo"".$row[0];
			}
		}
		catch(Exception $ex){
			echo $ex.getMessage();
		}
	}
	public function operationReturn($qry){
		$this->connection();
		try{
		    $res="";
			$ok=$this->db->query($qry);
			while($row=$ok->fetch_array(MYSQLI_NUM)){					
				$res=$row[0];
			}
			return $res;
		}
		catch(Exception $ex){
			echo $ex.getMessage();
		}
	}
	private function viewTableReport($qry,$dt_ids){
		$this->connection();
		try{
			$this->result=$this->db->query($qry);
			$condition=(bool)mysqli_num_rows($this->result);
			if(!$condition){
				//echo "There are wrong query please check your query";
			}else{
				$fields=$this->result->fetch_fields(); ?>
				<table id='<?php echo $dt_ids ; ?>' class='table tables tbless table-striped table-bordered table-hover'>
					<thead class="thead bg-info" style="color: white;font-weight: 700;">
						<tr>
							<?php 
							foreach($fields as $field){ ?>
								<th ><?php echo"".$field->name;?></th>
								<?php 
							} ?>
						</tr>
					</thead>
					<tbody>
						<?php
						while($row=$this->result->fetch_array(MYSQLI_NUM)){?>
							<tr>
								<?php 
								if($row[0]=="Total"){
									echo"<tr> <thead style=' background-color: #13b9cd !important; color: white;font-weight: 700;'>";
									foreach ($row as $key => $value) {
										echo"<td alt=$key>$value</td>";
									}
									echo"</thead></tr>";
								}else{
									foreach ($row as $key => $value) {?>
									<td>
										<?php
										echo"".str_replace('_', ' ', $value); ?>
									</td>
										<?php 
									}
								}?>
							</tr>
							<?php 
						}?>
					</tbody>
				</table>
				<?php
			}
		}catch(Exception $ex){
			echo $ex.getMessage();
		}
	}
	private function viewDataTable($qry1,$dtTble){
		$this->connection();
		try{
			$this->result=$this->db->query($qry1);
			$condition=(bool)mysqli_num_rows($this->result);
			if(!$condition){
				//echo "There are wrong query please check your query";
			}else{
				$fields=$this->result->fetch_fields(); 
				if ($this->startsWith($fields[0]->name, 'result')){ ?>
				<table id='<?php echo $dtTble; ?>' class='table tables tbless table-striped table-bordered table-hover'>
					<thead class="thead bg-success" style="color: white;">
						<tr>
							<?php 
							$lop=0;
							foreach($fields as $field){
								echo "<th>$field->name</th>";
							}?>
						</tr>
					</thead>
					<tbody>
						<?php 
						while($row=$this->result->fetch_array(MYSQLI_NUM)){ ?>
							<tr>
								<?php 
								foreach ($row as  $value){ ?>
									<td>
										<?php
										echo"".str_replace('_', ' ', $value); ?>
									</td>
									<?php 
								} ?>
								
							</tr>
							<?php 
						} ?>
					</tbody>
				</table>
				<?php } else { ?>
				<table id='<?php echo $dtTble; ?>' class='table tables tbless table-striped table-bordered table-hover'>
					<thead class="thead bg-white" style="color: white;">
						<tr>
							<?php 
							$lop=0;
							foreach($fields as $field){
								echo "<th>$field->name</th>";
							}?>
							<th>Action</th>
						</tr>
					</thead>
					<tbody>
						<?php 
						while($row=$this->result->fetch_array(MYSQLI_NUM)){ ?>
							<tr>
								<?php 
								foreach ($row as  $value){ ?>
									<td>
										<?php
										echo"".str_replace('_', ' ', $value); ?>
									</td>
									<?php 
								} ?>
								<td class="td-actions text-center" style="padding: 10px;">

									
									<button type="button" title='Edit' class="btn btn-success btn-sm <?php echo "btn_edit_".str_replace('dt_', '', $dtTble); ?>" value="<?php echo $row[0];?>">

														<i class="ti-pencil"></i></button>
<button  type="button" title='Remove' class="btn btn-danger btn-sm <?php echo "btn_remove_".str_replace('dt_', '', $dtTble); ?>" value="<?php echo $row[0];?>">

														<i class="las la-trash"></i></button>
									
								</td>

							</tr>
							<?php 
						} ?>
					</tbody>
				</table>
				<?php }
			}
		}catch(Exception $ex){
			echo $ex.getMessage();
		}
	}
	private function viewDataTableNoDelete($qry1,$dtTble){
		$this->connection();
		try{
			$this->result=$this->db->query($qry1);
			$condition=(bool)mysqli_num_rows($this->result);
			if(!$condition){
				//echo "There are wrong query please check your query";
			}else{
				$fields=$this->result->fetch_fields(); 
				if ($this->startsWith($fields[0]->name, 'result')){ ?>
				<table id='<?php echo $dtTble; ?>' class='table tables tbless table-striped table-bordered table-hover'>
					<thead class="thead bg-white" style="color: white;">
						<tr>
							<?php 
							$lop=0;
							foreach($fields as $field){
								echo "<th>$field->name</th>";
							}?>
						</tr>
					</thead>
					<tbody>
						<?php 
						while($row=$this->result->fetch_array(MYSQLI_NUM)){ ?>
							<tr>
								<?php 
								foreach ($row as  $value){ ?>
									<td>
										<?php
										echo"".str_replace('_', ' ', $value); ?>
									</td>
									<?php 
								} ?>
								
							</tr>
							<?php 
						} ?>
					</tbody>
				</table>
				<?php } else { ?>
				<table id='<?php echo $dtTble; ?>' class='table tables tbless table-striped table-bordered table-hover'>
					<thead class="thead bg-info" style="color: white;">
						<tr>
							<?php 
							$lop=0;
							foreach($fields as $field){
								echo "<th>$field->name</th>";
							}?>
							<th>Info</th>
						</tr>
					</thead>
					<tbody>
						<?php 
						while($row=$this->result->fetch_array(MYSQLI_NUM)){ ?>
							<tr>
								<?php 
								foreach ($row as  $value){ ?>
									<td>
										<?php
										echo"".str_replace('_', ' ', $value); ?>
									</td>
									<?php 
								} ?>
								<td class="td-actions text-center">

									<button type="button" title='Edit' class="btn btn-success <?php echo "btn_edit_".str_replace('dt_', '', $dtTble); ?>" value="<?php echo $row[0];?>"><i class='material-icons'>edit</i></button>
									
								</td> 

							</tr>
							<?php 
						} ?>
					</tbody>
				</table>
				<?php }
			}
		}catch(Exception $ex){
			echo $ex.getMessage();
		}
	}
	private function viewDataTableInfo($qry1,$dtTble){
		$this->connection();
		try{
			$this->result=$this->db->query($qry1);
			$condition=(bool)mysqli_num_rows($this->result);
			if(!$condition){
				//echo "There are wrong query please check your query";
			}else{
				$fields=$this->result->fetch_fields(); 
				if ($this->startsWith($fields[0]->name, 'result')){ ?>
				<table id='<?php echo $dtTble; ?>' class='table tables tbless table-striped table-bordered table-hover'>
					<thead class="thead bg-info" style="color: white;">
						<tr>
							<?php 
							$lop=0;
							foreach($fields as $field){
								echo "<th>$field->name</th>";
							}?>
						</tr>
					</thead>
					<tbody>
						<?php 
						while($row=$this->result->fetch_array(MYSQLI_NUM)){ ?>
							<tr>
								<?php 
								foreach ($row as  $value){ ?>
									<td>
										<?php
										echo"".str_replace('_', ' ', $value); ?>
									</td>
									<?php 
								} ?>
								
							</tr>
							<?php 
						} ?>
					</tbody>
				</table>
				<?php } else { ?>
				<table id='<?php echo $dtTble; ?>' class='table tables tbless table-striped table-bordered table-hover'>
					<thead class="thead bg-info" style="color: white;">
						<tr>
							<?php 
							$lop=0;
							foreach($fields as $field){
								echo "<th>$field->name</th>";
							}?>
							<th>Info</th>
						</tr>
					</thead>
					<tbody>
						<?php 
						while($row=$this->result->fetch_array(MYSQLI_NUM)){ ?>
							<tr>
								<?php 
								foreach ($row as  $value){ ?>
									<td>
										<?php
										echo"".str_replace('_', ' ', $value); ?>
									</td>
									<?php 
								} ?>
								<td class="td-actions text-center">

									<button type="button" title='Edit' class="btn btn-success <?php echo "btn_edit_".str_replace('dt_', '', $dtTble); ?>" value="<?php echo $row[0];?>"><i class='material-icons'>remove_red_eye</i></button>
									
								</td> 

							</tr>
							<?php 
						} ?>
					</tbody>
				</table>
				<?php }
			}
		}catch(Exception $ex){
			echo $ex.getMessage();
		}
	}
	public function fillCombo($qry,$id,$choose){
		$this->connection();
		echo"<div class='' id='test'> <select id='$id' class='form-control input-rounded ' name='$id' style='width:100%;'  required=''>";
		try{
			$this->result=$this->db->query($qry);
			$condition=(bool)mysqli_num_rows($this->result);
			echo $condition;
			if(!$condition){
				echo "<option> $choose </option>";
			}else{
			echo"<option value='' selected='' style='color:green'>".$choose."</option>"; ?>
			<?php
			while($row=$this->result->fetch_array(MYSQLI_NUM)){						
				echo"<option value=".$row[0].">".str_replace('_', ' ', $row[1])."</option>";
			}
			}
		}catch(Exception $ex){
			echo $ex.getMessage();
		}

		echo"</select></div>";
	}
	public function search($qry){
		$this->connection();
		try{
			$this->result=$this->db->query($qry);
		}
		catch(Exception $ex){
			echo $ex.getMessage();
		}
		return $this->result;
	}
	public function getOptionsFillCombo($query){
	    $this->connection();
        $output = '';
		try{
		    $this->result=$this->db->query($query);
		    $results= mysqli_fetch_all( $this->result,MYSQLI_NUM);
		    foreach($results as $row){
		        $output .= '<option value="'.$row[0].'">'.$row[1].'</option>';
             }
             return $output;
		}catch(Exception $ex){
			echo $ex.getMessage();
		}
    }
    public function __call($method, $arguments) {
	    if($method == 'Table') {
	        $ar = array();
	        $ar[0] = $arguments[0];
	        $ar[1] = $arguments[1];
	        // $r = substr($arguments[2],count($arguments[2])-1);
	        if($arguments[2] == 'n') {
	          return call_user_func_array(array($this,'viewDataTable'), $ar);
	        }else if($arguments[2] == 'e') {
	          return call_user_func_array(array($this,'viewDataTableNoDelete'), $ar);
	        }else if($arguments[2] == 'r') {
	          return call_user_func_array(array($this,'viewTableReport'), $ar);
	        }else if($arguments[2] == 'i') {
	          return call_user_func_array(array($this,'viewDataTableInfo'), $ar); 
	        }
	    }
 	}
}
?>