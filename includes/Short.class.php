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
class Short extends App{	
	/**
	 * Database and config
	 * @since 4.0
	 **/
	protected $db, $config = array(), $do = "", $action = "";
	/**
	 * The return URL Format. Default = Main or Second. URL. To use subdomains, use http://{?}.YOURDOMAIN.com
	 * @var string
	 * @example http://goo.gl
	 * @since 4.1
	 */	
	protected $return_url=NULL;
	/**
	 * Webpage screenshot generator
	 * @var string, Possible value: "pagepeeker","mshots","webthumb" or your custom API using @URL@ as the placeholder
	 * @example http://athumbservice.com/thumb.php?key=SomeKey&w=500&h=500&url=@URL@
	 * @see image() function
	 * @since 4.1
	 */	
	protected $thumb_provider = "webthumb";
	/**
	 * Redirect Override
	 * @var boolean, If TRUE, user can use $_GET["r"] to override. E.g. ?r=splash or ?r=frame
	 * @deprecated 4.0
	 * @since 3.0
	 */	
	private $redirect_override=FALSE;
	/**
	 * Self-Shortening
	 * @var boolean, If TRUE, it will be possible to shorten the URL of the domain where this script is located.
	 * @since 3.0
	 */	
	private $self_shortening=FALSE;
	/**
	 * Anti-Flood Time
	 * @var integer Minutes, Stats will not be updated when the same visitor clicks the same url for this amount of time
	 * @since 4.0
	 */	
	private $anti_flood=30;	
	/**
	 * Reserved system aliases
	 * @var array of reserved keywords used by the application. These cannot and should not be used in anyway!
	 * @since 4.1
	 */	
	private $aliases = array("admin","includes","static","themes","content");		
	/**
	 * List of URL extensions to not allow! Feel free to add to the list.
	 * @example http://somesite.com/virus.exe will not be allowed
	 * @since 4.1
	 **/
	private $executables = array(".exe",".dll",".bin",".dat",".osx");
	/**
	 * Constructor: Checks logged user status
	 * @since 5.0
	 **/
	public function __construct($db,$config){
  	$this->config=$config;
  	$this->db=$db;
  	$this->db->object=TRUE;
  	// Clean Request
  	if(isset($_GET)) $_GET=array_map("Main::clean", $_GET);
		$this->http=((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443)?"https":"http");
		$this->check();
	}
	/**
	 * Run Short Class
	 * @since 5.2
	 **/	
	protected function analyze($action, $do){
		$this->action =$action;
		$this->do = $do;
		// Shorten URL
		if($this->action=="shorten"){
			if(!isset($_POST["url"])) return $this->_404();	
			if(isset($_POST["urls"]) && isset($_POST["multiple"]) && $_POST["multiple"]=="1"){
				$array = $this->bulk();
			}else{
				$array = $this->add();
			}
			if($array){
				echo json_encode($array);
			}
			return;
		}
		// Stats
		if(strpos($this->action," ") || strpos($this->action,"+")) {
			$this->action=rtrim(rtrim($this->action," "),"+");
			return $this->stats();
		}

		// Run Subactions
		if(in_array($this->do,array("qr","i","ico")) && method_exists("Short", $this->do)){
			$fn = $this->do;
			return $this->$fn();
		}

		return $this->redirect();
	}			
	/**
	 * Main method that analyzes, validates and shortens a URL
	 * @since 5.6.5
	 **/
	public function add($array=array(),$option=array()){
		// Kill the bots
		if(Main::bot()) die($this->_404());
		// if custom array is not try to use post
		if(empty($array) && isset($_POST) && !empty($_POST)){
			$array = $_POST;
		}

		if(isset($option["api"]) && $option["api"] && isset($option["user"])){
			$this->user = $option["user"];
			$this->user->id = $option["user"]->id;
		}

		// Validate URL
		$url=$this->validate(Main::clean($array["url"],3,FALSE));
		// Get domain name
		$domain = Main::domain($array["url"],TRUE,FALSE);
    // Get extension
    $ext = Main::extension($url);
		
		$hash = str_replace("www.","",Main::domain($url,TRUE,FALSE));

		// Plug in Pre Shorten
		Main::plug("pre_shorten", $array);

    // If require registration
		if($this->config["require_registration"] && !isset($option["api"]) && !isset($this->user)) return array('error' => 1, 'msg' => e('Please create a free account or login to shorten URLs.'));

		if(isset($this->user) && $this->user->plan->numurls > 0 && $this->count("user_urls") >= $this->user->plan->numurls) return array('error' => 1, 'msg' => e('You have reached your maximum links limit. Please upgrade to another plan.'));

		// Check if private
		if($this->config["private"] && !isset($this->user)) return array('error' => 1, 'msg' => e('This service is meant to be used internally only.'));

		// Validate Request
		if(empty($url) || !$url) return array('error' => 1, 'msg' => e('Please enter a valid URL.'));

		// Prevent self-shortening
		if($domain == Main::domain($this->config["url"]) && !$this->self_shortening) return  array('error' => 1, 'msg' => 'You cannot shorten URLs of this website.');	

		// Check domain is blacklisted
		if($this->blacklist_domain($url)) return  array('error' => 1, 'msg' => e('This domain name has been blacklisted.'));	

		// Match the domain to the list of keywords
		if($this->config["adult"] && $this->blacklist_keywords($url)) return  array('error' => 1, 'msg' => e('This URL contains blacklisted keywords.'));	

		// Checks URL with Google
		if(!$this->safe($url)) return  array('error' => 1, 'msg' => e('URL is suspected to contain malwares and other harmful content.'));

		// Checks URL with Phistank
		if($this->phish($url)) return  array('error' => 1, 'msg' => e('URL is suspected to contain malwares and other harmful content.'));

		// Check if URL is linked to .exe, .dll, .bin, .dat, .osx,
		if($this->config["adult"] && in_array($ext,$this->executables)) return  array('error' => 1, 'msg' => e('Linking to executable files is not allowed.'));

		// Check expiration
		if(isset($_POST["expiry"]) && !empty($_POST["expiry"]) && strtotime("now") > strtotime($_POST["expiry"])) return array('error' => 1, 'msg' => e('The expiry date must be later than today.'));

		// Check domain name

		if(isset($this->user) && isset($array["domain"]) && $this->db->get("domains", ["userid" => $this->user->id, "domain" => $array["domain"], "status" => "1"])){
			$this->config["url"] = trim($array["domain"]);
		}

		// Validate selected domain name
		if(isset($array["domain"]) && $this->validate_domain_names(trim($array["domain"]))){
			$this->config["url"] = trim($array["domain"]);
		}

		if(empty($array["domain"]) && $this->config["root_domain"]) {
			$array["domain"] = $this->config["url"];
		}

		// Check Captcha
		if($this->config["captcha"] && !isset($this->user) && !isset($_GET["bookmark"])){
			$captcha = Main::check_captcha($array);
			if($captcha!='ok'){
				return array('error' => 1, 'msg' => $captcha,'html'=>'captcha');
			}
		}	

		// Check custom alias
		if(isset($array["custom"]) && !empty($array["custom"])){			
			if(strlen($array["custom"]) < 3){
				return array('error' => 1, 'msg' => e('Custom alias must be at least 3 characters.'));
			}elseif($this->blacklist_keywords($array["custom"])){
				return array('error' => 1, 'msg' => e('Inappropriate aliases are not allowed.'));

			}elseif($this->db->get("url","custom=? AND domain = ?","",array(Main::slug(Main::clean($array["custom"])),$array["domain"]))){
				return array('error' => 1, 'msg' => e('That alias is taken. Please choose another one.'));

			}elseif($this->db->get("url","alias=? AND domain = ?","",array(Main::slug(Main::clean($array["custom"])),$array["domain"]))){
				return array('error' => 1, 'msg' => e('That alias is taken. Please choose another one.'));

			}elseif($this->reserved_alias($array["custom"])){
				return array('error' => 1, 'msg' => e('That alias is reserved. Please choose another one.'));
			}elseif($this->premium_alias($array["custom"])){
				return array('error' => 1, 'msg' => e('That is a premium alias and is reserved to only pro members.'));
			}
		}
		// Generate formatted list of countries
		if(!empty($array['location'][0]) && !empty($array['target'][0])){
			foreach ($array['location'] as $i => $country) {
				if(!empty($country) && !empty($array['target'][$i])){

					if(!$this->safe($array['target'][$i]) || $this->phish($array['target'][$i])) continue;
					$countries[strtolower(Main::clean($country))] = $this->validate(Main::clean($array['target'][$i],3,FALSE));
			  }
			}
			$countries = json_encode($countries);
		}else{
			$countries = '';
		}

		// Generate formatted list of devices
		if(!empty($array['device'][0]) && !empty($array['dtarget'][0])){
			foreach ($array['device'] as $i => $device) {
				if(!empty($device) && !empty($array['dtarget'][$i])){

					if(!$this->safe($array['dtarget'][$i]) || $this->phish($array['dtarget'][$i])) continue;
					$devices[strtolower(Main::clean($device))]=$this->validate(Main::clean($array['dtarget'][$i],3,FALSE));
			  }
			}
			$devices = json_encode($devices);
		}else{
			$devices = '';
		}

		$pixels = "";
		if(isset($this->user) && $this->user->pro && isset($_POST["pixels"]) && is_array($_POST["pixels"])){
			$pixels = [];
			foreach ($_POST["pixels"] as $pixel) {
				if(in_array($pixel, ["facebook","adwords","linkedin"])){
					$pixels[] = trim($pixel);
				}
			}			
			$pixels = implode(",", $_POST["pixels"]);
		}

		if(!isset($array["type"])) {
			$array["type"] = "";
		}

		// Generate formatted list of countries
		if(!empty($array['paramname'][0]) && !empty($array['paramvalue'][0])){
			$parameters = [];
			foreach ($array['paramname'] as $i => $paramname) {
				if(!empty($paramname) && !empty($array['paramvalue'][$i])){
					$parameters[Main::clean($paramname, 3, true)] = Main::clean($array['paramvalue'][$i], 3, true);
			  }
			}
			$parameters = json_encode($parameters);
		}else{
			$parameters = '';
		}

		// If logged and URL is already shortened, retrieve it
		if(isset($this->user) && $this->user->id && (!isset($array["password"]) || empty($array["password"])) &&  (!isset($array["custom"]) || empty($array["custom"]))){
			if($data = $this->db->get("url",array("url"=>"?","userid"=>"?","location"=>"?","pass"=>"?", "devices" => "?", "pixels" => "?","type" => "?"), array("limit"=>"1"), array($url, $this->user->id, $countries, "", $devices, "", ""))){
				return $this->build($data->alias.$data->custom);
			}
		}
		// If not logged and and URL is already shortened, retrieve it
		if(!isset($this->user) && (!isset($array["password"]) || empty($array["password"])) &&  (!isset($array["custom"]) ||empty($array["custom"]))){
			if($data=$this->db->get("url",array("url"=>"?","userid"=>"?","location"=>"?","pass"=>"?","custom"=>"?"),array("limit"=>1),array($url,0,"","",""))){
				// Add to public history			
				$this->check_history($data->alias.$data->custom);
				return $this->build($data->alias.$data->custom);			
			}			
		}	

		// Maximum number of URls
		if(isset($this->user) && $this->user->pro && $this->user->plan->numurls > 0 && $this->count_user_urls() >= $this->user->plan->numurls) return array('error' => 1, 'msg' => e('You have maxed your short URLs limit. Either delete existing URLs or upgrade to a premium plan.'));

		if(isset($array["type"]) && ($array["type"] == "frame" || preg_match("!overlay-!", $array["type"]))){
			if(Main::iframePolicy($url)){
				return array('error' => 1, 'msg' => e('This URL cannot be used with this redirect method because browsers will prevent it for security reasons.'));
			}
		}


		$custom = "";
		$alias = "";
		if(isset($array["custom"]) && !empty($array["custom"])){
			$custom = Main::slug(Main::clean($array["custom"],3,TRUE));
		}else{
			$alias = $this->alias();
		}

		// Add to public history
		if(!isset($option["noreturn"])){
			if(!isset($this->user)){
				$this->check_history($alias.$custom);
			}			
			$this->echoBuild($alias.$custom);			
		}

		// Get meta data
		$meta_title = "";
		$meta_description = "";

		$protocol = explode("://", $array["url"], 2)[0];		
		$schemes = explode(",", $this->config["schemes"]);

		$schemes = array_diff($schemes, ["http", "https", "ftp"]);

		$preg_schemes = implode("://|", $schemes);


		// Check link has meta data
		if(in_array($ext, array(".zip",".rar",".7z",".flv",".mp4",".avi",".mp3",".jpeg",".png",".jpg",".gif",".mk4",".iso"))){
			$meta_title = "This is a downloadable file.";
			$meta_description = "Please note that this short URL is linked to a downloadable file.";
		
		}elseif($preg_schemes && preg_match('~('.$preg_schemes.'://)(.*)~', $array["url"])){
			$meta_title = "";
			$meta_description = "This url is using a custom protocol: {$protocol}";

		}else{	
			$info = Main::get_meta_data($url, TRUE);
			if(!empty($info)){
				$meta_title = Main::clean($info['title'],3,TRUE);
				$meta_description = Main::clean($info['description'],3,TRUE);
			}			
		}
		
		// Let's register new URL
		$data = array(
			":alias" => $alias,
			":custom" => $custom,
			":url" => $url,
			":description" => isset($array["description"]) && !empty($array["description"]) ? Main::clean($array["description"],3,TRUE) : "",
			":location" => $countries,
			":devices" => $devices,
			":date" => "NOW()",
			":pass" => isset($array["password"]) ? Main::clean($array["password"],3,TRUE) : "",
			":meta_title" => $meta_title,
			":meta_description" => $meta_description,
			":userid" => isset($this->user) ? $this->user->id : "0",
			":domain" => isset($array["domain"]) ? trim($array["domain"]) : "",
			":pixels" => $pixels,
			":parameters" => $parameters,
			":expiry" => (isset($_POST["expiry"]) && !empty($_POST["expiry"]) && strtotime("now") < strtotime($_POST["expiry"]) ? date("Y-m-d", strtotime($_POST["expiry"])) : NULL),
			":public" => isset($this->user) ? $this->user->public : 1 // Based on user settings.
		);		

		if(isset($array["private"])) $data[":public"] = "0";
		
		// Custom redirect
		if(($this->config["frame"] == "3" || (isset($this->user) && $this->user->pro)) && isset($array["type"]) && (is_numeric($array["type"]) || in_array($array["type"], array("direct","frame","splash")))) {
			$data[":type"]=Main::clean($array["type"],3,TRUE);
		}
		if($this->pro() && isset($array["type"]) && preg_match("~overlay-(.*)~", $array["type"])){
			$data[":type"] = $array["type"];
		}

		// Save to Database
		if($this->db->insert("url",$data)){
			if(!isset($option["noreturn"])) return NULL;

			if(!isset($this->user)){
				$this->check_history($alias.$custom);
			}				
			return $this->build($alias.$custom);
		}
	}
	/**
	 * Build return URL
	 * @since 5.0
	 */
	protected function build($data){

		if(!is_null($this->return_url)){
			if(preg_match("~{?}~", $this->return_url)){
				return array("error"=>0,"short" => str_replace("{?}",$data,$this->return_url));
			}else{
				return array("error"=>0,"short" => "{$this->return_url}/{$data}");
			}
		}
		return array("error"=>0,"short" => "{$this->config["url"]}/{$data}");
	}	
	/**
	 * [echoBuild description]
	 * @author KBRmedia <http://gempixel.com>
	 * @version 5.0
	 * @param   [type] $data [description]
	 * @return  [type]       [description]
	 */
	protected function echoBuild($data){

		if(!is_null($this->return_url)){
			if(preg_match("~{?}~", $this->return_url)){
				return print(json_encode(array("error"=>0,"short" => str_replace("{?}",$data,$this->return_url))));
			}else{
				return print(json_encode(array("error"=>0,"short" => "{$this->return_url}/{$data}")));
			}
		}
		return print(json_encode(array("error"=>0,"short" => "{$this->config["url"]}/{$data}")));
	}			
	/**
	 * Bulk URL Shortening
	 * @since 4.0
	 */	
	private function bulk($numURLs = 10){
		$urls="";

		// Check Captcha
		if(!$this->logged()){
			$captcha=Main::check_captcha($_POST);
			if($captcha!='ok'){
				return array('error' => 1, 'msg' => $captcha,'html'=>'captcha');
			}
		}	
 	  $url = explode("\n",$_POST["urls"]);
 	  $i = 0;
 	  foreach($url as $link){
 	  	if($i > $numURLs) break;
 	  	if(!empty($link)){
 	  		$array = array("url"=>trim($link));
 	  		if(isset($_POST["domain"])){
 	  			$array["domain"] = Main::clean($_POST["domain"],3,TRUE);
 	  		}
 	  		if(isset($_POST["type"]) && $this->pro()){
 	  			$array["type"] = Main::clean($_POST["type"],3,TRUE);
 	  		}
 	  		$this->config["captcha"]=FALSE;
				$short = $this->add($array, ["noreturn" => TRUE]);
	    	if(!$short["error"]){
	    		$urls.=$link." => ".$short["short"]." \n";
	    	}else{		    		
	    		$urls.=$link." => ".$short["msg"]." \n";
	    	}
 	  	}
 	  	$i++;
 	  }
 		echo json_encode(array("error"=>0, "confirm"=>1, "short"=> str_replace("\r", "", $urls)));	 	  
	}	
	/**
	 * Check Anon history
	 * @since 4.1
	 */
	private function check_history($alias){
		if($anonid = Main::cookie("aid")){
			$urls = json_decode($anonid,TRUE);
			if(!in_array($alias, $urls)){
				$urls[] = $alias;
				$new = array_reverse($urls);
				$keep = array_slice($new, 0, 9);
				Main::cookie("aid",json_encode($keep),60*24*365);		
			}
		}else{
			Main::cookie("aid",json_encode(array($alias)),60*24*365);      	
		}				
	}	
	/**
	 * Redirect
	 * @since 5.6.3
	 */	
	private function redirect(){
		// Filter do
		$this->filter($this->do);

		$current = $_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"];

		$current = str_replace("/".urlencode($this->action), "", $current);
		
		$current = explode("?", $current)[0];


		if("http://".$current == $this->config["url"] || "https://".$current == $this->config["url"]){
			// Fetch URL and show 404 if doesn't exist
			if(!$url=$this->db->get("url","(BINARY alias=:id OR BINARY custom=:id) AND domain LIKE :domain",array("limit"=> 1),array(":id"=>$this->action, ":domain" => "%$current"))){

				if(!$url=$this->db->get("url","(BINARY alias=:id OR BINARY custom=:id) AND domain IS NULL",array("limit"=> 1),array(":id"=>$this->action))){

					$url=$this->db->get("url","(BINARY alias=:id OR BINARY custom=:id) AND domain = ''",array("limit"=> 1),array(":id"=>$this->action));
				}
			}
		}else{
			// Fetch URL and show 404 if doesn't exist
			if(!$url=$this->db->get("url","(BINARY alias=:id OR BINARY custom=:id) AND domain LIKE :domain",array("limit"=> 1),array(":id"=>$this->action, ":domain" => "%$current"))){
				return $this->_404();
			}			
		}

		if(!$url) return $this->_404();

		$url->url = str_replace("&amp;","&",$url->url);

		if(!empty($url->parameters) && $params = json_decode($url->parameters, false)){
			if(strpos($url->url, "?")){
				$url->url = $url->url."&".http_build_query($params);
			}else{
				$url->url = $url->url."?".http_build_query($params);
			}
		}
		
		if($url->userid > 0){
			$current = $_SERVER["HTTP_HOST"];
			$user = $this->db->get("user", ["id" => $url->userid], ["limit" => "1"]);

			$list = [
				str_replace(["http://", "https://"],"", $this->config["url"])
			];

			if($domains = $this->db->get("domains", ["userid" => $user->id, "status" => "1"])) {
				foreach ($domains as $domain) {
					$list[] = str_replace(["http://", "https://"],"", $domain->domain);
				}
			}

			if($this->config["multiple_domains"]){
				$domains = explode("\n", $this->config["domain_names"]);
				foreach ($domains as $d) {
					if(empty($d)) continue;
					$list[] = str_replace(["http://", "https://"],"", trim($d));
				}
			}
			$found = 0;
			foreach ($list as $l) {
				if(strpos("!$l!i", $current)) $found = 1;
			}

			if(!$found) return $this->_404();
		}

		// Check if expired
		if(!empty($url->expiry) && strtotime("now") > strtotime($url->expiry)){
			return $this->custom404("Expired");
		}

		// Add short URL
		if(!isset($_SESSION["{$url->id}_passcheck"]) || $_SESSION["{$url->id}_passcheck"]!==TRUE){
			$_SESSION["{$url->id}_passcheck"]=FALSE;
		}
		// Note: Password check is stored in a session. User will have access until the browser is closed.
		if(isset($_POST["token"])){
			// Validate CSRF Token
			if(!Main::validate_csrf_token($_POST["token"])){
				return Main::redirect(Main::href($this->action,"",FALSE),array("danger",e("Invalid token. Please try again.")));
			}
			// if encryted Password (old version)
			if(strlen($url->pass)>= 32) $_POST["password"] = md5($_POST["password"]);
			// Check Password
			if($_POST["password"]!==$url->pass){
				return Main::redirect($this->action,array("danger",e("Please enter a valid password.")));
			}
			// Set Session
			$_SESSION["{$url->id}_passcheck"]=TRUE;
		}

		// Let's check if it is password-protected
		if(!empty($url->pass) && (!isset($_SESSION["{$url->id}_passcheck"]) || !$_SESSION["{$url->id}_passcheck"])){
			return $this->pass($url);
		}
		// Check if is facebook bot
		if(Main::isSocial()){
			return $this->botDirect($url);
		}

		// Update stats
		$this->update($url);
		// Check if URL is geotargeted
		if(!empty($url->location) && $this->config["geotarget"]){
			$country=$this->country();			
			$location=json_decode($url->location,TRUE);							
			if (isset($location[$country]) && $country) {
				$url->url=$location[$country];
			}
		}
		if(!empty($url->devices) && $this->config["devicetarget"]){
			$device = $this->device();			
			$devices = json_decode($url->devices,TRUE);							
			if (isset($devices[$device]) && $device) {
				$url->url = $devices[$device];
			}
		}	
		// Active Security Check
		if(!$this->safe($url->url)){
			return $this->unsafe();
		}
		// Check with Phish
		if($this->phish($url->url)){
			return $this->unsafe();
		}				
		// Set Meta info
		if(!empty($url->meta_title)) Main::set("title",$url->meta_title);
		if(!empty($url->meta_description)) Main::set("description",$url->meta_description);
		Main::set("url","{$this->config["url"]}/{$url->alias}{$url->custom}");
		Main::set("image","{$this->config["url"]}/{$url->alias}{$url->custom}/i");	
		Main::video($url->url);

		// Get User info
		if($url->userid !=0 && $user = $this->db->get(array("count"=>"id,admin,banned,media,splash_opt,pro,expiration,overlay,fbpixel,linkedinpixel,adwordspixel,quorapixel","table"=>"user"),array("id"=>$url->userid),array("limit"=>1))){			
			// Disable URLs of user is banned
			if($user->banned) return $this->_404();
			// If membership expired, switch to free
			if($user->pro && time() > strtotime($user->expiration)) $this->db->update("user",array("pro"=>0),array("id"=>$user->id));
			$url->media = $user->media;
			$url->pro = $user->pro;
			if($user->admin) $url->pro = 1;
			$url->userpixels = ["facebook" => $user->fbpixel, "linkedin" => $user->linkedinpixel, "adwords" => $user->adwordspixel];
		}else{
			$url->media = $this->config["show_media"];
			$url->pro=0;
		}
		if(!$this->config["pro"]){
			$url->pro = 1;
		}
		$url->short= (empty($user->domain) ? $this->config["url"] : $user->domain)."/".$url->alias.$url->custom;

		if($url->pro && preg_match("~overlay-(.*)~", $url->type) && $overlay = $this->db->get("overlay", ["id" => str_replace("overlay-", "", $url->type), "userid" => $user->id], ["limit" => 1]))	{
			return $this->overlay($url, $overlay);
		}	
		// Custom Splash Page
		if(is_numeric($url->type) && $url->pro && $splash = $this->db->get("splash",array("id"=>"?","userid"=>"?"),array("limit"=>1),array($url->type,$url->userid))){
			return $this->custom($url, $splash);
		}
		
		// If is media, show media
		if($url->media && $media = $this->isMedia($url->url)){
			return $this->media($url,$media);
		}
		// Check redirect method
		if($this->config["frame"]=="3" || $url->pro){
			if(empty($url->type)){
				return $this->direct($url);
			}elseif(in_array($url->type, array("direct","frame","splash"))){
				$fn = $url->type;
				return $this->$fn($url);
			}
		}

		// Switch to a method
		$methods = array("0"=>"direct","1"=>"frame","2"=>"splash", "3"=> "splash");
		$fn = $methods[$this->config["frame"]];
		return $this->$fn($url);
	}
	/**
	 * Update Stats
	 * @since 5.6.3
	 **/
	private function update($url){
		// Prevents Bots
		if(Main::bot()) return FALSE;
		// Check user visited recently
		if(Main::cookie("short_{$this->action}")) return FALSE;

		// Check Limit	
		if($url->userid && $user = $this->db->get("user", ["id" => $url->userid], ["limit" => 1])){
			$count = $this->db->count("stats", "MONTH(date) = MONTH(NOW()) AND YEAR(date) = YEAR(NOW()) AND urluserid = '{$url->userid}'");	
			$plan = $this->db->get("plans", ["id" => $user->planid], ["limit" => 1]);
			if($plan && $plan->numclicks > 0 && $count >= $plan->numclicks) return FALSE;
		}

		// Update clicks
		if($this->db->update("url",array("click" => "click+1"),array("id"=>":a"),array(":a"=>$url->id))){
			// Set cookie to prevent flooding valid for XX minutes
			Main::cookie("short_{$this->action}",'1',$this->anti_flood);
		}

		if(!$this->db->get("stats", ["urlid" => $url->id, "ip" => Main::ip()], ["limit" => 1])){
			$this->db->update("url",array("uniqueclick" => "uniqueclick+1"),array("id"=>":a"), array(":a"=>$url->id));
		}		

		if($this->config["tracking"]=="1"){
			// System Analytics
			if(isset($_SERVER["HTTP_REFERER"]) && !empty($_SERVER["HTTP_REFERER"]) && !is_null($_SERVER["HTTP_REFERER"])){
				$referer = Main::clean($_SERVER["HTTP_REFERER"],3,TRUE);
				$domain = parse_url($referer);
				if(isset($domain["host"])){
					$domain = $domain["scheme"]."://".$domain["host"];
				}else{
					$domain = "";
				}
			}else{
				$referer = "direct";
				$domain = "";
			}
			$data = array(
					":short" => $this->action,
					":urlid" => $url->id,
					":urluserid" => $url->userid,
					":date" => "NOW()",
					":country" => $this->country(),
					":referer" => $referer,
					":domain" => $domain,
					":ip" => Main::ip(),
					":os" => Main::os(),
					":browser" => Main::browser()							
				);
			// Save data
			$this->db->insert("stats",$data);
		}
		return FALSE;				
	}
	/**
	 * [overlay description]
	 * @author KBRmedia <http://gempixel.com>
	 * @version 1.0
	 * @return  [type] [description]
	 */
	private function overlay($url, $overlay){
		// Inject GA code
		if(!empty($this->config["analytic"])){					
			Main::add("<script type='text/javascript'>(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)})(window,document,'script','//www.google-analytics.com/analytics.js','ga');ga('create', '{$this->config["analytic"]}', '".Main::domain($this->config["url"])."');ga('send', 'pageview');</script>","custom",FALSE);
		}					
		if($this->sandbox==TRUE) {
			// Developement Stylesheets
			Main::add("<link rel='stylesheet/less' type='text/css' href='{$this->config["url"]}/themes/{$this->config["theme"]}/style.less'>","custom",false);
			Main::cdn("less");
		}		

		$overlay_data = "";

		if($url->pro){
			$this->injectPixels($url->pixels, $url->userid);
		}

		if(!$this->url_framed($url->url)) return $this->direct($url);
		
		$data = json_decode($overlay->data);

		if($overlay->type == "message"){
			$overlay_data = "<div class='custom-message {$data->position}' style='background-color:{$data->bg} !important'>
        <div class='".($data->text ? 'notclickable' : 'clickable')."'>
          ".(!empty($data->label) ? "<div class='custom-label' style='background-color:{$data->labelbg};color:{$data->labelcolor}'>{$data->label}</div>" : "")."
          ".($data->image ? "<span class='custom-img'><img src='{$this->config["url"]}/content/{$data->image}' alt='{$data->message}'></span>" : "")."
          <p style='color:{$data->color}'>
            <span class='custom-text'>{$data->message}</span> 
          ".($data->text ? "
              <a href='{$data->link}' class='btn btn-xs' style='background-color: {$data->btnbg};color: {$data->btncolor}'>{$data->text}</a>              
          " : "")."
          </p>
        </div>
        <a href='{$url->url}' class='remove'><i class='glyphicon glyphicon-remove-sign'></i></a>
      </div>";
			if(empty($data->text)) Main::add('<script>$(document).ready(function(){ $(".clickable").click(function() { window.location = "'.$data->link.'"; });});</script>', "custom", true);      
		}

		if($overlay->type == "contact"){
			$overlay_data = "<div class='contact-overlay {$data->position}'><a href='' style='color: {$data->color};background-color:{$data->bg} !important' href='#cev' class='contact-event'><i class='fa fa-question' style='color: {$data->btncolor};background-color:{$data->btnbg} !important'></i> ".($data->label ? $data->label : "Need Help?")."</a>
				<div class='contact-box' style='color: {$data->color};background-color:{$data->bg} !important'>
					<a href='' class='contact-close'  style='color: {$data->color}'><i class='fa fa-times'></i></a>
					<h1 class='contact-label' style='color: {$data->color};'>".($data->label ? $data->label : "Need Help?")."</h1>
					<p class='contact-description' style='color: {$data->color};'>".($data->content ? $data->content : "")."</p>
					<form class='contact-form'>
						<div class='form-group'>
							<label for='contact-name' class='control-label' style='color: {$data->color};'>".($data->lang->name ? $data->lang->name : "Name")."</label>
							<input type='text' class='form-control' id='contact-name' placeholder='John Smith' style='color:{$data->inputcolor};background-color:{$data->inputbg} !important' name='name' data-required='true'>
						</div>
						<div class='form-group'>
							<label for='contact-email' class='control-label' style='color: {$data->color};'>".($data->lang->email ? $data->lang->email : "Email")."</label>
							<input type='email' class='form-control' id='contact-email' placeholder='johnsmith@company.com' style='color:{$data->inputcolor};background-color:{$data->inputbg} !important' name='email' data-required='true'>
						</div>		
						<div class='form-group'>
							<label for='contact-message' class='control-label' style='color: {$data->color};'>".($data->lang->message ? $data->lang->message : "Message")."</label>
							<textarea class='form-control' id='contact-message' placeholder='Your message' style='color:{$data->inputcolor};background-color:{$data->inputbg} !important' name='message' data-required='true'></textarea>
						</div>
						".Main::captcha()."
						".Main::csrf_token(true)."
						<input type='hidden' name='integrity' value='".str_replace("=", "", base64_encode(Main::strrand(5).".".$overlay->id))."'>
						<button type='submit' class='contact-btn' style='color:{$data->btncolor};background-color:{$data->btnbg} !important'>".($data->lang->button ? $data->lang->button : "Send")."</button>	
					</form>															
				</div></div>";			     
		}
		if($overlay->type == "poll"){
			$overlay_data = "<div class='poll-overlay {$data->position}'>
				<div class='poll-box' style='color: {$data->color};background-color:{$data->bg} !important'>
					<p class='poll-question' style='color: {$data->color};'>{$data->question}</p>
					<form class='poll-form'>
						<ol class='poll-answers'>";
						foreach ($data->answers as $key => $el) {
							$overlay_data .= "<li style='color: {$data->color};'><label><input data-class='grey' type='radio' name='answer' value='{$key}'> {$el->option}</label></li>";
						}
			$overlay_data .="</ol>
						".Main::csrf_token(true)."
						<input type='hidden' name='integrity' value='".str_replace("=", "", base64_encode(Main::strrand(5).".".$overlay->id))."'>
						<button type='submit' class='poll-btn' style='color:{$data->btncolor};background-color:{$data->btnbg} !important'>Vote</button>	
					</form>															
				</div></div>";		
				Main::cdn("icheck");
		}
		include($this->t(__FUNCTION__));
	}
	/**
	 * Splash
	 * @since 4.2.1
	 **/
	private function splash($url){

		if($url->pro){
			$this->injectPixels($url->pixels, $url->userid);
		}

		// Add timer animation	
		if(!empty($this->config["timer"]) || $this->config["timer"] !=="0"){
			Main::add('<script type="text/javascript">var count = '.$this->config['timer'].';var countdown = setInterval(function(){$("a.redirect").attr("href","#pleasewait").html(count + " seconds");if (count < 1) {clearInterval(countdown);$("a.redirect").attr("href","'.$url->url.'").html("Continue");}count--;}, 1000);</script>',"custom",FALSE);     
			// Main::add('<script type="text/javascript">var count = '.$this->config['timer'].';var countdown = setInterval(function(){$("a.redirect").attr("href","#pleasewait").html(count + " seconds");if (count < 1) {clearInterval(countdown);window.location="'.$url->url.'";}count--;}, 1000);</script>',"custom",FALSE);					
		}				

		// BlockAdblock
		if($this->config["detectadblock"] && !$url->pro){
			Main::cdn("blockadblock");
			Main::add('<script type="text/javascript">var detect = '.json_encode(["on" => e("Adblock Detected"), "detail" => e("Please disable Adblock and refresh the page again.")]).'</script>',"custom",FALSE);			
			Main::add("{$this->config["url"]}/static/detect.app.js","script",FALSE);		
		}				

		$this->header();
		include($this->t(__FUNCTION__));
		$this->footer();
	}
	/**
	 * Splash
	 * @since 4.2
	 **/
	private function custom($url,$splash){
		if($url->pro){
			$this->injectPixels($url->pixels, $url->userid);
		}		
		// Add timer animation	
		if(!empty($this->config["timer"]) || $this->config["timer"] !=="0"){

			Main::add('<script type="text/javascript">var count = '.$this->config['timer'].';var countdown = setInterval(function(){$(".c-countdown span").html(count);if (count < 1) {clearInterval(countdown);window.location="'.$url->url.'";}count--;}, 1000);</script>',"custom",FALSE);					
		}				
		$this->footerShow = false;
		$this->headerShow = false;
		$data = json_decode($splash->data);
		$data->avatar = Main::href("content/{$data->avatar}");
		$data->banner = Main::href("content/{$data->banner}");
		$this->header();
		include($this->t("custom.splash"));
		$this->footer();
	}	
	/**
	 * Frame
	 * @since 5.0
	 **/
	private function frame($url){
		if($url->pro){
			$this->injectPixels($url->pixels, $url->userid);
		}		
		// Inject GA code
		if(!empty($this->config["analytic"])){					
			Main::add("<script type='text/javascript'>(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)})(window,document,'script','//www.google-analytics.com/analytics.js','ga');ga('create', '{$this->config["analytic"]}', '".Main::domain($this->config["url"])."');ga('send', 'pageview');</script>","custom",FALSE);
		}					
		// BlockAdblock
		if($this->config["detectadblock"] && !$url->pro){
			Main::cdn("blockadblock");
			Main::add('<script type="text/javascript">var detect = '.json_encode(["on" => e("Adblock Detected"), "detail" => e("Please disable Adblock and refresh the page again.")]).'</script>',"custom",FALSE);			
			Main::add("{$this->config["url"]}/static/detect.app.js","script",FALSE);		
		}				
		if(!$this->url_framed($url->url)) return $this->direct($url);
		include($this->t(__FUNCTION__));
	}
	/**
	 * Direct Method
	 * @since 5.0
	 **/
	private function direct($url){
		if($url->pro && !empty($url->pixels)){
			$addPixels = $this->injectPixels($url->pixels, $url->userid);

			echo '<!DOCTYPE html>
						<html lang="en">
						<head>
						  <meta charset="UTF-8">
						  <title>'.$url->meta_title.' | '.$this->config["title"].'</title>			
						  <meta name="description" content="'.$url->meta_description.'" />
			  
						  <meta http-equiv="refresh" content="2;url='.$url->url.'">
						  <style>body{background:#f8f8f8; postition: relative;}.loader,.loader:after{border-radius:50%;width:5em;height:5em}.loader{position:absolute!important;top:38%;display:block;left:48%;left:calc(50vw - 5em);font-size:10px;text-indent:-9999em;border-top:1.1em solid rgba(128,128,128,.2);border-right:1.1em solid rgba(128,128,128,.2);border-bottom:1.1em solid rgba(128,128,128,.2);border-left:1.1em solid grey;-webkit-transform:translateZ(0);-ms-transform:translateZ(0);transform:translateZ(0);-webkit-animation:load8 1.1s infinite linear;animation:load8 1.1s infinite linear}@-webkit-keyframes load8{0%{-webkit-transform:rotate(0);transform:rotate(0)}100%{-webkit-transform:rotate(360deg);transform:rotate(360deg)}}@keyframes load8{0%{-webkit-transform:rotate(0);transform:rotate(0)}100%{-webkit-transform:rotate(360deg);transform:rotate(360deg)}}</style>
						  '.$addPixels.'
						</head>
						<body>
						  <div class="loader">Redirecting</div>
						</body>
						</html>';
			return;
		}		
		// Add header
		header('HTTP/1.1 301 Moved Permanently');
		header("Location: {$url->url}",true,301);
		return;
	}
	/**
	 * Redirect Facebook Bot
	 * @author KBRmedia <http://gempixel.com>
	 * @version 5.0
	 * @param   [type] $url [description]
	 * @return  [type]      [description]
	 */
	private function botDirect($url){
		// Add header
		header('HTTP/1.1 301 Moved Permanently');
		header("Location: {$url->url}",true,301);
		return;
	}
	/**
	 * Media Page
	 * @since 5.4.3
	 **/
	private function media($url, $data){
		if($url->pro){
			$this->injectPixels($url->pixels, $url->userid);
		}		
		if(!empty($this->config["analytic"])){					
			Main::add("<script type='text/javascript'>(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)})(window,document,'script','//www.google-analytics.com/analytics.js','ga');ga('create', '{$this->config["analytic"]}', '".Main::domain($this->config["url"])."');ga('send', 'pageview');</script>","custom",FALSE);
		}					
		// BlockAdblock
		if($this->config["detectadblock"] && !$url->pro){
			Main::cdn("blockadblock");
			Main::add('<script type="text/javascript">var detect = '.json_encode(["on" => e("Adblock Detected"), "detail" => e("Please disable Adblock and refresh the page again.")]).'</script>',"custom",FALSE);			
			Main::add("{$this->config["url"]}/static/detect.app.js","script",FALSE);		
		}				
		$url->shorturl = (!empty($url->domain) ? $url->domain : $this->config["url"] )."/".$url->alias.$url->custom;
		$url->embed = $this->embed($data);
		
		$this->header();
		include($this->t(__FUNCTION__));
		$this->footer();
	}
			/**
			 * Detect if URL is media
			 * @since 5.5.1
			 **/
			private function isMedia($url){
		    preg_match('((http://|https://|www.)([\w\-\d]+\.)+[\w\-\d]+)',$url, $r);
    		$host = @str_replace(".","", $r[2]);				
		    $hosts=array(
						"youtube" => "youtube\.(.*)\/watch\?v=([^\&\?\/]+)",
						"vimeo" => "vimeo\.(.*)\/([^\&\?\/]+)",
						"vine" => "vine\.(.*)\/v/([^\&\?\/]+)",
						"dailymotion" => "dailymotion\.(.*)\/video/([^\&\?\/]+)_([^\&\?\/]+)",
						"funnyordie" => "funnyordie\.(.*)\/videos/([^\&\?\/]+)",
						"collegehumor" => "collegehumor\.(.*)\/video/([^\&\?\/]+)\/([^\&\?\/]+)"
		    	);
		    if(array_key_exists($host, $hosts) && preg_match("~{$hosts[$host]}~", $url, $match)){
					return (object) array("host"=>$host,"id"=>$match[2],"url"=>$url);
		    }
		    return FALSE;
			}
			/**
			 * Embed Media
			 * @since 4.0
			 **/
			private function embed($data){
				if(!is_object($data))	return;
				$sites=array(
					// Youtube
					"youtube" => "<iframe id='ytplayer' type='text/html'  width='640' height='400' allowtransparency='true' src='//www.youtube.com/embed/{$data->id}?autoplay=1&origin={$this->config["url"]}' frameborder='0'></iframe>",
					// Vimeo
					"vimeo" => "<iframe src='//player.vimeo.com/video/{$data->id}' width='640' height='400' allowtransparency='true' frameborder='0' webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>",
					// Dailymotion
					"dailymotion" => "<iframe src='http://www.dailymotion.com/embed/video/{$data->id}' width='640' height='390' allowtransparency='true' frameborder='0' webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>",
					// FunnyOrDie
					"funnyordie" => "<iframe src='http://www.funnyordie.com/embed/{$data->id}' width='640' height='400' allowtransparency='true' frameborder='0' allowfullscreen webkitallowfullscreen mozallowfullscreen></iframe>",
					// Collegehumor
					"collegehumor" => "<iframe src='http://www.collegehumor.com/e/{$data->id}'  width='640' height='400' allowtransparency='true' frameborder='0' webkitAllowFullScreen allowFullScreen></iframe>",
					// Vine
					"vine" => "<iframe class='vine-embed' src='{$data->url}/embed/postcard' width='600' height='600' allowtransparency='true' frameborder='0'></iframe><script async src='//platform.vine.co/static/scripts/embed.js' charset='utf-8'></script>"
				);
				return $sites[$data->host];
			}
			/**
			 * Comments
			 * @since 4.1
			 **/
			protected function comment($appid=""){
				if(!empty($this->config["facebook_app_id"])) $appid=$this->config["facebook_app_id"];
				if(empty($appid)) return FALSE;

				$html='<div class="panel panel-default panel-body" id="'.__FUNCTION__.'">';
					$html.='<h3>'.e("Comments").'</h3>';  		
	  			$html.= '<script id="auto_css_facebook">$(document).ready(function(){$(".fb-comments").attr("data-width", $("#comment").width());FB.XFBML.parse($("#comment")[0]);});</script>';

	  		  $html.="<div id='fb-root'></div>
										<script>(function(d, s, id) {
										  var js, fjs = d.getElementsByTagName(s)[0];
										  if (d.getElementById(id)) return;
										  js = d.createElement(s); js.id = id;
										  js.src = '//connect.facebook.net/en_US/all.js#xfbml=1&appId=$appid';
										  fjs.parentNode.insertBefore(js, fjs);
										}(document, 'script', 'facebook-jssdk'));</script>
								<div class='fb-comments' data-href='{$this->config["url"]}/{$this->action}' data-width='600' data-numposts='5' data-colorscheme='light'></div>";
	      $html.="</div>";
	      return $html;
			}
	/**
	 * Show password form
	 * @since 5.4.3
	 **/
	private function pass($url){
		// Set Meta info
		Main::set("title",e("Enter your password to unlock this URL"));
		Main::set("description",e('The access to this URL is restricted. Please enter your password to view it.'));
		// BlockAdblock
		if($this->config["detectadblock"] && !$url->pro){
			Main::cdn("blockadblock");
			Main::add('<script type="text/javascript">var detect = '.json_encode(["on" => e("Adblock Detected"), "detail" => e("Please disable Adblock and refresh the page again.")]).'</script>',"custom",FALSE);			
			Main::add("{$this->config["url"]}/static/detect.app.js","script",FALSE);		
		}				
		// Let's show the password field
		$this->isUser=FALSE;
		$this->header();
		if(empty($url->domain)) $url->domain = $this->config["url"];
		echo '<section>
						<div class="container">    
							<div class="centered form">';
		echo '     '.Main::message().'
					      <form role="form" class="live_form" method="post" action="'.$url->domain.'/'.$this->action.'">
									<h3>'.e("Enter your password to unlock this URL").'</h3>
									<p>'.e('The access to this URL is restricted. Please enter your password to view it.').'</p>					      
					        <div class="form-group">
					          <label for="pass1">'.e("Password").'</label>
					          <input type="password" class="form-control" id="pass1" placeholder="Password" name="password">             
					        </div>        
					        '.Main::csrf_token(TRUE).'
					        <button type="submit" class="btn btn-primary">'.e("Unlock").'</button>        
					      </form>';
		echo '    </div>
						</div>
					</section>';
		$this->footer();
		return;
	}
	/**
	 * Stats Page
	 * @since 5.7.2
	 **/
	private function stats(){
		
		$this->action =  str_replace("+","", $this->action);

		if(!is_numeric($this->action)){
			$current = $_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"];

			$current = str_replace("/".urlencode($this->action)."+", "", $current);
			
			$current = explode("?", $current)[0];

			if("http://".$current == $this->config["url"] || "https://".$current == $this->config["url"]){
				
				$main = str_replace(["http://", "https://"], "", $this->config["url"]);
				$url = $this->db->get("url","(BINARY alias=:a OR BINARY custom=:a) AND (domain LIKE :b OR domain = :c OR domain IS NULL)", ["limit" => 1], [":a" => $this->action, ":b" => "%{$main}", ":c" => ""]);

			}else{

				$url = $this->db->get("url","(BINARY alias=:a OR BINARY custom=:a) AND domain LIKE :b", ["limit" => 1], [":a" => $this->action, ":b" => "%{$current}"]);
			}

			if(!$url) return Main::redirect("404");
			
			return Main::redirect($url->id."+");
		}


		if(!$url = $this->db->get("url", ["id" => ":id"], ["limit" => 1], [":id" => $this->action])) return $this->_404();
		
		// Check if user is anon and url is public		
		if(!$this->logged() && (!empty($url->pass) || !$url->public)) {		

			return Main::redirect(Main::href("user/login","",FALSE),array("danger",e("This URL is private and only the creator can access the stats. If you are the creator, please login to access it.")));
		}

		// Check if user is logged and is admin or owner
		if($this->logged() && !$url->public && !$this->admin()){
			if($this->user->id !== $url->userid) return Main::redirect(Main::href("user/login","",FALSE),array("danger",e("This URL is private and only the creator can access the stats. If you are the creator, please login to access it.")));
		}


		// Active Security Check
		if(!$this->safe($url->url)){
			return $this->unsafe();
		}
		// Check with Phish
		if($this->phish($url->url)){
			return $this->unsafe();
		}			

		$url->short=$this->config["url"]."/".$url->alias.$url->custom;

		if($this->config["tracking"] == 1){

			if($url->uniqueclick == "0"){
				
				$count = $this->db->count("stats","urlid = '{$url->id}' GROUP by ip");
				$this->db->update("url", ["uniqueclick" => $count], ["id" => $url->id]);

			}else{
				$count = $url->uniqueclick;
			}

			$url->unique = $count;

		}else{

			$url->unique = "n/a";

		}

		$url->fullurl = (!empty($url->domain) ? $url->domain : $this->config["url"])."/".$url->alias.$url->custom;

		Main::set("title",e("Stats for")." ".$url->meta_title);
		Main::set("description","Advanced statistics page for the short URL {$this->config["url"]}/{$url->alias}{$url->custom}.");
		Main::set("url","{$this->config["url"]}/{$url->alias}{$url->custom}");
		Main::set("image","{$this->config["url"]}/{$url->alias}{$url->custom}/i");
		Main::cdn("flot");
		Main::add("{$this->config["url"]}/static/js/Chart.min.js");
		Main::add("{$this->config["url"]}/static/js/jvector.js");
		Main::add("{$this->config["url"]}/static/js/jvector.world.js");
		Main::add("{$this->config["url"]}/analytic/".str_replace("=", "", base64_encode("{$url->id}:{$url->click}"))."?token={$this->config["public_token"]}");
		$this->header();
		include($this->t(__FUNCTION__));
		$this->footer();
	}
	/**
	 * API
	 * @since 5.0
	 * @deprecated 5.4
	 **/
	protected function _api_(){
		// Validate request
		if(!$this->config["api"]) return $this->api_build(array("error"=>1,"msg"=>"API service is disabled."));		
		
		// Check API Key
		if(!isset($_GET["key"]) && !isset($_GET["api"])) return $this->api_build(array("error"=>1,"msg"=>"A valid API key is required to use this service."));
		
		$key = isset($_GET["key"]) ? $_GET["key"] : $_GET["api"];
		// Get user
		if(!$user = $this->db->get("user",array("api"=>"?"),array("limit"=>1),array($key))) return $this->api_build(array("error"=>1,"msg"=>"A valid API key is required to use this service."));
		$this->user->id = $user->id;
		// Check if banned
		if($user->banned){					
			return $this->api_build(array("error"=>1,"msg"=>"You have been banned for abuse."));
		}		
		$array["domain"] = $user->domain;
		$this->config["captcha"] = 0;
		$this->config["private"] = 0;
		$this->config["user"] = 1;
		$this->config["require_registration"] = 0;
		// Check Request type
		if(!isset($_GET["url"]) && !isset($_GET["short"])) return $this->api_build(array("error"=>1,"msg"=>"Please enter a valid URL."));
	
		// Check if shorten request is made
		if(isset($_GET["url"])){
			$array = array();
			$array["url"]	= Main::clean($_GET["url"],3,TRUE);
			
			if(isset($_GET["custom"]) && !empty($_GET["custom"])) $array["custom"] = Main::slug($_GET["custom"]);
			if($user->pro) $array["type"] = $user->defaulttype;

			$result = $this->add($array, ["noreturn" => TRUE]);
			return $this->api_build($result,isset($result["short"]) ? $result["short"] :"");
		}

		// Check if retrieval request is made
		if(isset($_GET["short"])){
			$g = parse_url($_GET["short"]);
			$g = explode("/",$g["path"]);
			$g = array_reverse($g);
			if(!$url = $this->db->get("url","alias=:q OR custom=:q",array("limit"=>1),array(":q"=>$g[0]))) return $this->api_build(array("error"=>1,"msg"=>"This URL couldn't be found. Please double check it."));
			if((!empty($url->pass) || !$url->public) && $url->userid !== $user->id) return $this->api_build(array("error"=>1,"msg"=>"This URL is private or password-protected."));

			$array = array(
					"error" => 0,
					"long" => $url->url,
					"click" => $url->click,
					"date" => $url->date,
					"location" => json_decode($url->location,TRUE)
				);
			return $this->api_build($array,$url->url);
		}
		return;
	}
	/**
	 * API Build
	 * @since 4.0
	 **/
	private function api_build($array,$text=""){
		header("content-type: application/javascript");
		// JSONP Request
		if(isset($_GET["callback"])){
			return print("{$_GET["callback"]}(".json_encode($array).")");
		}
		// Text
		if(isset($_GET["format"]) && $_GET["format"]=="text"){
			header("content-Type: text/plain");
			return print($text);
		}
		// JSON
		return print(json_encode($array));		
	}
	/**
	 * Generate QR
	 * @since 5.4.3
	 */		
	protected function qr(){
		if(isset($_GET["size"]) && !empty($_GET["size"]) && preg_match('/[0-5]x[0-5]/', $_GET["size"])){
			$size=str_replace('"', "", str_replace("'", "", Main::clean($_GET["size"],3,TRUE)));
		}else{
			$size="149x149";
		}

		if(!$url = $this->db->get("url","BINARY alias=:a OR BINARY custom=:a",array("limit" => "1"),array(":a"=>$this->action))){
			return;
		}
		
		$api_url = "http://chart.apis.google.com/chart?chs=$size&chld=L|0&choe=UTF-8&cht=qr&chl=".urlencode((!empty($url->domain) ? $url->domain : $this->config["url"])."/{$this->action}?source=qr");	

		if(!$image = Main::curl($api_url)){
			header("Location: $api_url");	
			exit;	
		}
		header('Pragma: public');
		header('Cache-Control: max-age=86400');
		header('Expires: '. gmdate('D, d M Y H:i:s \G\M\T', time() + 86400));				
		header('Content-type: image/jpeg;');
		echo $image;
	}
	/**
	 * Grab favicon
	 * @author KBRmedia <https://gempixel.com>
	 * @version 5.5
	 * @return  [type] [description]
	 */
	protected function ico(){
		if(!$url = $this->db->get("url","BINARY alias=:a OR BINARY custom=:a",array("limit"=>1),array(":a"=>$this->action))){
			$api_url = "{$this->config["url"]}/static/globe.png";
			header("Location: $api_url");	
			exit;	
		}

		$protocol = explode("://", $url->url, 2)[0];		


		if(!in_array($protocol, ["http", "https"])){
			$api_url = "{$this->config["url"]}/static/globe.png";
			header("Location: $api_url");	
			exit;	
		}
		$api_url = "https://www.google.com/s2/favicons?domain={$url->url}";
		header("Location: $api_url");	
		exit;					
	}

	/**
	 * Generate Thumbnail
	 * @since 5.4.3
	 */		
	protected function i(){
		//$t_url=$this->config["url"]."/404";
		if(!$url = $this->db->get("url","BINARY alias=:a OR BINARY custom=:a",array("limit"=>1),array(":a"=>$this->action))){
			return;
		}

		$t_url = urlencode($url->url);

		$list = [
			"https://s.wordpress.com/mshots/v1/$t_url?w=800",
			"https://api.pagepeeker.com/v2/thumbs.php?size=l&url=$t_url",
			"https://api.miniature.io/?width=800&height=600&screen=1024&url=$t_url",
			"https://image.thum.io/get/width/600/crop/900/".urldecode($t_url)
		];

		$api_url = $list[array_rand($list, 1)];

		header("Location: $api_url");	
		exit;		

		if(!$image = Main::curl($api_url)){
			header("Location: $api_url");	
			exit;	
		}
		header('Pragma: public');
		header('Cache-Control: max-age=86400');
		header('Expires: '. gmdate('D, d M Y H:i:s \G\M\T', time() + 86400));		
		header('Content-type: $format');
		echo $image;		
	}
	/**
	 * Check if domain is blacklisted
	 * @since 5.7
	 */
	protected function blacklist_domain($url){
		if(empty($this->config["domain_blacklist"])) return FALSE;
		$url = parse_url(strtolower($url));
		$array = explode(",",$this->config["domain_blacklist"]);
		$domain = explode('.', $url["host"]);

		if(count($domain) > 2){
			$url["host"] = $domain[1].".".$domain[2];
		}		

		foreach ($array as $domain) {	
		  if ($domain == $url["host"]) {
		  	return TRUE;
		  }
		}
		return FALSE;		                
	}
	/**
	 * Check if URL contains blacklisted keywords
	 * @since 3.1.1
	 */
	protected function blacklist_keywords($url){
		if(!$this->config["adult"]) return FALSE;
		if(empty($this->config["keyword_blacklist"])) {
			$array = array('porn','sex','porno','redtube','4tube','spankwire',
					'xshare','ziporn','naked','pornstar','pussy','fuck','suck','porntube',
					'scriptmaster','warez','scriptmafia','nulled','jigshare','gaaks','newone',
					'intercambiosgratis','scriptease','xtragfx','vivaprogram','kickassgfx',
					'gfxdl','fulltemplatedownload','dlscript','nigger','dick','faggot','cunt','gay',
					'asshole','penis','vagina','motherfucker','fucker','shit','fucked','boobs');
		}else{
			$array=explode(",",$this->config["keyword_blacklist"]);
		}
		foreach ($array as $domain) {
		  $domain=trim($domain);
		  if (strpos($url,$domain)) {
		  	return TRUE;
		  }
		}
		return FALSE;		
	}
	/**
	 * Check if url can framed
	 * @since 4.0
	 */	
	protected function url_framed($url){
		$array=array("facebook.","youtube.","google.","gmail.","yahoo.","github.");
		$domain=Main::domain($url,TRUE,FALSE);
		foreach ($array as $u) {
		  if (preg_match("!$u!",$domain)) {
		  	return FALSE;
		  }
		}
		return TRUE;			
	}
	/**
	 * List of reserved custom alias
	 */
	protected function reserved_alias($alias){
		// Check system alias
		if(in_array($alias,array_merge($this->aliases,$this->actions))) return TRUE;
		return FALSE;
	}	
	/**
	 * Premium Aliases
	 * @since 4.0
	 **/
	protected function premium_alias($alias){
		// Check reserved alias
		if(!$this->pro() && in_array($alias, explode(",",$this->config["aliases"]))) return TRUE;		
	}
	/**
	 * Validate URL
	 * @since 5.5
	 */	
	protected function validate($url){
		if(empty($url)) return FALSE;		

		$protocol = explode("://", $url, 2)[0];		
		$schemes = explode(",", $this->config["schemes"]);

		$schemes = array_diff($schemes, ["http", "https", "ftp"]);

		$preg_schemes = implode("://|", $schemes);

		if(preg_match('(((http://|https://|ftp://|www.)*?)([\w\-\d]+\.)+[\w\-\d]+)', $url)) {			
			if(!preg_match('(http://|https://)', $url)){
				return "http://$url";
			}	
			return $url;		
		}

		if(in_array($protocol, $schemes)){
			return $url;
		}


		if(preg_match('~('.$preg_schemes.'://)(.*)~', $url)){
			return $url;
		}

		if(!filter_var($url, FILTER_VALIDATE_URL)){
			$parsed = parse_url($url);
			if(!isset($parsed["scheme"]) || !$parsed["scheme"]) return FALSE;
			if(!isset($parsed["host"]) || !$parsed["host"]) return FALSE;
		}					
		return $url;
	}
	/**
	 * Check if domain is safe using Google Safe (API Required)
	 * @since 5.6.5
	 */	
	public function safe($url){
		if(empty($this->config["safe_browsing"])) return TRUE;
		
		// $SAFE_URL = "https://safebrowsing.googleapis.com/v4/threatMatches:find?key={$this->config["safe_browsing"]}";

		$WEB_RISK_URL = "https://webrisk.googleapis.com/v1beta1/uris:search?key=";

		// Add Key
		$WEB_RISK_URL .= $this->config["safe_browsing"];

		$threatTypes = ["MALWARE", "SOCIAL_ENGINEERING", "UNWANTED_SOFTWARE"];

		foreach ($threatTypes as $threat) {
			$WEB_RISK_URL .= "&threatTypes={$threat}";
		}

		$WEB_RISK_URL .= "&uri=".urlencode($url);

		$get = Main::curl($WEB_RISK_URL, ["json" => TRUE]);
		
		$getDecoded = json_decode($get);

		if(isset($getDecoded->threat) && $getDecoded->threat->threatTypes[0]) return FALSE;

		return TRUE;				
	}	
 /**
	 * Check if URL is a Phish using phishtank
	 * @since 5.6.4
	 */	
	public function phish($url){
		
		if(empty($this->config["phish_api"]) || empty($this->config["phish_username"])) return FALSE;

		$api = "https://checkurl.phishtank.com/checkurl/";
		$data["format"]="json";

		$data["app_key"] = $this->config["phish_api"];

		$data["url"] = urlencode($url);
		$get = Main::curl($api, array("post" => true, "data"=> $data, "sendHeader" => ["User-Agent: phishtank/{$this->config["phish_username"]}"]));
		$r = json_decode($get);

		if(isset($r->results->valid) && $r->results->valid == "1") return TRUE;
		return FALSE;				
	}
	/**
	 * Unique Alias
	 * @since 5.4
	 **/
	protected function alias(){
		$unique = FALSE;
		$max_loop = 100;
		$i=0;
		if($this->config["alias_length"] < 3) $this->config["alias_length"] = 3; 
		while (!$unique) {
			// retry if max attempt reached
			if($i>=$max_loop) {
				$this->config["alias_length"]++;
				$i=0;
			}
			$alias = Main::strrand($this->config["alias_length"]);
			if(!$this->db->get("url",array("alias"=>$alias))) $unique=TRUE;
			$i++;
		}		
		return $alias;
	}
	/**
	 * [pixel_fb description]
	 * @author KBRmedia <http://gempixel.com>
	 * @version 5.0
	 * @param   [type] $id [description]
	 * @return  [type]     [description]
	 */
	protected function pixel_fbpixel($id){
		if(empty($id) || strlen($id) < 9) return;

		return "<script type='text/javascript'>
						  !function(f,b,e,v,n,t,s)
						  {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
						  n.callMethod.apply(n,arguments):n.queue.push(arguments)};
						  if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
						  n.queue=[];t=b.createElement(e);t.async=!0;
						  t.src=v;s=b.getElementsByTagName(e)[0];
						  s.parentNode.insertBefore(t,s)}(window, document,'script',
						  'https://connect.facebook.net/en_US/fbevents.js');
						  fbq('init', '{$id}');
						  fbq('track', 'PageView');		
						  fbq('track', 'Lead');
						</script>
						<noscript><img height='1' width='1' style='display:none'
						  src='https://www.facebook.com/tr?id={$id}&ev=PageView&noscript=1'
						/></noscript>";
	}
	/**
	 * [pixel_adwords description]
	 * @author KBRmedia <http://gempixel.com>
	 * @version 1.0
	 * @param   [type] $id [description]
	 * @return  [type]     [description]
	 */
	protected function pixel_adwordspixel($id){
		if(empty($id) || strlen($id) < 9) return;

		$Eid = explode("/", $id);

		return "<script async src='https://www.googletagmanager.com/gtag/js?id={$Eid[0]}'></script>
						<script type='text/javascript'>
						  window.dataLayer = window.dataLayer || [];
						  function gtag(){dataLayer.push(arguments);}
						  gtag('js', new Date());

						  gtag('config', '{$Eid[0]}');

						  gtag('event', 'conversion', {'send_to': '{$id}'});
						</script>";
	}	
	/**
	 * [pixel_linkedin description]
	 * @author KBRmedia <http://gempixel.com>
	 * @version 5.0
	 * @param   [type] $id [description]
	 * @return  [type]     [description]
	 */
	protected function pixel_linkedinpixel($id){
		if(empty($id) || strlen($id) < 6) return;

		return '<script type="text/javascript">
							_linkedin_data_partner_id = "'.$id.'";
							</script><script type="text/javascript">
							(function(){var s = document.getElementsByTagName("script")[0];
							var b = document.createElement("script");
							b.type = "text/javascript";b.async = true;
							b.src = "https://snap.licdn.com/li.lms-analytics/insight.min.js";
							s.parentNode.insertBefore(b, s);})();
						</script>
						<noscript>
						<img height="1" width="1" style="display:none;" alt="" src="https://dc.ads.linkedin.com/collect/?pid='.$id.'&fmt=gif" />
						</noscript>';
	}	
	/**
	 * [pixel_adroll description]
	 * @author KBRmedia <http://gempixel.com>
	 * @version 5.1
	 * @param   [type] $id [description]
	 * @return  [type]     [description]
	 */
	protected function pixel_adrollpixel($id){

		if(empty($id) || strlen($id) < 9) return;

		$Eid = explode("/", $id);

		return '<script type="text/javascript">
					    adroll_adv_id = "'.$Eid[0].'";
					    adroll_pix_id = "'.$Eid[1].'";
					    (function () {
					        var _onload = function(){
					            if (document.readyState && !/loaded|complete/.test(document.readyState)){setTimeout(_onload, 10);return}
					            if (!window.__adroll_loaded){__adroll_loaded=true;setTimeout(_onload, 50);return}
					            var scr = document.createElement("script");
					            var host = (("https:" == document.location.protocol) ? "https://s.adroll.com" : "http://a.adroll.com");
					            scr.setAttribute(\'async\', \'true\');
					            scr.type = "text/javascript";
					            scr.src = host + "/j/roundtrip.js";
					            ((document.getElementsByTagName(\'head\') || [null])[0] ||
					                document.getElementsByTagName(\'script\')[0].parentNode).appendChild(scr);
					        };
					        if (window.addEventListener) {window.addEventListener(\'load\', _onload, false);}
					        else {window.attachEvent(\'onload\', _onload)}
					    }());
					</script>';

	}
	/**
	 * [pixel_twitter description]
	 * @author KBRmedia <http://gempixel.com>
	 * @version 5.1
	 * @param   [type] $id [description]
	 * @return  [type]     [description]
	 */
	protected function pixel_twitterpixel($id){
		return "<script type='text/javascript'>
						  !function(e,t,n,s,u,a){e.twq||(s=e.twq=function(){s.exe?s.exe.apply(s,arguments):s.queue.push(arguments);
						  },s.version='1.1',s.queue=[],u=t.createElement(n),u.async=!0,u.src='//static.ads-twitter.com/uwt.js',
						  a=t.getElementsByTagName(n)[0],a.parentNode.insertBefore(u,a))}(window,document,'script');
						  
						  twq('init','$id');
						  twq('track','PageView');
					  </script>";
	}
	/**
	 * [pixel_quorapixel description]
	 * @author KBRmedia <https://gempixel.com>
	 * @version 5.6.3
	 * @param   [type] $id [description]
	 * @return  [type]     [description]
	 */
	protected function pixel_quorapixel($id){
		return "<script>
							!function(q,e,v,n,t,s){if(q.qp) return; n=q.qp=function(){n.qp?n.qp.apply(n,arguments):n.queue.push(arguments);}; n.queue=[];t=document.createElement(e);t.async=!0;t.src=v; s=document.getElementsByTagName(e)[0]; s.parentNode.insertBefore(t,s);}(window, 'script', 'https://a.quora.com/qevents.js');
							qp('init', '$id');
							qp('track', 'ViewContent');
							</script>
							<noscript><img height=\"1\" width=\"1\" style=\"display:none\" src=\"https://q.quora.com/_/ad/$id/pixel?tag=ViewContent&noscript=1\"/></noscript>";
	}	
	/**
	 * [injectPixels description]
	 * @author KBRmedia <http://gempixel.com>
	 * @version 5.1
	 * @param   [type] $pixels [description]
	 * @return  [type]         [description]
	 */
	protected function injectPixels($pixels, $userid){

		$pixels = explode(",", $pixels);
		$pixel = "";

		$user = $this->db->get("user", ["id" => $userid], ["limit" => 1]);

  	foreach ($pixels as $p) {
  		$pe = explode("-", $p);

  		if(empty($user->{$pe[0]})) continue;

  		$cData = json_decode($user->{$pe[0]}, TRUE);

  		if(!isset($cData[$pe[1]]["tag"])) continue;
  		
  		$fn = "pixel_{$pe[0]}";
  		$pixel .= $this->$fn($cData[$pe[1]]["tag"]);
			Main::add($this->$fn($cData[$pe[1]]["tag"]),"custom",FALSE);							
  	}
  	return $pixel;
	}
}