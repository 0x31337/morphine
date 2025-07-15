<?php
/**
 * JASOOS Engine Database API
 * responsible for manipulating the MySQL database in an easy-to-use method
 * sanitized and secured requests with fewer code than other frameworks
 * Please refer to the docs and read Database API section for more detailed information.
 * Note: Please @DO_NOT directly edit on this API on BDR without announcing the changes publicly.
 */

// security
if (!function_exists("GetSQLValueString")) {
	function GetSQLValueString($theValue, $theType, $theDefinedValue = "", $theNotDefinedValue = "") 
	{
	  if (PHP_VERSION < 6) {
	    $theValue = get_magic_quotes_gpc() ? stripslashes($theValue) : $theValue;
	  }

	  $theValue = function_exists("mysqli_real_escape_string") ? mysqli_real_escape_string($theValue) : mysqli_escape_string($theValue);

	  switch ($theType) {
	    case "text":
	      $theValue = ($theValue != "") ? "'" . $theValue . "'" : "NULL";
	      break;    
	    case "long":
	    case "int":
	      $theValue = ($theValue != "") ? intval($theValue) : "NULL";
	      break;
	    case "double":
	      $theValue = ($theValue != "") ? doubleval($theValue) : "NULL";
	      break;
	    case "date":
	      $theValue = ($theValue != "") ? "'" . $theValue . "'" : "NULL";
	      break;
	    case "defined":
	      $theValue = ($theValue != "") ? $theDefinedValue : $theNotDefinedValue;
	      break;
	  }
	  return $theValue;
	}
}

# Database functions library.
function uptfunc($data){

		$col = 0;
		$val = 0;
		$arrCol = 0;
		$arrVal = 0;
		$columns = array();
		$values = array();
		foreach ($data as $key => $value) {
			if($key == 'table') { $table = $value; continue; }
			if($key == 'setColumn') { 
				if (is_array($value)) {
						$column[$arrCol] = $value; $arrCol++; continue; 
					}else{
						$column = $value; continue; 
					}
			}
			if($key == 'setValue') { 
				if (is_array($value)) {
					$value_[$arrVal] = $value; $arrVal++; continue;
				}else{
					$value_ = $value; continue;
				}
					
			}
			if($key == 'where') { 
					foreach($value as $colname=>$colval){
						$whereCol[$col] = $colname; $col++;
						$whereVal[$val] = $colval; $val++;
					}
				}
		}



		if(!is_array($column[0])){
			$setQuery = $value_[0];
		}else{

		if (count($column[0]) == count($value_[0])) {
			# Here the code goes ($column=$value, $column2=$value2...)
			$eq = null;
			foreach ($column[0] as $k => $clmn) {
				$eq .= $clmn."='".$value_[0][$k]."'";
				if($k == count($column) -1) break;
				$eq .= ',';
			}
			$setQuery = $eq;
		} else {
			return 'JASOOS Engine critical error (logic): number of columns and values are different.';
		}


		if (count($whereCol) == count($whereVal)) {
			$eq = null;
			foreach ($whereCol as $k => $Col) {
				$eq .= $Col."='".$whereVal[$k]."'";
			if($k == count($whereCol) -1) break;
				$eq .= " AND ";
			}
			$whereQuery = $eq;
		} else {
			return "JASOOS Engine critical error (logic): number of Where clauses and their values are different.";
		}
			return $sql = "UPDATE $table SET $setQuery WHERE $whereQuery";
		}
}

function instfunc($db, $data){
		if (is_array($data)) {
		$col = 0;
		$val = 0;
		$columns = array();
		$values = array();
			foreach ($data as $key => $value) {
					if($key == 'table') { $table = mysqli_real_escape_string($db, $value); continue; }
				$columns[$col] = mysqli_real_escape_string($db, $value);
				$col++;
				$values[$val] = mysqli_real_escape_string($db, $key);
				$val++;
			}

		 $values = "'".implode("','", $values)."'";
 		 $columns = implode(",", $columns);

 		 $sql = "INSERT INTO $table ".
		   	"($columns) ".
    	    "VALUES ".
       		"($values)";
		}else{
			return "JASOOS Engine critical error (BUG IN CODE): Wrong entry to \$obj->insert('BUG'); ";
		}
       	 
       	   $insert = mysqli_query($db, $sql);
		   $output = ($insert) ? $output = "Data inserted successfully." : $output = "JASOOS Engine critical error (database)[insert]:".mysqli_error($db) ;

		  return $output;
		}

function batchInstFunc($db, $data, $batchSize = 1000) {
    // Input validation
    if (!isset($data['table']) || !isset($data['columns']) || !isset($data['values'])) {
        return "Error: Missing required parameters (table, columns, or values)";
    }

    // Extract and validate data
    $table = '`' . $data['table'] . '`';  // Escape table name
    $columns = array_map(function($col) {
        return '`' . trim($col) . '`';  // Escape column names
    }, explode('|', $data['columns']));
    $values = $data['values'];
    $types = isset($data['types']) ? $data['types'] : '';

    // Validate values array
    if (empty($values)) {
        return "Error: No values provided for insert";
    }

    // Validate column count consistency
    foreach ($values as $index => $valueSet) {
        if (count($valueSet) !== count($columns)) {
            return "Error: Value set at index $index does not match column count";
        }
    }

    // Initialize variables for tracking
    $totalRecords = count($values);
    $processedRecords = 0;
    $successCount = 0;

    try {
        // Start transaction for better performance and data integrity
        mysqli_begin_transaction($db);

        // Process in batches to optimize memory usage
        while ($processedRecords < $totalRecords) {
            // Get current batch
            $batchValues = array_slice($values, $processedRecords, $batchSize);
            $currentBatchSize = count($batchValues);

            // Construct SQL query for this batch
            $columnsPart = implode(', ', $columns);
            $placeholders = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';
            $valuesPart = implode(', ', array_fill(0, $currentBatchSize, $placeholders));

            $sql = "INSERT INTO $table ($columnsPart) VALUES $valuesPart";

            // For debugging - can be removed in production
            // error_log("SQL Query: $sql");

            // Prepare statement
            $stmt = mysqli_prepare($db, $sql);
            if ($stmt === false) {
                throw new Exception("Prepare failed: " . mysqli_error($db));
            }

            // Flatten values for binding
            $flatValues = [];
            foreach ($batchValues as $valueSet) {
                foreach ($valueSet as $value) {
                    $flatValues[] = $value;
                }
            }

            // Create types string for binding
            $typesStr = str_repeat($types, $currentBatchSize);

            // Validate types string length
            if (strlen($typesStr) !== count($flatValues)) {
                throw new Exception("Types string length (" . strlen($typesStr) .
                    ") does not match parameter count (" . count($flatValues) . ")");
            }

            // Bind parameters
            if (!$stmt->bind_param($typesStr, ...$flatValues)) {
                throw new Exception("Binding parameters failed: " . $stmt->error);
            }

            // Execute statement
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }

            // Track success
            $successCount += $stmt->affected_rows;

            // Clean up
            $stmt->close();

            // Update processed count
            $processedRecords += $currentBatchSize;
        }

        // Commit transaction
        mysqli_commit($db);
        return "Success: Inserted $successCount records";

    } catch (Exception $e) {
        // Rollback on error
        mysqli_rollback($db);
        return "JASOOS Engine critical error (database): " . $e->getMessage();
    }
}

function delfunc($data){
			$arrCol = 0;
		$arrVal = 0;
		foreach ($data as $key => $value) {
			if($key == 'table') { $table = $value; continue; }
			if($key == 'where') { 
				if (is_array($value)) {
					foreach ($value as $keyVal => $valuee) {
					
						$column[$arrCol] = $keyVal; $arrCol++;
						$value_[$arrVal] = $valuee; $arrVal++; continue;
						

					}
				}else{
						$column = $value; continue; 
					}
			}
		}


		if(!is_array($column)){
			return 'JASOOS Engine critical error (syntax): you have to set an array in \'where\' element. (the array $data[...])';
		}else{

		if (count($column) == count($value_)) {
			# Where query cooking ($column=$value, $column2=$value2...)
			$eq = null;
			foreach ($column as $k => $clmn) {
                if(@$value_[$k][0] != "'")
                {
                    $eq .= $clmn."='".$value_[$k]."'";
                }
                else
                {
                    $eq .= $clmn."=".$value_[$k]." ";
                }

				if($k == count($column) -1) break;
				$eq .= ' AND ';
			}
			$whereQuery = $eq;
		} else {
			return 'JASOOS Engine critical error (logic): number of columns and values are different. (element \'where\' in the array $data[]).';
		}

			return $sql = "DELETE FROM $table WHERE $whereQuery";
		}
}
function wheresort($where, $isOR){
	$wh = null;
	$p = 0;
	$d = 0;
	if(!is_array($where)){
		return $where;
	}else{
	foreach($where as $s){
		$d++;
	}
	foreach ($where as $cl => $vl) {
		$wh .= $cl."='".$vl."'"; $p++;
		if($p>0 && $p != $d) $wh .= ($isOR) ? ' OR ' : ' AND ' ;
	}
	return $wh;
	}
}
function copyData($data, $updateTarget=false){
	$jasDatabase = new jasDatabase();
	jasDatabase::set();

				$coll = 0;
				$vall = 0;
				$arrCol = 0;
				$arrVal = 0;
				$whereCol = array();
				$whereVal = array();
				$where = null;
				foreach ($data as $key => $value) {
					if(strtolower($key) == 'tables') { 
							foreach($value as $frm=>$to){
								$frmTbl = $frm;
								$toTbl = $to; continue;
							}
					}
					if(strtolower($key) == 'columns') { 
						if (is_array($value)) {
							$is_array = true;
							foreach ($value as $keyVal => $valuee) {
								if(strtolower($keyVal) == 'where'){
									if(!is_array($valuee)) { 
										list($c, $v) = explode('=', $valuee,2);
										$v = "'".$v."'";
										$where = $c ."=". $v;
										continue;
									}else{
										foreach($valuee as $col => $val){
											$whereCol[$arrCol] = $col; $arrCol++;
											$whereVal[$arrVal] = $val; $arrVal++; continue;
										}
									}
									continue;
								}
								
								$fromCol[$coll] = $keyVal; $coll++;
								$toCol[$vall] = $valuee; $vall++; continue;

							}
						}else{
								echo "JASOOS Engine critical error (syntax): the element 'Columns' should be an array."; 
							}
					}
				}


				echo "<pre>";

				if($is_array){ 
					if(count($whereCol) != count($whereVal)){ 
						die('JASOOS Engine critical error (logic):  number of columns and values are different.'); 
					}else{
						$qu = null;
						foreach($whereCol as $ke => $wval){
							$qu .= $wval."='".$whereVal[$ke]."'";
							if($ke == count($whereCol) -1) break;
							$qu .= ' AND ';
						}
					}
				}
				if (count($fromCol) == count($toCol)) {
					# where query ($column=$value, $column2=$value2...)
					$eq = null;
					foreach ($fromCol as $k => $frm) {
						$eq .= $frm."='".$toCol[$k]."'";
						if($k == count($fromCol) -1) break;
						$eq .= ',';
					}
					$frmTo = $eq;
				} else {
					echo 'JASOOS Engine critical error (logic): number of columns and values are different. (element \'where\' in the array $data[]).';
				}
		
		// $TC = target Columns in the target Table. ($col,$col2 ...etc)
		$TC = null;
		$FC = null;
		$V = null;
		$op = 0;
		foreach ($toCol as $k => $value) {
			if($k == 0) 
				$TC .= $value;
			if($k > 0)
				$TC .= ','.$value;
		}

		// $FC = source Columns in the source Table. ($col,$col2 ...etc)
		foreach ($fromCol as $k => $value) {
			if($k == 0) 
				$FC .= $value;
			if($k > 0)
				$FC .= ','.$value;
		}

		if($where) $qu = $where;

		foreach ($fromCol as $key => $value) {
		$jasDatabase->select('*',$frmTbl,$qu);
		$com = 0; // Al-Kawasim al-moshtaraka -- Commonalities
		while($row = $jasDatabase->exists()){
			if($jasDatabase->getTotalRows() > 1){
				++$op;
				$V .= $row[$value];
				if($op > 0 && $op != $jasDatabase->getTotalRows()*count($toCol))
					$V .= ',';
			}else{
				if($key > 0) $V .= ",";
				$V .= $row[$value];

			}
		}
		}

		$VALUES = explode(',', $V);
		$com = count($VALUES)/count($toCol);
		$sum = null; // the logic sum of the query's elements.
		$colNum = count($toCol);

		if($com != 1){
				if($colNum == 1){
				while($x < $com){
					if($x == 0) $sum = "('";
					$sum .= $VALUES[$x]."')";
					if($x != $com-1) $sum .= ",('";
					$x++;
					//$sum .= "')";
					}
				}else{
					for ($i=0; $i<$com; $i += 1) {

					if($i == 0) $sum .= "('";
					$j = 1;
					$x = $i; 
					$sec = 1;
					while($j < $colNum){
						
						if($sec == 1 && count($fromCol) > 2){
							$sum .= $VALUES[$x]."','".substr($VALUES[$x+$com],strlen($VALUES[$x+$com])) ;
							$sec = 1;
							
						}else{
							$sum .= $VALUES[$x]."','".$VALUES[$x+$com];
						}
						$sec++;
						$x += $com;
						$j++;
					}
					if($i != $com -1) { $sum .= "'),('";}else{ $sum .= "')"; }
				}
			}
		}else{
			$sum = "('";
			$sum .= implode("','", $VALUES);
			$sum = $sum . "')";
		}
		if(!$updateTarget){
			$sql = "INSERT INTO $toTbl ".
					   	"($TC) ".
			    	    "VALUES ".
		       			"$sum";
			return $sql;
		}else{
			foreach ($toCol as $k => $cc) {
				$q .= $cc."='".$VALUES[$k]."'";
				if($k != count($fromCol) -1) $q .= ", ";
			}
			foreach($data as $k => $v){
				if(strtolower($k) == "target"){
					$whr = null;
					$l = 0;
					foreach($v as $key=>$va){
						$whr .= $key."='".$va."'";
						$l++;
						if($l != 0 && $l < count($v)) $whr .= " AND ";
						
					}
				}
			}
			return $sql = "UPDATE $toTbl SET $q WHERE $whr";
		}
}
function moveData($data, $delRow=true, $updateTarget = false){
	$jasDatabase = new jasDatabase();
	jasDatabase::set();

				$coll = 0;
				$vall = 0;
				$arrCol = 0;
				$arrVal = 0;
				$whereCol = array();
				$whereVal = array();
				$where = null;
				foreach ($data as $key => $value) {
					if(strtolower($key) == 'tables') { 
							foreach($value as $frm=>$to){
								$frmTbl = $frm;
								$toTbl = $to; continue;
							}
					}
					if(strtolower($key) == 'columns') { 
						if (is_array($value)) {
							$is_array = true;
							foreach ($value as $keyVal => $valuee) {
								if(strtolower($keyVal) == 'where'){
									if(!is_array($valuee)) { 
										list($c, $v) = explode('=', $valuee,2);
										$v = "'".$v."'";
										$where = $c ."=". $v;
										continue;
									}else{
										foreach($valuee as $col => $val){
											$whereCol[$arrCol] = $col; $arrCol++;
											$whereVal[$arrVal] = $val; $arrVal++; continue;
										}
									}
									continue;
								}
								
								$fromCol[$coll] = $keyVal; $coll++;
								$toCol[$vall] = $valuee; $vall++; continue;

							}
						}else{
								echo "JASOOS Engine critical error (syntax): the element 'Columns' should be an array."; 
							}
					}
				}


				echo "<pre>";

				if($is_array){ 
					if(count($whereCol) != count($whereVal)){ 
						die('JASOOS Engine critical error (logic):  number of columns and values are different.'); 
					}else{
						$qu = null;
						foreach($whereCol as $ke => $wval){
							$qu .= $wval."='".$whereVal[$ke]."'";
							if($ke == count($whereCol) -1) break;
							$qu .= ' AND ';
						}
					}
				}
				if (count($fromCol) == count($toCol)) {
					# where query syntax ($column=$value, $column2=$value2...)
					$eq = null;
					foreach ($fromCol as $k => $frm) {
						$eq .= $frm."='".$toCol[$k]."'";
						if($k == count($fromCol) -1) break;
						$eq .= ',';
					}
					$frmTo = $eq;
				} else {
					echo 'JASOOS Engine critical error (logic): number of columns and values are different. (element \'where\' in the array $data[]).';
				}
		
		// $TC = target Columns in the target Table. ($col,$col2 ...etc)
		$TC = null;
		$FC = null;
		$V = null;
		$op = 0;
		foreach ($toCol as $k => $value) {
			if($k == 0) 
				$TC .= $value;
			if($k > 0)
				$TC .= ','.$value;
		}

		// $FC = source Columns in the source Table. ($col,$col2 ...etc)
		foreach ($fromCol as $k => $value) {
			if($k == 0) 
				$FC .= $value;
			if($k > 0)
				$FC .= ','.$value;
		}

		if($where) $qu = $where;

		foreach ($fromCol as $key => $value) {
		$jasDatabase->select('*',$frmTbl,$qu);
		$com = 0; // Al-Kawasim al-moshtaraka -- Commonalities
		while($row = $jasDatabase->exists()){
			if($jasDatabase->getTotalRows() > 1){
				++$op;
				$V .= $row[$value];
				if($op > 0 && $op != $jasDatabase->getTotalRows()*count($toCol))
					$V .= ',';
			}else{
				if($key > 0) $V .= ",";
				$V .= $row[$value];

			}
		}
		}

		$VALUES = explode(',', $V);
		$com = count($VALUES)/count($toCol);
		$sum = null; 
		$colNum = count($toCol);
		$x = 0;
		if($com != 1){
				if($colNum == 1){
				while($x < $com){
					if($x == 0) $sum = "('";
					$sum .= $VALUES[$x]."')";
					if($x != $com-1) $sum .= ",('";
					$x++;

					}
				}else{
					for ($i=0; $i<$com; $i += 1) {

					if($i == 0) $sum .= "('";
					$j = 1;
					$x = $i; 
					$sec = 1;
					while($j < $colNum){
						
						if($sec == 1 && count($fromCol) > 2){
							$sum .= $VALUES[$x]."','".substr($VALUES[$x+$com],strlen($VALUES[$x+$com])) ;
							$sec = 1;
							
						}else{
							$sum .= $VALUES[$x]."','".$VALUES[$x+$com];
						}
						$sec++;
						$x += $com;
						$j++;
					}
					if($i != $com -1) { $sum .= "')";}else{ $sum .= "')"; }
				}
			}
		}else{
			$sum = "('";
			$sum .= implode("','", $VALUES);
			$sum = $sum . "')";
		}
		if(!$updateTarget){
			$sql = "INSERT INTO $toTbl ".
					   	"($TC) ".
			    	    "VALUES ".
			       		"$sum";
		}else{
			foreach ($toCol as $k => $cc) {
				$q .= $cc."='".$VALUES[$k]."'";
				if($k != count($fromCol) -1) $q .= ", ";
			}
			foreach($data as $k => $v){
				if(strtolower($k) == "target"){
					$whr = null;
					$l = 0;
					foreach($v as $key=>$va){
						$whr .= $key."='".$va."'";
						$l++;
						if($l != 0 && $l < count($v)) $whr .= " AND ";
						
					}
				}
			}
			$sql = "UPDATE $toTbl SET $q WHERE $whr";
			
		}
		if($delRow == true){
			$sql2 = "DELETE FROM $frmTbl WHERE $qu";
		}else{
			$q = null;
			foreach ($fromCol as $k => $cc) {
				$q .= $cc."=' '";
				if($k != count($fromCol) -1) $q .= ", ";
					$sql2 = "UPDATE $frmTbl SET $q WHERE $qu ";
			}
		}

		return array($sql, $sql2);	
}


?>