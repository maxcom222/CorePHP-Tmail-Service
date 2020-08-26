<?php  
/**
 * ====================================================================================
 *                           GemPixel AutoUpdater (c) KBRmedia
 * ----------------------------------------------------------------------------------
 * @copyright 
 *  if you have downloaded this from another site or received it from someone else than me,
 *  then you are engaged in illegal activity. You must delete this software immediately or 
 *  buy a proper license from http://gempixel.com/buy/short.
 *
 *  Thank you for your cooperation and don't hesitate to contact me if anything :)
 * ====================================================================================
 *
 * @author KBRmedia (http://gempixel.com)
 * @link http://gempixel.com 
 * @package GemPixel
 * @subpackage Application updater
 */

namespace kbrmedia;

class AutoUpdate {

	/**
	 * Constant
	 */
	const latestVersion = "1.0";
	const serverURL = "https://cdn.gempixel.com/updater";
	/**
	 * Private
	 * @var null
	 */
	private $endpoint = NULL;	
	private $purchaseKey = NULL;
	private $error = NULL;

	/**
	 * [__construct description]
	 * @author KBRmedia <https://gempixel.com>
	 * @version 1.0
	 */
	
	public function __construct($key) {		
		$this->purchaseKey = $key;
	}
	/**
	 * [install description]
	 * @author KBRmedia <https://gempixel.com>
	 * @version 1.0
	 * @return  [type] [description]
	 */
	public function install(){
		// Check to make sure everything is OK
		$this->check();
		
		if($this->verify()){
			return TRUE;
		}

		$this->error = "An unexpected error occured. Please update manually.";
		throw new \Exception($this->error);		
		return FALSE;		
	}
	/**
	 * [check description]
	 * @author KBRmedia <https://gempixel.com>
	 * @version 1.0
	 * @return  [type] [description]
	 */
	private function check(){		
		// Check cURL
		if(!in_array('curl', get_loaded_extensions())){ 
			$this->error = "cURL library is not available. Please update manually.";
			throw new \Exception($this->error);			
			return FALSE;
		}

		// Check ZipArchive
		if(!class_exists("ZipArchive")){
			$this->error = "ZipArchive library is not available. Please update manually.";
			throw new \Exception($this->error);			
			return FALSE;
		}

		// Check Permission
		if(!is_writable(ROOT)){
			$this->error = ROOT." is not writable. Please change the permission to 755.";
			throw new \Exception($this->error);			
			return FALSE;
		}

		// Check Key
		if(is_null($this->purchaseKey) || empty($this->purchaseKey)){
			$this->error = "Purchase key is invalid. You can find your purchase key in the downloads section of codecayon.";
			throw new \Exception($this->error);			
			return FALSE;			
		}
	}
	/**
	 * [getMessage description]
	 * @author KBRmedia <https://gempixel.com>
	 * @version 1.0
	 * @return  [type] [description]
	 */
	public function getMessage(){
		return $this->error;
	}
	/**
	 * [verify description]
	 * @author KBRmedia <https://gempixel.com>
	 * @version 1.0
	 * @return  [type] [description]
	 */
	private function verify(){	

		$this->endpoint = self::serverURL."/".self::latestVersion."/";
		
		$response = $this->http(["data" => ["url" => \Main::url()]]);

		$http = json_decode($response);

		if(isset($http->status) && $http->status == "validated"){
			return $this->download($http->download);
		}

		$this->error = "An error occured: {$http->message}";
		throw new \Exception($this->error);		
		return FALSE;
	}

	/**
	 * [download description]
	 * @author KBRmedia <https://gempixel.com>
	 * @version 1.0
	 * @return  [type] [description]
	 */
	protected function download($link){

		if(!file_put_contents(ROOT."/main-auto.zip", fopen($link, 'r'))){
			$this->error = "The file cannot be downloaded an error occured.";
			throw new \Exception($this->error);		
			return FALSE;			    
		}

		return $this->extract();
	}

	/**
	 * [extract description]
	 * @author KBRmedia <https://gempixel.com>
	 * @version 1.0
	 * @return  [type] [description]
	 */
	protected function extract(){
   
   	$zip = new \ZipArchive();
    $file = $zip->open(ROOT."/main-auto.zip");

    if($file === TRUE) {
      
      if(!$zip->extractTo(ROOT."/")){
				$this->error = "The file was downloaded but cannot be extracted due to permission.";
				throw new \Exception($this->error);		
				return FALSE;	
      }

      $zip->close();
      
    } else {
			$this->error = "The file cannot be extracted.";
			throw new \Exception($this->error);		
			return FALSE;	    	
	  }

	  return $this->update();
	}
	/**
	 * [update description]
	 * @author KBRmedia <https://gempixel.com>
	 * @version 1.0
	 * @return  [type] [description]
	 */
	protected function update(){
		$this->endpoint = \Main::url()."/updater.php?run=true";
		$this->http();
		return $this->clean();
	}

	/**
	 * [clean description]
	 * @author KBRmedia <https://gempixel.com>
	 * @version 1.0
	 * @return  [type] [description]
	 */
	protected function clean(){
		if(file_exists(ROOT."/main-auto.zip")){
			unlink(ROOT."/main-auto.zip");
			return TRUE;
		}
	}
	/**
	 * [http description]
	 * @author KBRmedia <https://gempixel.com>
	 * @version 1.0
	 * @param   [type] $url    [description]
	 * @param   array  $option [description]
	 * @return  [type]         [description]
	 */
	protected function http($option = []){  

	  $curl = curl_init();
	  curl_setopt($curl, CURLOPT_URL, $this->endpoint);

	  if(isset($option["data"]) && is_array($option["data"])){
	    
	    $fields = "";
	    foreach($option["data"] as $key => $value) { $fields .= $key.'='.$value.'&'; }
	    rtrim($fields, '&');       

	    curl_setopt($curl, CURLOPT_POST, count($option["data"]));
	    curl_setopt($curl, CURLOPT_POSTFIELDS, $fields);
	  }

	  curl_setopt($curl, CURLOPT_HTTPHEADER, [
	    "X-Authorization: TOKEN ".$this->purchaseKey,
	    "X-Script: Premium URL Shortener",
	    "X-Version: "._VERSION
	  ]);

	  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	  curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	  $response = curl_exec($curl);
	  curl_close($curl);

	  return $response;
	}  	
}