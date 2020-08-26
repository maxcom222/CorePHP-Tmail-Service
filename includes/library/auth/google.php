<?php 
/**
 * Google oAuth 2.0 Class
 * @copyright GemPixel.com - Personal use only!
 * @author KBRmedia
 */
class Google_Auth {
	/**
	 * Reserved Variables
	 */
	private $client_id = NULL;
	private $client_secret = NULL;
	private $response = NULL;
	private $redirect_uri = NULL;
	private $auth_url = "https://accounts.google.com/o/oauth2/auth?";
	private $token_url = "https://accounts.google.com/o/oauth2/token?";
	private $info_url = "https://www.googleapis.com/userinfo/v2/me?";
	private $exception = TRUE;
	public 	$error = NULL;
	/**
	 * Construct Google Auth
	 * @author KBRmedia
	 * @since  1.0
	 */
	public function __construct($client_id, $client_secret, $redirect_uri, $exception = TRUE){
		$this->exception = $exception;
		// Validate Code
		if(!isset($_SESSION)) {
			session_start();
		}else{
			session_regenerate_id();
		}
		if(empty($client_id) || empty($client_secret)){
			if($this->exception){
				throw new Exception("Application is not set up properly!");
			}else{
				$this->error = "Application is not set up properly!";
			}
		}
		// Validate Error
		if(isset($_GET["error"])){
			if($this->exception){
				throw new Exception('You did not authorize us to use your Google account.');
			}else{
				$this->error = "You did not authorize us to use your Google account.";
			}			
		}
		$this->client_id = $client_id;
		$this->client_secret = $client_secret;
		$this->redirect_uri = $redirect_uri;
		$this->run();		
	}
	/**
	 * Return Data
	 * @author KBRmedia
	 * @since  1.0
	 */
	public function info($array = FALSE){
		if($array){
			return json_decode(json_encode($this->response), TRUE);
		}
		return $this->response;
	}
	/**
	 * Get Auth Code
	 * @author KBRmedia
	 * @since  1.0
	 */
	private function run(){
		$state = md5(rand());
		$options = array(
			"scope" => "email profile",
			"redirect_uri" => $this->redirect_uri,
			"response_type" => "code",
			"state" => $state,
			"client_id" => $this->client_id
		);	
		if(isset($_GET["code"]) || !empty($_GET["code"])){		
			return $this->get_token();	
		}else{
			$_SESSION["oauth_state"] = $state;
			return header("Location: ".$this->auth_url.http_build_query($options));
		}		
	}
	/**
	 * Exchange Code for a token
	 * @author KBRmedia
	 * @since  1.0
	 */
	private function get_token(){
		if(!isset($_GET["state"]) || $_GET["state"] !== $_SESSION["oauth_state"]){			
			if($this->exception){
				throw new Exception("Security token doesn't match.");	
			}else{
				$this->error = "Security token doesn't match.";
			}		
		}		
		$code = $_GET["code"];
		$options = array(
			"code" => $code,
			"client_secret" => $this->client_secret,
			"redirect_uri" => $this->redirect_uri,
			"client_id" => $this->client_id,
			"grant_type" => "authorization_code"
		);
		$response = $this->http($this->token_url, $options);
		return $this->get_info($response);
	}
	/**
	 * Exchange Token for data
	 * @author KBRmedia
	 * @since  1.0
	 */
	private function get_info($data){
		$data = json_decode($data);
		// Validate Response
		if(!isset($data->access_token)) { 			
			if($this->exception){
				throw new Exception('Oups. The access token is not valid. Please try again.');	
			}else{
				$this->error = "Oups. The access token is not valid. Please try again.";
			}		 			
		}
		$response = $this->http($this->info_url."access_token=".$data->access_token, NULL, "GET");
		$this->response = json_decode($response);
	}
	/**
   * Make an HTTP request
	 * @since  1.0
   * @return API results
   */
  private function http($url, $postfields = NULL, $method = "POST") {
	    $this->http_info = array();
	    $ci = curl_init();
	    /* Curl settings */
	    curl_setopt($ci, CURLOPT_RETURNTRANSFER, TRUE);
	    switch ($method) {
	      case 'POST':
	        curl_setopt($ci, CURLOPT_POST, TRUE);
	        if (!empty($postfields)) {
	          curl_setopt($ci, CURLOPT_POSTFIELDS, $postfields);
	        }
	        break;
	      case 'DELETE':
	        curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'DELETE');
	        if (!empty($postfields)) {
	          $url = "{$url}?{$postfields}";
	        }
	    }
			curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, FALSE);
	    curl_setopt($ci, CURLOPT_URL, $url);
	    $response = curl_exec($ci);
	    $this->http_code = curl_getinfo($ci, CURLINFO_HTTP_CODE);
	    $this->http_info = array_merge($this->http_info, curl_getinfo($ci));
	    echo curl_error($ci);
	    curl_close ($ci);
    return $response;
  }
}