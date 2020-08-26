<?php 
/**
 * ====================================================================================
 *                          Premium URL Shortener (c) KBRmedia
 * ----------------------------------------------------------------------------------
 * @copyright - This software is exclusively sold at CodeCanyon.net. If you have downloaded this
 *  from another site or received it from someone else than me, then you are engaged
 *  in illegal activities. You must delete this software immediately or buy a proper
 *  license from http://codecanyon.net/user/KBRmedia/portfolio?ref=KBRmedia.
 *
 *  Thank you for your cooperation and don't hesitate to contact me if anything :)
 * ====================================================================================
 * 
 * WARNING: This file should not be modified in anyway or the app will not run!
 *          This file may not be shared or sold as is. For more info please contact us.
 *
 * @author KBRmedia (http://gempixel.com)
 * @link http://gempixel.com 
 * @license http://gempixel.com/license
 * @package Premium URL Shortener
 * @subpackage Custom PDO API
 */
class Database{

	protected $config=array(), $dbinfo, $db;
	public $db_error, $query, $prefix;
	public $rowCount, $rowCountAll;
	public $num_queries=0, $show_query="",$object=FALSE;
	/**
	 * Connect to Database
	 * @since 1.0
	 **/
	public function __construct($config,$db){
		$this->config=$config;
		$this->dbinfo=$db;
		$this->prefix=$db["prefix"];
		try{
		  $this->db = new PDO("mysql:host=".$this->dbinfo["host"].";dbname=".$this->dbinfo["db"]."", $this->dbinfo["user"], $this->dbinfo["password"]);    
		  $this->db->exec("set names utf8mb4");
			// if(!$this->config["debug"]){
			// 	$this->db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
			// }
		}catch (Exception $e){
		  $this->db_error = "Cannot connect to database: {$e->getMessage()}";
		  exit;
		}
	}
	/**
	 * Output Errors
	 * @since 1.0
	 **/
	public function __destruct(){
		if(!empty($this->db_error) && $this->config["debug"]){
			print_r("<h3>Database Error</h3><pre>{$this->db_error}</pre>");
			if($this->config["debug"]=="2" && !empty($this->query)) {
				print_r("<h3>Database Query</h3><pre>{$this->query}</pre>");
			}
			error_log("Database Error: {$this->db_error} occured at http://{$_SERVER["HTTP_HOST"]}{$_SERVER["REQUEST_URI"]}");
		}		
		unset($this->config);
		unset($this->db_error);
		unset($this->query);
		unset($this->dbinfo);
		unset($this->db);
	}	
	/**
	 * Use Original PDO API if needed
	 * @since 1.0
	 **/
	public function pdo(){
		return $this->db;
	}	
	/**
	 * Get configuration
	 * @since 1.0
	 **/
	public function get_config($table="settings"){
		// Get Config	
		$data=$this->get($table);
		foreach ($data as $key) {
			$config[$key['config']] = stripslashes($key['var']);
		}
		return $config+$this->config;
	}

	/** 
	 * Select function
	 * @since v1.0
	 */
	public function get($table,$where='',$sort='',$param=array()){
		// Build Query
		if(is_array($table)){
				$query="SELECT {$table["count"]} FROM ";
				$query.="`{$this->dbinfo["prefix"]}{$table["table"]}`";
				$table=$table["table"];
		}else{
			$query="SELECT * FROM ";
			$query.="`{$this->dbinfo["prefix"]}$table`";
		}
		if(!empty($where)){
			if(is_array($where)){
				$query.=" WHERE ";
				$count=count($where);
				$i=0;
				foreach ($where as $key => $value) {
					$query.="`$key` = ".$this->quote($value,$param);
					if(++$i != $count) {
					   $query.=" AND ";
					}			
				}				
			}else{
				$query.=" WHERE ";
				$query.= $where;			
			}
		}
		if(!empty($sort) && isset($sort["count"])){
			$result = $this->db->prepare($query);
			$result->execute($param);
			$this->rowCount=$result->rowCount();
		}	
		if(!empty($sort)){
			if(isset($sort["group"])){
				$query.=" GROUP BY `{$sort["group"]}`";
			}	
			if(isset($sort["group_custom"])) {
				$query.=" GROUP BY {$sort["group_custom"]}";			
			}
			if(isset($sort["order"])){
				if($sort["order"]=="RAND()"){
					$query.=" ORDER BY RAND()";
				}else{
					$query.=" ORDER BY `{$sort["order"]}`";
				}				
			}		
			if(isset($sort["order"])){
				if(isset($sort["asc"]) && $sort["asc"]==TRUE){
					$query.=" ASC";
				}else{
					$query.=" DESC";
				}
			}			
			if(isset($sort["limit"])){
				$query.=" LIMIT {$sort["limit"]}";
			}			
		}
	
		$result = $this->db->prepare($query);
		$result->execute($param);
		$this->rowCountAll=$result->rowCount();
			if($this->error_message($result->errorInfo())) {
				$this->query=strtr($query,$param);
				$this->db_error=$this->error_message($result->errorInfo());
				exit;
			}
		++$this->num_queries;
		if((isset($sort["limit"]) && $sort["limit"]=="1")){

			if($this->object){
				return $result->fetchObject();
			}else{
				return $result->fetch(PDO::FETCH_ASSOC);
			}			
		}else{
			
			if($this->object){
				return $result->fetchAll(PDO::FETCH_CLASS);
			}else{
				return $result->fetchAll();
			}
			return $data;
		}		
	return FALSE;				
	}

	/** 
	 * Run Queries
	 * @since v1.0
	 */
	public function run($query,$param=array(),$fetch=FALSE,$array=array()){
		$data="";

		$result = $this->db->prepare($query);
		if($result->execute($param) && !$fetch){
				return TRUE;
		}
		if($this->error_message($result->errorInfo())) {
			$this->query=strtr($query,$param);
			$this->db_error=$this->error_message($result->errorInfo());
			exit;
		}		
		++$this->num_queries;
		// Return Response
		if(empty($array)){
			if($this->object){
				return $result->fetchAll(PDO::FETCH_CLASS);
			}else{
				return $result->fetchAll();
			}
		}else {
			$data=$result->fetch(PDO::FETCH_ASSOC);
				if($array) return array($data);
				return $data;
		}					
	}

	/** 
	 * Insert to database
	 * @since v1.0
	 */
	public function insert($table,$parameters=array()){
		$param="";
		$val="";
		$insert= $this->ph($parameters);
		//Build Query
		$query="INSERT INTO {$this->dbinfo["prefix"]}$table";						
		if(is_array($insert)){
			$count=count($insert);
			$i=0;			
			foreach ($insert as $key => $value) {
				if($parameters[$value]=="NOW()"){
					$val.= "NOW()";
					unset($parameters[$value]);
				}else{
					$val.=$this->quote($value,$parameters);
				}					
				$param.="`$key`";
				if(++$i != $count) {
				    $param.=",";
				    $val.=",";
				}				
			}
			$query.=" ($param) VALUES ($val)";
		}		
		$result = $this->db->prepare($query);
		$result->execute($parameters);
		if($this->error_message($result->errorInfo())) {
			$this->query=strtr($query,$parameters);
			$this->db_error=$this->error_message($result->errorInfo());
			exit;
		}
		++$this->num_queries;
	return TRUE;		
	}
	/**
	 * Update Query
	 * @since 1.0
	 **/
	public function update($table,$field,$where,$param=array()){
		if(empty($field)){
			$field=$this->ph($param);
		}
		//Build Query
		$query="UPDATE {$this->dbinfo["prefix"]}$table SET ";
		if(is_array($field)){
			$count=count($field);
			$i=0;			
			foreach ($field as $key => $value) {
				if($value=="NOW()"){
					$query.="`$key`=$value";
				}else{
					$query.="`$key`=".$this->quote($value,$param);
				}					
				if(++$i != $count) {
				    $query.=",";
				}				
			}
		}else{
			$query.=$field;
		}
		if(is_array($where)){
			$count=count($where);
			$i=0;		
			$query.=" WHERE ";
			foreach ($where as $key => $value) {
				$query.="`$key`=".$this->quote($value,$param);
				if(++$i != $count) {
				    $query.=" AND ";
				}				
			}
		}else{
			$query.=" WHERE $where";
		}		

		$result = $this->db->prepare($query);
		$result->execute($param);
		if($this->error_message($result->errorInfo())) {
			$this->query=strtr($query,$param);
			$this->db_error=$this->error_message($result->errorInfo());
			exit;
		}
		++$this->num_queries;
	return TRUE;	
	}

	/** 
	 * Delete Function
	 * @since v1.0
	 */
	public function delete($table,$where,$param=array()){
		//Build Query
		$query="DELETE FROM {$this->dbinfo["prefix"]}$table";
		if(is_array($where)){
			$count=count($where);
			$i=0;		
			$query.=" WHERE ";
			foreach ($where as $key => $value) {
				$query.="`$key`=".$this->quote($value,$param);
				if(++$i != $count) {
				    $query.=" AND ";
				}				
			}
		}else{
			$query.=" WHERE ";
			$query.=$where;
		}

		$result = $this->db->prepare($query);
		$result->execute($param);		
		if($this->error_message($result->errorInfo())) {
			$this->query=strtr($query,$param);
			$this->db_error=$this->error_message($result->errorInfo());
			exit;
		}
		++$this->num_queries;
	return TRUE;	
	}
	/**
	 * Search Database
	 * @since 1.0
	 **/
	public function search($table,$where,$sort=array(),$param=array()){
		// Build Query
		if(is_array($table)){
			$query="SELECT {$table["count"]} FROM ";
			$query.="`{$this->dbinfo["prefix"]}{$table["table"]}`";
			$table=$table["table"];
		}else{
			$query="SELECT * FROM ";
			$query.="`{$this->dbinfo["prefix"]}$table`";
		}
		$query.=" WHERE ";
		if(is_array($where)){
			$i=0;
			$count=count($where);			
			foreach ($where as $key => $value) {
				if(is_array($value)){
					$query.="`{$value[0]}`='{$value[1]}' AND (";
					++$i;
				}else{
					if($i==0) $query.="(";
					$query.="`$key` LIKE $value";
					if(++$i != $count) {
				    $query.=" OR ";
					}else{
						 $query.=")";
					}
				}						
			}				
		}else{
			$query.=$where;
		}
		$result = $this->db->prepare($query);
		$result->execute($param);
		$this->rowCount=$result->rowCount();		
		if(!empty($sort)){
			if(isset($sort["group"])){
				$query.=" GROUP BY `{$sort["group"]}`";
			}	
			if(isset($sort["group_custom"])) {
				$query.=" GROUP BY {$sort["group_custom"]}";			
			}			
			if(isset($sort["order"])){
				if($sort["order"]=="RAND()"){
					$query.=" ORDER BY RAND()";
				}else{
					$query.=" ORDER BY `{$sort["order"]}`";
				}				
			}
			if(isset($sort["order"])){
				if(isset($sort["asc"])){
					$query.=" ASC";
				}else{
					$query.=" DESC";
				}
			}			
			if(isset($sort["limit"])){
				$query.=" LIMIT {$sort["limit"]}";
			}			
		}		

		$result = $this->db->prepare($query);
		$result->execute($param);		
		if($this->error_message($result->errorInfo())) {
			$this->query=strtr($query,$param);
			$this->db_error=$this->error_message($result->errorInfo());
			exit;
		}

		if($result->rowCount()){
			// Return Response
			if($this->object){
				return $result->fetchAll(PDO::FETCH_CLASS);
			}else{
				return $result->fetchAll();
			}
			return $data;	
		}
		++$this->num_queries;
		return array();
	}

	/** 
	 * Row Count
	 * @since v1.0
	 */
	public function count($table,$where='',$sum='',$format=FALSE,$division=FALSE) {
		if(!empty($where)) $where="WHERE $where";
		if($sum) {			
	  	$query = "SELECT SUM($sum) FROM {$this->dbinfo["prefix"]}$table $where";
			$result = $this->db->prepare($query);
			$result->execute();	
			if($this->error_message($result->errorInfo())) {
				$this->query=$query;
				$this->db_error=$this->error_message($result->errorInfo());
				exit;
			}							  		
			while($row = $result->fetch(PDO::FETCH_ASSOC)){
				$count=$row['SUM('.$sum.')'];
				if(!$count) $count="0";
			}
		}else{
	  	$query = "SELECT COUNT(*) FROM {$this->dbinfo["prefix"]}$table $where";
			$result = $this->db->prepare($query);
			$result->execute();
			if($this->error_message($result->errorInfo())) {
				$this->query=$query;
				$this->db_error=$this->error_message($result->errorInfo());
				exit;
			}									
			$row = $result->fetchAll(PDO::FETCH_ASSOC);
			
			if(count($row) == "1"){
				$count = $row[0]["COUNT(*)"];
			}else{
				$count = count($row);
			}
			if(!$count) $count="0";
		}
		++$this->num_queries;
		if($format) return Main::formatnumber($count,2);
		return $count;
	}
	// Generate Placeholders
	private  function ph(array $a){
		$b=array();
		foreach ($a as $key => $value) {
			$b[str_replace(":", "", $key)]="$key";
		}
		return $b;
	}
	// Check if there is an error
	private function error_message($error){
		if(!empty($error[2])){
			return $error[2];
		}
		return FALSE;
	}
	// Check if quotes are needed
	private function quote($string,$param=''){	
		if(empty($param)){
			return "'$string'";
		}
		return $string;
	}
}