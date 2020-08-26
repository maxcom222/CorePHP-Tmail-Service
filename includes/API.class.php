<?php 
/**
 * ====================================================================================
 *                           Premium URL Shortener (c) KBRmedia
 * ----------------------------------------------------------------------------------
 * @copyright This software is exclusively sold at CodeCanyon.net. If you have downloaded this
 *  from another site or received it from someone else than me, then you are engaged
 *  in an illegal activity. You must delete this software immediately or buy a proper
 *  license from http://codecanyon.net/user/KBRmedia/portfolio?ref=KBRmedia.
 *
 *  Thank you for your cooperation and don't hesitate to contact me if anything :)
 * ====================================================================================
 *
 * @author KBRmedia (http://gempixel.com)
 * @link http://gempixel.com 
 * @license http://gempixel.com/license
 * @package Premium URL Shortener
 * @subpackage Short Class
 */
class API extends App{
	/**
	 * Allowed actions
	 * @var array
	 */
	protected $actions = array("details", "urls", "edit");
	/**
	 * [$key description]
	 * @var null
	 */
	protected $key = NULL;
	/**
	 * [$user description]
	 * @var null
	 */
	protected $user = NULL;

	/**
	 * Class Constructer 
	 */
	public function __construct($db, $config, $do){
  	$this->config = $config;
  	$this->db = $db;
  	$this->do = $do;

  	// Clean Request
  	if(isset($_GET)) $_GET = array_map("Main::clean", $_GET);
		if(isset($_GET["page"]) && is_numeric($_GET["page"]) && $_GET["page"] > 0) $this->page = Main::clean($_GET["page"]);
		$this->http = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://":"http://");
		$this->check();
		
		$this->config["captcha"] = 0;
		$this->config["private"] = 0;
		$this->config["user"] = 1;
		$this->config["require_registration"] = 0;		

		return $this->index();
	}
	/**
	 * Index
	 * @since  5.6.4
	 */
	protected function index(){
		// Check if enabled
		if(!$this->config["api"]) return $this->error("001");		

		// Check if key exists
		if(!isset($_GET["key"]) && !isset($_GET["api"])){
			return $this->error("002");
		}		

		$key = isset($_GET["key"]) ? $_GET["key"] : $_GET["api"];

		// Check KEY
		if(empty($key) || strlen($key) < 4){
			return $this->error("002");
		}

		// Get User
		if(!$user = $this->db->get("user",["api" => "?"], ["limit" => 1], [$key])) return $this->error("002");

		$this->key = $key;
		$this->user = $user;

		$this->user->plan = $this->db->get("plans", ["id" => $user->planid], ["limit" => 1]);

		if($this->isTeam($user) && !$this->teamPermission("api.create", $user)){
			// Run Error
			return $this->error("000");			
		}

		if(!$user->active || $user->banned) return $this->error("009");

		// Run Methods
		if(!empty($this->do)){
			if(in_array($this->do, $this->actions) && method_exists(__CLASS__, $this->do)){
				// Run Method
				return $this->{$this->do}();
			}				
		} else {
				// Shorten
				return $this->shorten();
		}		
		// Run Error
		return $this->error("000");
	}

	/**
	 * [shorten description]
	 * @author KBRmedia <http://gempixel.com>
	 * @version 5.3
	 * @return  [type] [description]
	 */
	private function shorten(){

		if(!isset($_GET["url"]) || empty($_GET["url"])) return $this->error("004");

		include(ROOT."/includes/Short.class.php");
		$short = new Short($this->db,$this->config);

		$array = [];

		$array["private"] = TRUE;

		$array["url"]	= Main::clean($_GET["url"],3,TRUE);

		$array["type"] = NULL;
		
		if(isset($_GET["custom"]) && !empty($_GET["custom"])) $array["custom"] = Main::slug($_GET["custom"]);

		if(isset($_GET["pass"]) && !empty($_GET["pass"])) $array["password"] = Main::clean($_GET["pass"], 3, TRUE);

		if(isset($_GET["domain"]) && !empty($_GET["domain"])) $array["domain"] = Main::clean($_GET["domain"], 3, TRUE);

		if(isset($_GET["type"])){

			if($this->user->pro) {
				if(!in_array($_GET["type"], ["direct", "frame", "splash","overlay"])) return $this->error("009");
				$array["type"] = Main::clean($_GET["type"]);

			}else{
				if(!in_array($_GET["type"], ["direct", "frame", "splash"])) return $this->error("009");
				if(!$this->config["pro"]) $array["type"] = Main::clean($_GET["type"]);
			}			

		}

		$result = $short->add($array, ["noreturn" => TRUE, "api" => TRUE, "user" => $this->user]);

		return $this->build($result,isset($result["short"]) ? $result["short"] :"");
	}

	/**
	 * [details description]
	 * @author KBRmedia <http://gempixel.com>
	 * @version 5.2
	 * @return  [type] [description]
	 */
	private function details(){

		if(!isset($_GET["alias"])) return $this->error("007");

		if(!$url = $this->db->get("url", "alias = ? OR custom = ?", ["limit" => 1], [Main::clean($_GET["alias"]),Main::clean($_GET["alias"])])){
			return $this->error("008");
		}

		if($url->userid != $this->user->id) return $this->error("008");

		// Unique Clicks
		$unique = $this->db->count("stats","urlid = '{$url->id}' GROUP by ip");

		// Countries
		$countries = $this->db->get(array("count"=>"country AS country, COUNT(country) AS count","table"=>"stats"),array("urlid"=>"?"),array("group"=>"country","order"=>"count", "limit" => "10"),array($url->id));  
    
    $i=0;
    $top_country = [];

    foreach ($countries as $country) {
      $top_country[ucwords($country->country)] = $country->count;
    }

    arsort($top_country);

    // referrers
		
		$top_referrers = [];

    $referrers = $this->db->get(array("count"=>"domain AS domain, COUNT(domain) AS count","table"=>"stats"),array("urlid"=>"?"), array('group' => "domain", "limit" => 10),array($url->id));
 
    $browsers = $this->db->get(array("count"=>"browser as browser, COUNT(browser) AS count","table"=>"stats"),array("urlid"=>"?"), array('group' => "browser","limit"=>10, "order" => "count"),array($url->id));

    $os = $this->db->get(array("count"=>"os as os, COUNT(os) AS count","table"=>"stats"),array("urlid"=>"?"), array('group' => "os","limit"=>10,"order" => "count"),array($url->id));		    

    $fb = $this->db->count("stats","urlid = '$url->id' AND (domain LIKE '%facebook.%' OR domain LIKE '%fb.%')");
    $tw = $this->db->count("stats","urlid = '$url->id' AND (domain LIKE '%twitter.%' OR domain LIKE '%t.co%')");
    $gl = $this->db->count("stats","urlid = '$url->id' AND (domain LIKE '%plus.url.google%')");

    foreach ($referrers as $referrer) {
    	if(empty($referrer->domain)) $referrer->domain = e("Direct, email and other");
    	if(!preg_match("~facebook.~", $referrer->domain) && !preg_match("~fb.~", $referrer->domain) && !preg_match("~t.co~", $referrer->domain) && !preg_match("~twitter.~", $referrer->domain) && !preg_match("~plus.url.google.~", $referrer->domain)){
    		$top_referrers[$referrer->domain] = $referrer->count;
    	}
    }  

    $top_browsers = [];
    foreach ($browsers as $browser) {
      $top_browsers[ucwords($browser->browser)] = $browser->count;
    }
    $top_os = [];
    foreach ($os as $o) {
      $top_os[ucwords($o->os)] = $o->count;
    }

    arsort($top_referrers); 
    arsort($top_browsers); 
    arsort($top_os); 
		
		return $this->build([
								"error" => 0,
								"details" => [
										"shorturl" 		=> (empty($url->domain) ? $this->config["url"] : $url->domain)."/".$url->alias.$url->custom,
										"longurl" 		=> $url->url,
										"title" 			=> $url->meta_title,
										"description" => $url->meta_description,
										"location"		=> json_decode($url->location, TRUE),
										"device"			=> json_decode($url->devices, TRUE),
										"expiry"			=> $url->expiry,
										"date"				=> $url->date
								],
								"data" => [
										"clicks"  			 		=> $url->click,
										"uniqueClicks" 			=> $unique,
										"topCountries" 			=> $top_country,
										"topReferrers" 		 	=> $top_referrers,
										"topBrowsers"				=> $top_browsers,
										"topOs"							=> $top_os,
										"socialCount"	 => [
													"facebook" => $fb,
													"twitter"  => $tw,
													"google"   => $gl
										]
								]
						]);
	}

	/**
	 * [user urls description]
	 * @author KBRmedia <http://gempixel.com>
	 * @version 5.2
	 * @return  [type] [description]
	 */
	private function urls(){

		$sort = NULL;

		if(isset($_GET["limit"]) && is_numeric($_GET["limit"])) $sort["limit"] = Main::clean($_GET["limit"]);
		if(isset($_GET["order"]) && in_array($_GET["order"], ["date","click"])) $sort["order"] = Main::clean($_GET["order"]);
		
		$urls = $this->db->get("url", ["userid" => $this->user->id], $sort);

		$data = [];

		foreach ($urls as $url) {
			$data[] = [
									"alias" 					=> $url->alias.$url->custom,
									"shorturl"  			=> (empty($url->domain) ? $this->config["url"] : $url->domain)."/".$url->alias.$url->custom,
									"longurl"					=> $url->url,
									"clicks"					=> $url->click,
									"title"						=> $url->meta_title,
									"description"			=> $url->meta_description,
									"date"						=> $url->date,
							 ];
		}
		
		return $this->build(["error" => "0", "data" => $data]);
	}
	/**
	 * [error description]
	 * @author KBRmedia <http://gempixel.com>
	 * @version 5.2
	 * @param   [type] $code [description]
	 * @return  [type]       [description]
	 */
	private function error($code){
		$list = [
							"000" => "Wrong endpoint or invalid API request.",
							"001" => "API service is disabled.",
							"002" => "A valid API key is required to use this service.",
							"003" => "You have been banned for abuse.",
							"004" => "Please enter a valid URL.",
							"005" => "This URL couldn't be found. Please double check it.",
							"006" => "This URL is private or password-protected.",
							"007" => "You must send an alias paramater with URLs alias as the value.",
							"008" => "This URL does not exist or is not associated with your account.",
							"009" => "You account is either not active or banned for abuse.",
							"010" => "The redirection type is invalid.",
							"011" => "You do not have the permission to use the API system. Contact administrator.",
					];

		if(!isset($list[$code])) $code = "002";

		return $this->build(["error" => 1, "msg" => $list[$code]]);
	}
	/**
	 * [build description]
	 * @author KBRmedia <http://gempixel.com>
	 * @version 5.2
	 * @param   [type] $array [description]
	 * @param   string $text  [description]
	 * @return  [type]        [description]
	 */
	private function build($array, $text=""){
		// Set Header
		header("content-type: application/javascript");

		// JSONP Request
		if(isset($_GET["callback"])){
			return print("{$_GET["callback"]}(".json_encode($array).")");
		}

		// Text
		if(isset($_GET["format"]) && $_GET["format"] == "text"){
			header("content-Type: text/plain");
			return print($text);
		}

		// JSON
		return print(json_encode($array));		
	}
}