<?php 
/**
 * ====================================================================================
 *                           PREMIUM URL SHORTENER (c) KBRmedia
 * ----------------------------------------------------------------------------------
 * @copyright This software is exclusively sold at CodeCanyon.net. If you have downloaded this
 *  from another site or received it from someone else than me, then you are engaged
 *  in an illegal activity. You must delete this software immediately or buy a proper
 *  license from http://gempixel.com/buy/short.
 *
 *  Thank you for your cooperation and don't hesitate to contact me if anything :)
 * ====================================================================================
 *
 * @author KBRmedia (http://gempixel.com)
 * @link http://gempixel.com 
 * @package Premium URL Shortener
 * @subpackage Main Helper Class (Main.class.php)
 */
class Main{
    protected static $title="";
    protected static $description="";
    protected static $url="";
    protected static $image="";
    protected static $video="";
    protected static $body_class="";
    protected static $language = "en";
    protected static $lang="";
    protected static $plugin=array();
    private static $config=array();
    private static $session = [];
  /**
  * Generate meta title
  * @param none
  * @return title
  * @since v1.0
  */
    public static function title($separator="-"){               
      if(empty(self::$title)){
        return self::$config["title"];
      }else{
        return self::$title." $separator ".self::$config["title"];
      }
    }
  /**
  * Generate meta description
  * @param none
  * @return description
  * @since v1.0
  */
    public static function description(){      
      if(empty(self::$description)){
        return self::$config["description"];
      }else{
        return self::$description;
      }
    }
  /**
  * Generate URL
  * @param none
  * @return description
  * @since v1.0
  */
    public static function url(){      
      if(empty(self::$url)){
        return self::$config["url"];
      }else{
        return self::$url;
      }
    } 
  /**
  * Body Class inject
  * @param none
  * @return message
  * @since v1.0
  */     
    public static function body_class(){
      if(!empty(self::$body_class)) return " class='".self::$body_class."'";
    }    
  /**
  * Generate URL
  * @param none
  * @return description
  * @since v1.0
  */
    public static function image(){      
      if(empty(self::$image)){
        return;
      }else{
        return self::$image;
      }
    }   
  /**
  * Set meta info
  * @param none
  * @return Formatted array
  * @since v1.0
  */
    public static function set($meta,$value){
      if(!empty($value)){
        self::$$meta=$value;
      }
    }  
  /**
  * Generate Open-graph tags
  * @param none
  * @return string
  * @since v1.0
  */  
    public static function ogp(){            
      $meta="<meta property=\"og:type\" content=\"website\" />\n\t";      
      $meta.="<meta property=\"og:url\" content=\"".self::url()."\" />\n\t"; 
      $meta.="<meta property=\"og:title\" content=\"".self::title()."\" />\n\t";
      $meta.="<meta property=\"og:description\" content=\"".self::description()."\" />\n\t";  
      $meta.="<meta property=\"og:image\" content=\"".self::image()."\" />\n\t"; 
      if(!empty(self::$video)){
        $meta.=self::$video; 
      }
      
      if(self::$config["favicon"]){
        if(self::extension(self::$config["favicon"]) == ".ico"){
          $meta .= "<link rel=\"icon\" type=\"image/x-icon\" href=\"".self::$config["url"]."/content/".self::$config["favicon"]."\" sizes=\"32x32\" />\n\t";
        }else{
          $meta .= "<link rel=\"icon\" type=\"image/png\" href=\"".self::$config["url"]."/content/".self::$config["favicon"]."\" sizes=\"32x32\" />\n\t";
        }
      }
      $meta.="<link rel=\"canonical\" href=\"".self::url()."\" />\n\t";
      echo $meta; 
    } 
  /**
  * Generate Open-graph video tag
  * @param none
  * @return string
  * @since v2.0
  */  
  public static function video($url,$embed=FALSE){
    if(preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match)) {                     
       Main::set("image","http://i1.ytimg.com/vi/{$match[1]}/maxresdefault.jpg?feature=og");
       Main::set("video",'<meta property="og:video" content="http://www.youtube.com/v/'.$match[1].'?version=3&amp;autohide=1">
      <meta property="og:video:type" content="application/x-shockwave-flash">
      <meta property="og:video:width" content="1920">
      <meta property="og:video:height" content="1080">');
     }  
     if($embed) return '<iframe id="ytplayer" type="text/html" width="640" height="390" src="http://www.youtube.com/v/'.$match[1].'?version=3&amp;autohide=1" frameborder="0"/>';
  }    
  /**
   * [lang description]
   * @author KBRmedia <https://gempixel.com>
   * @version 1.0
   * @return  [type] [description]
   */
  public static function lang(){
    return self::$language;
  }  
  /**
   * [fstatic description]
   * @author KBRmedia <http://gempixel.com>
   * @version 1.0
   * @param   [type] $file [description]
   * @param   string $name [description]
   * @return  [type]       [description]
   */
  public static function fstatic($file, $name = ""){
    return self::$config["url"]."/static/{$file}";
  }       
  /**
  * Clean a string
  * @param string, cleaning level (1=lowest,2,3=highest)
  * @return cleaned string
  */

    public static function clean($string,$level='1',$chars=FALSE,$leave=""){        
        if(is_array($string)) return array_map("Main::clean",$string);

        $string=preg_replace('/<script[^>]*>([\s\S]*?)<\/script[^>]*>/i', '', $string);      
        switch ($level) {
          case '3':
            $search = array('@<script[^>]*?>.*?</script>@si',
                           '@<[\/\!]*?[^<>]*?>@si',
                           '@<style[^>]*?>.*?</style>@siU',
                           '@<![\s\S]*?--[ \t\n\r]*>@'
            ); 
            $string = preg_replace($search, '', $string);           
            $string=strip_tags($string,$leave);      
            if($chars) {
              if(phpversion() >= 5.4){
                $string=htmlspecialchars($string, ENT_QUOTES | ENT_HTML5,"UTF-8");  
              }else{
                $string=htmlspecialchars($string, ENT_QUOTES,"UTF-8");  
              }
            }
            break;
          case '2':
            $string=strip_tags($string,'<b><i><s><u><strong><span><p>');
            break;
          case '1':
            $string=strip_tags($string,'<b><i><s><u><strong><a><pre><code><p><div><span>');
            break;
        }   
        $string=str_replace('href=','rel="nofollow" href=', $string);   
        return $string; 
    }
  /**
  * Is Set and Equal to
  * @param key, value
  * @return boolean
  */ 
   public static function is_set($key,$value=NULL,$method="GET"){
      if(!in_array($method, array("GET","POST"))) return FALSE;
      if($method=="GET") {
        $method=$_GET;
      }elseif($method=="POST"){
        $method=$_POST;
      }
      if(!isset($method[$key])) return FALSE;
      if(!is_null($value) && $method[$key]!==$value) return FALSE;
      return TRUE;
   }
  /**
  * Validate and sanitize email
  * @param string
  * @return email
  */  
    public static function email($email){
        $email=trim($email);
        if (preg_match('/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]{2,})+$/i', $email) && strlen($email)<=50 && filter_var($email, FILTER_SANITIZE_EMAIL)){
            return filter_var($email, FILTER_SANITIZE_EMAIL);
        }
        return FALSE;
    }

  /**
  * Validate and sanitize username
  * @param string
  * @return username
  */  

    public static function username($user){
      if(preg_match('/^\w{4,}$/', $user) && strlen($user)<=20 && filter_var($user,FILTER_SANITIZE_STRING)) {
        return filter_var(trim($user),FILTER_SANITIZE_STRING);
      }
      return false;    
    }
  /**
  * Validate Date
  * @param string
  */  
    public static function validatedate($date, $format = 'Y-m-d H:i:s'){
      if(!class_exists("DateTime")){
        if(!preg_match("!(.*)-(.*)-(.*)!",$date)) return false;
        return true;
      }
      $d = DateTime::createFromFormat($format, $date);
      return $d && $d->format($format) == $date;
    }
  /**
   * Get IP
   * @since 1.0 
   **/
  public static function ip(){
     $ipaddress = '';
      if(isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
          $ipaddress =  $_SERVER['HTTP_CF_CONNECTING_IP'];
      } else if (isset($_SERVER['HTTP_X_REAL_IP'])) {
          $ipaddress = $_SERVER['HTTP_X_REAL_IP'];
      }
      else if (isset($_SERVER['HTTP_CLIENT_IP']))
          $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
      else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
          $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
      else if(isset($_SERVER['HTTP_X_FORWARDED']))
          $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
      else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
          $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
      else if(isset($_SERVER['HTTP_FORWARDED']))
          $ipaddress = $_SERVER['HTTP_FORWARDED'];
      else if(isset($_SERVER['REMOTE_ADDR']))
          $ipaddress = $_SERVER['REMOTE_ADDR'];
      else
          $ipaddress = 'UNKNOWN';

        $ip = explode(",", $ipaddress);
        if(is_array($ip)) return $ip[0];
      return $ipaddress;
  }
  /**
   * Validate URLs
   * @since 1.0
   **/
  public static function is_url($url){
    if (preg_match("/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i",$url) && filter_var($url, FILTER_VALIDATE_URL)){
      return true;
    }    
    return false;     
  }

  /**
  * Encode string
  * @param string, encode= MD5, SHA1 or SHA256 
  * @return hash
  */   
    public static function encode($string,$encoding="phppass"){      
      if($encoding=="phppass"){
        if(!class_exists("PasswordHash")) require_once(ROOT."/includes/library/phpPass.class.php");
        $e = new PasswordHash(8, FALSE);
        return $e->HashPassword($string.self::$config["security"]);
      }else{
        return hash($encoding,$string.self::$config["security"]);
      }
    }
  /**
  * Check Password
  * @param string, encode= MD5, SHA1 or SHA256 
  * @return hash
  */   
    public static function validate_pass($string,$hash,$encoding="phppass"){      

      if($encoding=="phppass"){
        if(!class_exists("PasswordHash")) require_once(ROOT."/includes/library/phpPass.class.php");
        $e = new PasswordHash(8, FALSE);
        return $e->CheckPassword($string.self::$config["security"], $hash);
      }else{
        return hash($encoding,$string.self::$config["security"]);
      }
    }
/**
 * Read user cookie and extract user info
 * @param 
 * @return array of info
 * @since v1.0
 */
  public static function user(){
    if(isset($_COOKIE["login"])){
      $data=json_decode(base64_decode($_COOKIE["login"]),TRUE);
    }elseif(isset($_SESSION["login"])){
      $data=json_decode(base64_decode($_SESSION["login"]),TRUE);     
    }
    if(isset($data["loggedin"]) && !empty($data["key"])){  
      return array(self::clean(substr($data["key"],60)),self::clean(substr($data["key"],0,60)));
    }     
    return FALSE;  
  }    
  /**
  * Generate api or random string
  * @param length, start
  * @return 
  */    
    public static function strrand($length=12,$api=""){    
        $use = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890"; 
        srand((double)microtime()*1000000); 
        for($i=0; $i<$length; $i++) { 
          $api.= $use[rand()%strlen($use)]; 
        } 
      return $api; 
    }

  /**
  * Get extension
  * @param file name
  * @return extension
  */   
    public static function extension($file){
        $ext = strrchr($file, "."); 
        $next = explode("?", $ext);
        return $next[0];
    }

  /**
  * Generate slug
  * @param string
  * @return slug
  */  

    public static function slug($url) {
        $url=preg_replace('/[^_0-9a-zA-Z -]/', '', $url);
        $url=preg_replace('/\s\s+/', ' ', $url);
        $url=str_replace(' ','-',$url);
      return $url;
    }  

  /**
  * Clean cookie
  * @param cookie
  * @return cleaned cookie
  */  

    public static function get_cookie($cookie){
      return Main::clean($cookie,1);
    }

  /**
  * Convert a timestap into timeago format
  * @param time
  * @return timeago
  */  

    public static function timeago($time, $tense=TRUE){
       $time=strtotime($time);
       $periods = array(e("second"), e("minute"), e("hour"), e("day"), e("week"), e("month"), e("year"), e("decade"));
       $lengths = array("60","60","24","7","4.35","12","10");
       $now = time();
         $difference = $now - $time;
         $tense= e("ago");
         for($j = 0; $difference >= $lengths[$j] && $j < count($lengths)-1; $j++) {
           $difference /= $lengths[$j];
         }
         $difference = round($difference);
         if($difference != 1) {
           $periods[$j].= "s";
         }
       if($tense){
        return "$difference $periods[$j] $tense ";
      }else{
        return $difference;
      }
    } 

  /**
  * Redirect function
  * @param url/path (not including base), message and header code
  * @return nothing
  */   

    public static function redirect($url,$message=array(),$header="",$fullurl=FALSE){      

      if(!empty($message)){      
        $_SESSION["msg"]=self::clean("{$message[0]}::{$message[1]}",2);
      }
      switch ($header) {
        case '301':
          header('HTTP/1.1 301 Moved Permanently');
          break;
        case '404':
          header('HTTP/1.1 404 Not Found');
          break;
        case '503':
          header('HTTP/1.1 503 Service Temporarily Unavailable');
          header('Status: 503 Service Temporarily Unavailable');
          header('Retry-After: 60');
          break;
      }
      if($fullurl){
        header("Location: $url");
        exit;
      }    
      header("Location: ".self::$config["url"]."/$url");
      exit;
    }

  /**
  * Notification Function
  * @param none
  * @return message
  */     

    public static function message(){
      if(isset($_SESSION["msg"]) && !empty($_SESSION["msg"])) {
        $message=explode("::",self::clean($_SESSION["msg"],2));
          $message="<div class='alert alert-{$message[0]} no-round'>{$message[1]}</div>";
          unset($_SESSION["msg"]);
      }else {
        $message="";
      }
      return $message;
    }

  /**
  * Show error message
  * @param message
  * @return formatted message
  */  
    public static function error($message){
      return "<div class='alert alert-danger'>$message</div>";
    }

  /**
  * Truncate a string
  * @param string, delimiter, append string
  * @return truncated message
  */  
    public static function truncate($string,$del,$limit="...") {
      $len = strlen($string);
        if ($len > $del) {
           $new = substr($string,0,$del).$limit;
            return $new;
        }
        return $string;
    } 

  /**
  * Format Number
  * @param number, decimal
  * @return formatted number
  */  
    public static function formatnumber($number,$decimal="0") {
      if($number>1000000000000) $number= round($number /1000000000000, $decimal)."T";
      if($number>1000000000) $number= round($number /1000000000, $decimal)."B";
      if($number>1000000) $number= round($number /1000000, $decimal)."M";
      if($number>10000) $number= round($number /10000, $decimal)."K";

      return $number;
    }  

  /**
  * Get Facebook Likes
  * @param Facebook page
  * @return number of likes
  */   
  	public static function facebook_likes($url){
  		if(preg_match('((http://|https://|www.)facebook.+[\w-\d]+/(.*))', $url,$id)) {
          $id = $id[2];
          $count = Main::cache_get(__FUNCTION__);
          if($count == null){
            $content = json_decode(@file_get_contents("https://graph.facebook.com/$id/"),TRUE);
            $count = $content["likes"];
            Main::cache_set(__FUNCTION__,$count,60);
          }
        return $count;        
  		}
  	}

  /**
  * Ajax Button
  * @param type, max number of page, current page, url, text, class
  * @return formatted button
  */ 
    public static function ajax_button($type, $max, $current,$url,$text='',$class="ajax_load"){
      if($current >= $max) return FALSE;
      return "<a href='$url' data-page='$current' data-type='$type' class='button fullwidth $class'>Load More $text</a>";
    }

  /**
  * Generates url based on settings (seo or not)
  * @param pretty urls (seo)
  * @return url
  */ 
    public static function href($default,$base=TRUE){      
      return (!$base)?"$default":"".self::$config["url"]."/$default";
    }

  /**
  * Generates admin url based on settings (seo or not)
  * @see Main::href()
  * @param d pretty urls (seo)
  * @return url
  */ 
    public static function ahref($seo="",$base=TRUE){
      return Main::href("admin/$seo",$base);
    }
  /**  
  * Generates pagination with class "pagination"
  * @param total number of pages, current pages, format of url
  * @return complete pagination elements
  */
  public static function pagination($total, $current, $format, $limit='1'){
         $page_count = ceil($total/$limit);
         $current_range = array(($current-5 < 1 ? 1 : $current-3), ($current+5 > $page_count ? $page_count : $current+3));

         $first_page = $current > 5 ? '<li><a href="'.sprintf($format, '1').'">'.Main::e("First").'</a></li>'.($current < 5 ? ' ' : '') : null;
         $last_page = $current < $page_count-2 ? ($current > $page_count-4 ? ' ' : '  ').'<li><a href="'.sprintf($format, $page_count).'">'.Main::e("Last").'</a></li>' : null;

         $previous_page = $current > 1 ? '<li><a href="'.sprintf($format, ($current-1)).'">'.Main::e("Previous").'</a></li> ' : null;
         $next_page = $current < $page_count ? ' <li><a href="'.sprintf($format, ($current+1)).'">'.Main::e("Next").'</a></li> ' : null;

         for ($x=$current_range[0];$x <= $current_range[1]; ++$x)    
        $pages[] = ($x == $current ? '<li class="active"><a href="#">'.$x.'</a></li>' : '<li><a href="'.sprintf($format, $x).'"">'.$x.'</a></li>');
         if ($page_count > 1)
      return '<ul class="pagination">'.$first_page.$previous_page.implode(' ', $pages).$next_page.$last_page.'</ul>';
  }
  /**
   * [nextpagination description]
   * @author KBRmedia <http://gempixel.com>
   * @version 1.0
   * @param   [type] $total   [description]
   * @param   [type] $current [description]
   * @param   [type] $format  [description]
   * @param   string $limit   [description]
   * @return  [type]          [description]
   */
  public static function nextpagination($current, $format, $stop = FALSE){

         $previous_page = $current > 1 ? '<li><a href="'.sprintf($format, ($current-1)).'">'.Main::e("Previous").'</a></li> ' : null;
         $next_page = !$stop ? ' <li><a href="'.sprintf($format, ($current+1)).'">'.Main::e("Next").'</a></li> ' : null;

      return '<ul class="pagination">'.$previous_page.$next_page.'</ul>';
  }
  /**  
  * Generates the path to the thumbnail based on mod-rewrite settings
  * @param file name, width, height
  * @return url
  */    
    public static function thumb($file,$width="",$height="",$base=TRUE){
      return Main::href("index.php?action=thumb&p=".Main::clean($file,3)."/$width/$height","thumb/".Main::clean($file,3)."/$width/$height",$base);
    }

/**  
* Validates the captcha based on settings
* @param data
* @return ok
* @since 5.7
*/  
  public static function check_captcha($array){
      if(self::$config["captcha"]=="1"){
        // Recaptcha
        if(empty($array["g-recaptcha-response"])) return e("Please answer the captcha.");

        require_once(ROOT."/includes/library/Recaptcha.php");
        
        $reCaptcha = new ReCaptcha(self::$config["captcha_private"]);

        if($reCaptcha->verify() == false){
          return e("The CAPTCHA wasn't entered correctly. Please try it again.")."<script>grecaptcha.reset()</script>";
        }

      }elseif(self::$config["captcha"]=="2"){
        // SolveMedia
        require_once(ROOT."/includes/library/Solvemedia.php");
        if(empty($array["adcopy_response"])) {
          return e('Please enter the CAPTCHA.');  
        }else{
          $resp = solvemedia_check_answer(SV_PRIVKEY,$_SERVER["REMOTE_ADDR"],$array["adcopy_challenge"],$array["adcopy_response"],SV_HASHKEY);
          if (!$resp->is_valid) {
           return e("The CAPTCHA wasn't entered correctly. Please try it again.")."<script>ACPuzzle.reload()</script>";
          }       
        }       
      }
      return 'ok';    
    }

/**  
* Generates CAPTCHA html based on settings
* @param none
* @return captcha
* @since 5.7.2
*/     
    public static function captcha(){
        if(self::$config["captcha"]=="1"){          
          require_once(ROOT."/includes/library/Recaptcha.php");          
          return Recaptcha::render(self::$config["captcha_public"]);
        }elseif(self::$config["captcha"]=="2"){
          require_once(ROOT."/includes/library/Solvemedia.php");
          return solvemedia_get_html(SV_CHALLENGE);
        }
    }

  /**
  * Generated CSRF Token
  * @param none
  * @return token
  * @since v1.0
  */   
    public static function csrf_token($form=FALSE,$echo=TRUE){
        if($form && $echo && isset($_SESSION["CSRF"])) return "<input type='hidden' name='token' value='{$_SESSION["CSRF"]}' />";      
        if($echo && isset($_SESSION["CSRF"])) return $_SESSION["CSRF"];

        $token = self::encode("csrf_token".rand(0,1000000).time().uniqid(),"SHA1");
        $_SESSION["CSRF"] = $token;

        if($form) return "<input type='hidden' name='token' value='$token' />";
      return $token;
    }

  /**
  * Validate CSRF Token
  * @param token
  * @return boolean
  * @since v1.0
  */   
    public static function validate_csrf_token($token,$redirect=""){
      if(isset($_SESSION["CSRF"]) && ($_SESSION["CSRF"] == trim($token))) {
        unset($_SESSION["CSRF"]);
        return TRUE;
      }
      if(!empty($redirect)) self::redirect($redirect,array("error",e("The CSRF token is not valid. Please try again.")));
      return FALSE;
    }  
/**
  * Create Nonce
  * @param action, duration in minutes
  * @return token
  * @since v4.0
  */   
    public static function nonce_create($action="",$duration="60"){
      $i = ceil( time() / ( $duration*60 / 2 ) );
      return md5( $i . $action . $action);
    }
/**
  * Return Nonce
  * @param action, GET key
  * @return token
  * @since v4.0
  */   
    public static function nonce($action="",$key="nonce"){
      return "?".$key."=".substr(self::nonce_create($action), -12, 10);
    }
  /**
  * Validate Nonce
  * @param action, GET key
  * @return boolean
  * @since v4.0
  */   
    public static function validate_nonce($action="",$key="nonce"){
      if(isset($_GET[$key]) && substr(self::nonce_create($action), -12, 10) == $_GET[$key]){
        return true;
      }
      return false;
    }  

  /**
   * Set ucookie
   * @param Name, value
   * @since v1.0
   */  
    public static function cookie($name,$value="",$time=1){
      if(empty($value)){
        if(isset($_COOKIE[$name])){
          return Main::clean($_COOKIE[$name],3,FALSE);
        }else{
          return FALSE;
        }
      }
      setcookie($name,$value, time()+($time*60), "/","",FALSE,TRUE);
    }
  /**
   * Enqueue scripts to header and footer
   * @since v1.0
   */
    public static function enqueue($where="header"){
      if($where=="footer"){  
        global $enqueue_footer;
        echo $enqueue_footer;
      }else{
        global $enqueue_header;
        echo $enqueue_header;
      } 
    }
  /**
   * Add scripts to header and footer
   * @since 5.6
   */  
    public static function add($url, $type="script", $location = "footer"){
      if($type == "style"){
        $tag = '<link rel="stylesheet" type="text/css" href="'.$url.'">';
      }elseif($type=="custom"){
        $tag = $url;
      }else{
        $tag = '<script type="text/javascript" src="'.$url.'"></script>';
      }
      if($location == TRUE || $location == "footer"){
        global $enqueue_footer;
        $enqueue_footer.=$tag."\n\t";
      }else{
        global $enqueue_header;
        $enqueue_header.=$tag."\n\t";
      }
    } 
  /**
   * Enqueue scripts to header and footer
   * @since v1.0
   */
    public static function admin_enqueue($where="header"){
      if($where=="footer"){  
        global $admin_enqueue_footer;
        echo $admin_enqueue_footer;
      }else{
        global $admin_enqueue_header;
        echo $admin_enqueue_header;
      } 
    }
  /**
   * Add scripts to header and footer
   * @since v1.0
   */  
    public static function admin_add($url,$type="script",$footer=TRUE){
      if($type=="style"){
        $tag='<link rel="stylesheet" type="text/css" href="'.$url.'">';
      }elseif($type=="custom"){
        $tag=$url;
      }else{
        $tag='<script type="text/javascript" src="'.$url.'"></script>';
      }
      if($footer){
        global $admin_enqueue_footer;
        $admin_enqueue_footer.=$tag."\n\t";
      }else{
        global $admin_enqueue_header;
        $admin_enqueue_header.=$tag."\n\t";
      }
    }
   /**
    * List of CDNs
    * Powered by CloudFlare.com
    * @since 5.6
    **/  
    public static function cdn($cdn,$version="",$admin=FALSE){
      $cdns=array(
        "jquery"=> array(
            "src" => "//ajax.googleapis.com/ajax/libs/jquery/[version]/jquery.min.js",
            "latest" =>"2.0.3"
          ),
        "jquery-ui"=> array(
            "src" => "//cdnjs.cloudflare.com/ajax/libs/jqueryui/[version]/jquery-ui.min.js",
            "latest" =>"1.10.3"
          ),
        "ace"=>array(
            "src"=>"//cdnjs.cloudflare.com/ajax/libs/ace/[version]/ace.js",
            "latest" => "1.1.01"
          ),
        "icheck"=>array(
            "src"=>"//cdnjs.cloudflare.com/ajax/libs/iCheck/[version]/icheck.min.js",
            "latest" => "1.0.1"            
          ),
        "ckeditor"=>array(
            "src"=>"//cdnjs.cloudflare.com/ajax/libs/ckeditor/[version]/ckeditor.js",
            "latest" => "4.3.2"
          ),
        "selectize"=>array(
            "src"=>"//cdnjs.cloudflare.com/ajax/libs/selectize.js/[version]/js/standalone/selectize.min.js",
            "latest"=>"0.8.5"
          ),
        "zlip"=>array(
            "src"=>"//cdnjs.cloudflare.com/ajax/libs/zclip/[version]/jquery.zclip.min.js",
            "latest"=>"1.1.2"
          ),
        "flot"=>array(
            "src"=>"//cdnjs.cloudflare.com/ajax/libs/flot/[version]/jquery.flot.min.js",
            "latest"=>"0.8.2",
            "js" => array(
                "//cdnjs.cloudflare.com/ajax/libs/flot/0.8.2/jquery.flot.time.min.js",
                "//cdnjs.cloudflare.com/ajax/libs/flot/0.8.2/jquery.flot.pie.min.js",
                "//cdnjs.cloudflare.com/ajax/libs/flot/0.8.2/excanvas.min.js"
              )
          ),
        "less"=>array(
            "src"=>"//cdnjs.cloudflare.com/ajax/libs/less.js/[version]/less.min.js",
            "latest"=>"1.6.2"
          ),
        "ckeditor"=>array(
            "src"=>"//cdnjs.cloudflare.com/ajax/libs/ckeditor/[version]/ckeditor.js",
            "latest"=>"4.3.2"
          ),
        "pace"=>array(
            "src"=>"//cdnjs.cloudflare.com/ajax/libs/pace/[version]/pace.js",
            "latest"=>"0.4.17"
          ),
        "chosen"=>array(
            "src"=>"//cdnjs.cloudflare.com/ajax/libs/chosen/[version]/chosen.jquery.min.js",
            "latest"=>"1.1.0",
            //"css"=> "//cdnjs.cloudflare.com/ajax/libs/chosen/1.1.0/chosen.css"
          ),
        "spectrum" => array(
            "src"=>"//cdnjs.cloudflare.com/ajax/libs/spectrum/[version]/spectrum.min.js",
            "latest"=>"1.8.0",
            "css"=> "//cdnjs.cloudflare.com/ajax/libs/spectrum/1.8.0/spectrum.min.css"
          ),
        "clipboard" => array(
            "src" => "https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/[version]/clipboard.min.js",
            "latest" => "1.5.15"
          ),
        "chartjs" => array(
            "src" => "//cdnjs.cloudflare.com/ajax/libs/Chart.js/[version]/Chart.min.js",
            "latest" => "2.6.0",
            "js" => array(
              //  "//cdnjs.cloudflare.com/ajax/libs/Chart.js/2.6.0/Chart.bundle.min.js"
              )          
          ),      
        "datepicker" => array(
            "src" => "//cdnjs.cloudflare.com/ajax/libs/datepicker/[version]/datepicker.min.js",
            "latest" => "0.6.4",
            "css" => "//cdnjs.cloudflare.com/ajax/libs/datepicker/0.6.4/datepicker.min.css"
              ),             
        "consent" => array(
            "src" => "//cdnjs.cloudflare.com/ajax/libs/cookieconsent2/[version]/cookieconsent.min.js",
            "latest" => "3.0.3",
            "css" => "//cdnjs.cloudflare.com/ajax/libs/cookieconsent2/3.0.3/cookieconsent.min.css"
              ),
        "blockadblock" => array(
            "src" => "//cdnjs.cloudflare.com/ajax/libs/blockadblock/[version]/blockadblock.min.js",
            "latest" => "3.2.1"
          ),
        "iconpicker" => [
            "src" => "//cdnjs.cloudflare.com/ajax/libs/fontawesome-iconpicker/[version]/js/fontawesome-iconpicker.min.js",
            "latest" => "3.0.0",
            "css" => "//cdnjs.cloudflare.com/ajax/libs/fontawesome-iconpicker/3.0.0/css/fontawesome-iconpicker.min.css"
          ],
        "tagsinput" => [
          "src" => "https://cdnjs.cloudflare.com/ajax/libs/jquery-tagsinput/[version]/jquery.tagsinput.min.js",
          "latest" => "1.3.6",
          "css" => "https://cdnjs.cloudflare.com/ajax/libs/jquery-tagsinput/1.3.6/jquery.tagsinput.min.css"
        ],
        "summernote" => [
            "src" => "https://cdnjs.cloudflare.com/ajax/libs/summernote/[version]/summernote-lite.js",
            "latest" => "0.8.11",
            "css" => "https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.11/summernote-lite.css"
          ],
        "autocomplete" => [
            "src" => "https://cdnjs.cloudflare.com/ajax/libs/jquery.devbridge-autocomplete/1.4.10/jquery.autocomplete.min.js",
            "latest" => "1.1.5"
            // "css" => "https://cdnjs.cloudflare.com/ajax/libs/awesomplete/1.1.5/awesomplete.base.min.css"
          ]
        );   
      if(array_key_exists($cdn, $cdns)){
        if(!empty($version)  && $version <= $cdns[$cdn]["latest"]){
          $js = str_replace("[version]", $version, $cdns[$cdn]["src"])."?v=$version";
        }else{
          $js = str_replace("[version]", $cdns[$cdn]["latest"], $cdns[$cdn]["src"])."?v={$cdns[$cdn]["latest"]}";
        }
       if($admin){          
          if(isset($cdns[$cdn]["css"])) Main::admin_add($cdns[$cdn]["css"]."?v={$cdns[$cdn]["latest"]}","style",FALSE);
          Main::admin_add($js,"script",FALSE);          
        }else{
          Main::add($js,"script",FALSE);          
          if(isset($cdns[$cdn]["css"])) Main::add($cdns[$cdn]["css"]."?v={$cdns[$cdn]["latest"]}","style",FALSE);
          if(isset($cdns[$cdn]["js"])){
            foreach ($cdns[$cdn]["js"] as $key) {
              Main::add($key."?v={$cdns[$cdn]["latest"]}","script",FALSE);
            }
          } 
          return TRUE;         
        }
      }
      return FALSE;
    }
  /**
   * Translate strings
   * @since v1.0
   */   
    public static function e($text){
      if(!is_array(Main::$lang)) return $text;
      if(isset(Main::$lang[$text]) && !empty(Main::$lang[$text])) {
        return ucfirst(Main::$lang[$text]);
      }
      return $text;    
    }
  /**
   * Check if user agent is bot
   * @since 4.1.1
   */  
  public static function bot($ua=""){
    if(empty($ua) && isset($_SERVER['HTTP_USER_AGENT'])) $ua = $_SERVER['HTTP_USER_AGENT'];
    $list = array("facebookexternalhit","Teoma", "alexa", "froogle", "Gigabot", "inktomi",
    "looksmart", "URL_Spider_SQL", "Firefly", "NationalDirectory",
    "Ask Jeeves", "TECNOSEEK", "InfoSeek", "WebFindBot", "girafabot",
    "crawler", "www.galaxy.com", "Googlebot", "Scooter", "Slurp",
    "msnbot", "appie", "FAST", "WebBug", "Spade", "ZyBorg", "rabaz",
    "Baiduspider", "Feedfetcher-Google", "TechnoratiSnoop", "Rankivabot",
    "Mediapartners-Google", "Sogou web spider", "WebAlta Crawler","TweetmemeBot",
    "Butterfly","Twitturls","Me.dium","Twiceler");
    foreach($list as $bot){
      if(strpos($ua,$bot)!==false) return true;
    }
    return false; 
  }
  /**
   * [isSocial description]
   * @author KBRmedia <http://gempixel.com>
   * @version 5.6.5
   * @return  boolean [description]
   */
  public static function isSocial(){
    if(!isset($_SERVER['HTTP_USER_AGENT'])) return FALSE;

    $ua = $_SERVER['HTTP_USER_AGENT'];

    $list = array("Twitturls","LinkedInBot","Twitterbot");
    foreach($list as $bot){
      if(strpos($ua,$bot)!==false) return true;
    }

      return FALSE;
  }
  /**
   * Get Meta data of URL
   * @param url
   * @return formatted array
   * @since  5.5.1
   */
  public static function get_meta_data($url, $checkHeader = FALSE){
    
    $array = array('title' => '','description' => '');

    // Check headers
    if($checkHeader && $headers = get_headers($url, 1)){
      foreach ($headers as $key => $value) {
        if(is_array($value)) continue;
        if(strtolower($key) == "content-type" && !preg_match("~text/html~", strtolower($value))){
          return $array;         
        }
      }
    }

    $content = Main::curl($url, ["redirect" => TRUE]);

    if($content){
      $pattern = "|<[\s]*title[\s]*>([^<]+)<[\s]*/[\s]*title[\s]*>|Ui";
        if(preg_match($pattern, $content, $match)){
          $array['title']=$match[1];
        }
        $data = get_meta_tags($url);
        if(isset($data['description'])){
          $array['description'] = $data['description'];
        }
      unset($data);
      unset($content);
      unset($match);      
      return $array;
    }
    return FALSE;
  }    
  /**
   * Custom cURL Function
   * @since 5.6.4
   **/ 
  public static function curl($url, $option = []){  

    if(in_array('curl', get_loaded_extensions())){ 
      $curl = curl_init();
      curl_setopt($curl, CURLOPT_URL, $url);

      if(isset($option["post"]) && isset($option["data"]) && is_array($option["data"])){
        $fields = "";
        //url-ify the data for the POST
        foreach($option["data"] as $key=>$value) { $fields .= $key.'='.$value.'&'; }

        rtrim($fields, '&');       
        curl_setopt($curl, CURLOPT_POST, count($option["data"]));
        curl_setopt($curl, CURLOPT_POSTFIELDS, $fields);
      }

      if(isset($option["post"]) && isset($option["body"])){
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, stripslashes($option["body"]));                       
      }
      if(isset($option["sendHeader"]) && is_array($option["sendHeader"])){
        curl_setopt($curl, CURLOPT_HTTPHEADER, $option["sendHeader"]);          
      }  
      if(isset($option["json"])){
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(                                                                          
          'Content-Type: application/json'                                                                   
        ));
      }
      if(isset($option["redirect"])){
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
      }
      if(isset($option["header"])){
        curl_setopt($curl, CURLOPT_HEADER, 1);
      }

      curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
      $resp = curl_exec($curl);
      curl_close($curl);
      if(isset($option["header"])){
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_size);
        $body = substr($response, $header_size);
        return ["header"=> $header, "body" => $body];
      }      
      return $resp;
    }
    return FALSE;
  } 
  /**
   * [gemCurl description]
   * @author KBRmedia <http://gempixel.com>
   * @version 1.0
   * @param   [type] $url    [description]
   * @param   array  $option [description]
   * @return  [type]         [description]
   */
  public static function gemCurl($url, $option = []){  

    if(in_array('curl', get_loaded_extensions())){ 
      $curl = curl_init();
      curl_setopt($curl, CURLOPT_URL, $url);

      if(isset($option["data"]) && is_array($option["data"])){
        $fields = "";
        foreach($option["data"] as $key => $value) { $fields .= $key.'='.$value.'&'; }

        rtrim($fields, '&');       
        curl_setopt($curl, CURLOPT_POST, count($option["data"]));
        curl_setopt($curl, CURLOPT_POSTFIELDS, $fields);
      }

      curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        "X-Authorization: TOKEN ".md5(self::$config["url"])
      ));
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
      $resp = curl_exec($curl);
      curl_close($curl);
      return $resp;
    }
    return FALSE;
  }  
  /**
   * Send Email
   * @param array
   * @return boolean
   */  
  public static function send(array $array){    

    require_once(ROOT."/includes/library/phpmailer/PHPMailerAutoload.php");

    $mail= new PHPMailer();  

    if(!empty(self::$config["smtp"]["host"]) && !empty(self::$config["smtp"]["port"]) && !empty(self::$config["smtp"]["user"]) && !empty(self::$config["smtp"]["pass"])){
      $mail->IsSMTP();
      $mail->SMTPAuth = true;
      if(self::$config["smtp"]["port"] == 465){
        $mail->SMTPSecure = "ssl";
      }else{
        $mail->SMTPSecure = "tls";
      }
      $mail->Host = self::$config["smtp"]["host"];
      $mail->Port = self::$config["smtp"]["port"]; 
      $mail->Username= self::$config["smtp"]["user"]; 
      $mail->Password  = self::$config["smtp"]["pass"];     
    }

    $mail->IsHTML(true); 
    $mail->SetFrom(self::$config["email"], self::$config["title"]);
    $mail->AddReplyTo(self::$config["email"], self::$config["title"]);
    $mail->AddAddress($array["to"]);
    $mail->Subject= $array["subject"];    

    $content=file_get_contents(TEMPLATE."/email.php");
    if(!empty(self::$config["logo"])){
      $content = str_replace("[logo]","<img src='".self::$config["url"]."/content/".self::$config["logo"]."' style='max-width:150px'>",$content);
    }else{
      $content = str_replace("[logo]",self::$config["title"],$content);
    }

    $content=str_replace("[subject]",$array["subject"],$content);
    $content=str_replace("[message]",$array["message"],$content);
    $content=str_replace("[title]", self::$config["title"],$content);
    $content=str_replace("[url]","<a href='".self::$config["url"]."'>".self::$config["url"]."</a>",$content);
    $content=str_replace("[contact]","<a href='".self::$config["url"]."/contact'>Contact Us</a>",$content);
    if(!empty(self::$config["facebook"])){
      $content=str_replace("[facebook]"," | <a href='".self::$config["facebook"]."'>Like us on Facebook</a>",$content);  
    }else{
      $content=str_replace("[facebook]","",$content);  
    }
    if(!empty(self::$config["twitter"])){
      $content=str_replace("[twitter]"," | <a href='".self::$config["twitter"]."'>Follow us on Twitter</a>",$content);
    }else{
      $content=str_replace("[twitter]","",$content);  
    }
    $mail->msgHTML($content);

    if(!$mail->send()) {
        error_log("SMTP Error: {$mail->ErrorInfo}");
        $headers  = 'From:  '.self::$config["title"].' <'.self::$config["email"].'>' . "\r\n";
        $headers .= 'MIME-Version: 1.0' . "\r\n";
        $headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
        mail($array["to"], $array["subject"], $content, $headers);
        return TRUE;
    } else {
      return TRUE;
    }          
  } 
  /**
   * [sendCustomer description]
   * @author KBRmedia <https://gempixel.com>
   * @version 5.7
   * @param   array  $array [description]
   * @return  [type]        [description]
   */
  public static function sendCustomer(array $array){    

    require_once(ROOT."/includes/library/phpmailer/PHPMailerAutoload.php");

    $mail= new PHPMailer();  

    if(!empty(self::$config["smtp"]["host"]) && !empty(self::$config["smtp"]["port"]) && !empty(self::$config["smtp"]["user"]) && !empty(self::$config["smtp"]["pass"])){
      $mail->IsSMTP();
      $mail->SMTPAuth = true;
      if(self::$config["smtp"]["port"] == 465){
        $mail->SMTPSecure = "ssl";
      }else{
        $mail->SMTPSecure = "tls";
      }
      $mail->Host = self::$config["smtp"]["host"];
      $mail->Port = self::$config["smtp"]["port"]; 
      $mail->Username= self::$config["smtp"]["user"]; 
      $mail->Password  = self::$config["smtp"]["pass"];     
    }

    $mail->IsHTML(true); 
    $mail->SetFrom(self::$config["email"], self::$config["title"]);
    $mail->AddReplyTo($array["from"]);
    $mail->AddAddress($array["to"]);
    $mail->Subject = $array["subject"];        
    $mail->msgHTML($array["message"]);

    if(!$mail->send()) {
        error_log("SMTP Error: {$mail->ErrorInfo}");
        $headers  = 'From:  '.self::$config["title"].' <'.self::$config["email"].'>' . "\r\n";
        $headers .= 'MIME-Version: 1.0' . "\r\n";
        $headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
        mail($array["to"], $array["subject"], $array["message"], $headers);
        return TRUE;
    } else {
      return TRUE;
    }          
  }     
  /**
   * Country Codes
   * @since 1.0
   **/
 public static function ccode($code,$reverse=FALSE){
    $array=array('AF' => 'Afghanistan', 'AX' => 'Aland Islands', 'AL' => 'Albania', 'DZ' => 'Algeria', 'AS' => 'American Samoa', 'AD' => 'Andorra', 'AO' => 'Angola', 'AI' => 'Anguilla', 'AQ' => 'Antarctica', 'AG' => 'Antigua And Barbuda', 'AR' => 'Argentina', 'AM' => 'Armenia', 'AW' => 'Aruba', 'AU' => 'Australia', 'AT' => 'Austria', 'AZ' => 'Azerbaijan', 'BS' => 'Bahamas', 'BH' => 'Bahrain', 'BD' => 'Bangladesh', 'BB' => 'Barbados', 'BY' => 'Belarus', 'BE' => 'Belgium', 'BZ' => 'Belize', 'BJ' => 'Benin', 'BM' => 'Bermuda', 'BT' => 'Bhutan', 'BO' => 'Bolivia', 'BA' => 'Bosnia And Herzegovina', 'BW' => 'Botswana', 'BV' => 'Bouvet Island', 'BR' => 'Brazil', 'IO' => 'British Indian Ocean Territory', 'BN' => 'Brunei Darussalam', 'BG' => 'Bulgaria', 'BF' => 'Burkina Faso', 'BI' => 'Burundi', 'KH' => 'Cambodia', 'CM' => 'Cameroon', 'CA' => 'Canada', 'CV' => 'Cape Verde', 'KY' => 'Cayman Islands', 'CF' => 'Central African Republic', 'TD' => 'Chad', 'CL' => 'Chile', 'CN' => 'China', 'CX' => 'Christmas Island', 'CC' => 'Cocos (Keeling) Islands', 'CO' => 'Colombia', 'KM' => 'Comoros', 'CG' => 'Congo', 'CD' => 'Congo, Democratic Republic', 'CK' => 'Cook Islands', 'CR' => 'Costa Rica', 'CI' => 'Cote D\'Ivoire', 'HR' => 'Croatia', 'CU' => 'Cuba', 'CY' => 'Cyprus', 'CZ' => 'Czech Republic', 'DK' => 'Denmark', 'DJ' => 'Djibouti', 'DM' => 'Dominica', 'DO' => 'Dominican Republic', 'EC' => 'Ecuador', 'EG' => 'Egypt', 'SV' => 'El Salvador', 'GQ' => 'Equatorial Guinea', 'ER' => 'Eritrea', 'EE' => 'Estonia', 'ET' => 'Ethiopia', 'FK' => 'Falkland Islands (Malvinas)', 'FO' => 'Faroe Islands', 'FJ' => 'Fiji', 'FI' => 'Finland', 'FR' => 'France', 'GF' => 'French Guiana', 'PF' => 'French Polynesia', 'TF' => 'French Southern Territories', 'GA' => 'Gabon', 'GM' => 'Gambia', 'GE' => 'Georgia', 'DE' => 'Germany', 'GH' => 'Ghana', 'GI' => 'Gibraltar', 'GR' => 'Greece', 'GL' => 'Greenland', 'GD' => 'Grenada', 'GP' => 'Guadeloupe', 'GU' => 'Guam', 'GT' => 'Guatemala', 'GG' => 'Guernsey', 'GN' => 'Guinea', 'GW' => 'Guinea-Bissau', 'GY' => 'Guyana', 'HT' => 'Haiti', 'HM' => 'Heard Island & Mcdonald Islands', 'VA' => 'Holy See (Vatican City State)', 'HN' => 'Honduras', 'HK' => 'Hong Kong', 'HU' => 'Hungary', 'IS' => 'Iceland', 'IN' => 'India', 'ID' => 'Indonesia', 'IR' => 'Iran, Islamic Republic Of', 'IQ' => 'Iraq', 'IE' => 'Ireland', 'IM' => 'Isle Of Man', 'IL' => 'Israel', 'IT' => 'Italy', 'JM' => 'Jamaica', 'JP' => 'Japan', 'JE' => 'Jersey', 'JO' => 'Jordan', 'KZ' => 'Kazakhstan', 'KE' => 'Kenya', 'KI' => 'Kiribati', 'KR' => 'Korea', 'KW' => 'Kuwait', 'KG' => 'Kyrgyzstan', 'LA' => 'Lao People\'s Democratic Republic', 'LV' => 'Latvia', 'LB' => 'Lebanon', 'LS' => 'Lesotho', 'LR' => 'Liberia', 'LY' => 'Libyan Arab Jamahiriya', 'LI' => 'Liechtenstein', 'LT' => 'Lithuania', 'LU' => 'Luxembourg', 'MO' => 'Macao', 'MK' => 'Macedonia', 'MG' => 'Madagascar', 'MW' => 'Malawi', 'MY' => 'Malaysia', 'MV' => 'Maldives', 'ML' => 'Mali', 'MT' => 'Malta', 'MH' => 'Marshall Islands', 'MQ' => 'Martinique', 'MR' => 'Mauritania', 'MU' => 'Mauritius', 'YT' => 'Mayotte', 'MX' => 'Mexico', 'FM' => 'Micronesia, Federated States Of', 'MD' => 'Moldova', 'MC' => 'Monaco', 'MN' => 'Mongolia', 'ME' => 'Montenegro', 'MS' => 'Montserrat', 'MA' => 'Morocco', 'MZ' => 'Mozambique', 'MM' => 'Myanmar', 'NA' => 'Namibia', 'NR' => 'Nauru', 'NP' => 'Nepal', 'NL' => 'Netherlands', 'AN' => 'Netherlands Antilles', 'NC' => 'New Caledonia', 'NZ' => 'New Zealand', 'NI' => 'Nicaragua', 'NE' => 'Niger', 'NG' => 'Nigeria', 'NU' => 'Niue', 'NF' => 'Norfolk Island', 'MP' => 'Northern Mariana Islands', 'NO' => 'Norway', 'OM' => 'Oman', 'PK' => 'Pakistan', 'PW' => 'Palau', 'PS' => 'Palestinian Territory, Occupied', 'PA' => 'Panama', 'PG' => 'Papua New Guinea', 'PY' => 'Paraguay', 'PE' => 'Peru', 'PH' => 'Philippines', 'PN' => 'Pitcairn', 'PL' => 'Poland', 'PT' => 'Portugal', 'PR' => 'Puerto Rico', 'QA' => 'Qatar', 'RE' => 'Reunion', 'RO' => 'Romania', 'RU' => 'Russian Federation', 'RW' => 'Rwanda', 'BL' => 'Saint Barthelemy', 'SH' => 'Saint Helena', 'KN' => 'Saint Kitts And Nevis', 'LC' => 'Saint Lucia', 'MF' => 'Saint Martin', 'PM' => 'Saint Pierre And Miquelon', 'VC' => 'Saint Vincent And Grenadines', 'WS' => 'Samoa', 'SM' => 'San Marino', 'ST' => 'Sao Tome And Principe', 'SA' => 'Saudi Arabia', 'SN' => 'Senegal', 'RS' => 'Serbia', 'SC' => 'Seychelles', 'SL' => 'Sierra Leone', 'SG' => 'Singapore', 'SK' => 'Slovakia', 'SI' => 'Slovenia', 'SB' => 'Solomon Islands', 'SO' => 'Somalia', 'ZA' => 'South Africa', 'GS' => 'South Georgia And Sandwich Isl.', 'ES' => 'Spain', 'LK' => 'Sri Lanka', 'SD' => 'Sudan', 'SR' => 'Suriname', 'SJ' => 'Svalbard And Jan Mayen', 'SZ' => 'Swaziland', 'SE' => 'Sweden', 'CH' => 'Switzerland', 'SY' => 'Syrian Arab Republic', 'TW' => 'Taiwan', 'TJ' => 'Tajikistan', 'TZ' => 'Tanzania', 'TH' => 'Thailand', 'TL' => 'Timor-Leste', 'TG' => 'Togo', 'TK' => 'Tokelau', 'TO' => 'Tonga', 'TT' => 'Trinidad And Tobago', 'TN' => 'Tunisia', 'TR' => 'Turkey', 'TM' => 'Turkmenistan', 'TC' => 'Turks And Caicos Islands', 'TV' => 'Tuvalu', 'UG' => 'Uganda', 'UA' => 'Ukraine', 'AE' => 'United Arab Emirates', 'GB' => 'United Kingdom', 'US' => 'United States', 'UM' => 'United States Outlying Islands', 'UY' => 'Uruguay', 'UZ' => 'Uzbekistan', 'VU' => 'Vanuatu', 'VE' => 'Venezuela', 'VN' => 'Viet Nam', 'VG' => 'Virgin Islands, British', 'VI' => 'Virgin Islands, U.S.', 'WF' => 'Wallis And Futuna', 'EH' => 'Western Sahara', 'YE' => 'Yemen', 'ZM' => 'Zambia', 'ZW' => 'Zimbabwe');
    if($reverse){
      $array=array_flip($array);
      if(isset($array[$code])) return $array[$code];      
    }
    $code=strtoupper($code);
    if(isset($array[$code])) return $array[$code];
  }
  /**
   * List of Countries and ISO Code
   * @since 5.6
   **/
  public static function countries($code="united states"){
    $countries = array('AF'=>'Afghanistan','AX'=>'Aland Islands','AL'=>'Albania','DZ'=>'Algeria','AS'=>'American Samoa','AD'=>'Andorra','AO'=>'Angola','AI'=>'Anguilla','AQ'=>'Antarctica','AG'=>'Antigua And Barbuda','AR'=>'Argentina','AM'=>'Armenia','AW'=>'Aruba','AU'=>'Australia','AT'=>'Austria','AZ'=>'Azerbaijan','BS'=>'Bahamas','BH'=>'Bahrain','BD'=>'Bangladesh','BB'=>'Barbados','BY'=>'Belarus','BE'=>'Belgium','BZ'=>'Belize','BJ'=>'Benin','BM'=>'Bermuda','BT'=>'Bhutan','BO'=>'Bolivia','BA'=>'Bosnia And Herzegovina','BW'=>'Botswana','BV'=>'Bouvet Island','BR'=>'Brazil','IO'=>'British Indian Ocean Territory','BN'=>'Brunei Darussalam','BG'=>'Bulgaria','BF'=>'Burkina Faso','BI'=>'Burundi','KH'=>'Cambodia','CM'=>'Cameroon','CA'=>'Canada','CV'=>'Cape Verde','KY'=>'Cayman Islands','CF'=>'Central African Republic','TD'=>'Chad','CL'=>'Chile','CN'=>'China','CX'=>'Christmas Island','CC'=>'Cocos (Keeling) Islands','CO'=>'Colombia','KM'=>'Comoros','CG'=>'Congo','CD'=>'Congo, Democratic Republic','CK'=>'Cook Islands','CR'=>'Costa Rica','CI'=>'Cote D\'Ivoire','HR'=>'Croatia','CU'=>'Cuba','CY'=>'Cyprus','CZ'=>'Czech Republic','DK'=>'Denmark','DJ'=>'Djibouti','DM'=>'Dominica','DO'=>'Dominican Republic','EC'=>'Ecuador','EG'=>'Egypt','SV'=>'El Salvador','GQ'=>'Equatorial Guinea','ER'=>'Eritrea','EE'=>'Estonia','ET'=>'Ethiopia','FK'=>'Falkland Islands (Malvinas)','FO'=>'Faroe Islands','FJ'=>'Fiji','FI'=>'Finland','FR'=>'France','GF'=>'French Guiana','PF'=>'French Polynesia','TF'=>'French Southern Territories','GA'=>'Gabon','GM'=>'Gambia','GE'=>'Georgia','DE'=>'Germany','GH'=>'Ghana','GI'=>'Gibraltar','GR'=>'Greece','GL'=>'Greenland','GD'=>'Grenada','GP'=>'Guadeloupe','GU'=>'Guam','GT'=>'Guatemala','GG'=>'Guernsey','GN'=>'Guinea','GW'=>'Guinea-Bissau','GY'=>'Guyana','HT'=>'Haiti','HM'=>'Heard Island & Mcdonald Islands','VA'=>'Holy See (Vatican City State)','HN'=>'Honduras','HK'=>'Hong Kong','HU'=>'Hungary','IS'=>'Iceland','IN'=>'India','ID'=>'Indonesia','IR'=>'Iran, Islamic Republic Of','IQ'=>'Iraq','IE'=>'Ireland','IM'=>'Isle Of Man','IL'=>'Israel','IT'=>'Italy','JM'=>'Jamaica','JP'=>'Japan','JE'=>'Jersey','JO'=>'Jordan','KZ'=>'Kazakhstan','KE'=>'Kenya','KI'=>'Kiribati','KR'=>'Korea','KW'=>'Kuwait','KG'=>'Kyrgyzstan','LA'=>'Lao People\'s Democratic Republic','LV'=>'Latvia','LB'=>'Lebanon','LS'=>'Lesotho','LR'=>'Liberia','LY'=>'Libyan Arab Jamahiriya','LI'=>'Liechtenstein','LT'=>'Lithuania','LU'=>'Luxembourg','MO'=>'Macao','MK'=>'Macedonia','MG'=>'Madagascar','MW'=>'Malawi','MY'=>'Malaysia','MV'=>'Maldives','ML'=>'Mali','MT'=>'Malta','MH'=>'Marshall Islands','MQ'=>'Martinique','MR'=>'Mauritania','MU'=>'Mauritius','YT'=>'Mayotte','MX'=>'Mexico','FM'=>'Micronesia, Federated States Of','MD'=>'Moldova','MC'=>'Monaco','MN'=>'Mongolia','ME'=>'Montenegro','MS'=>'Montserrat','MA'=>'Morocco','MZ'=>'Mozambique','MM'=>'Myanmar','NA'=>'Namibia','NR'=>'Nauru','NP'=>'Nepal','NL'=>'Netherlands','AN'=>'Netherlands Antilles','NC'=>'New Caledonia','NZ'=>'New Zealand','NI'=>'Nicaragua','NE'=>'Niger','NG'=>'Nigeria','NU'=>'Niue','NF'=>'Norfolk Island','MP'=>'Northern Mariana Islands','NO'=>'Norway','OM'=>'Oman','PK'=>'Pakistan','PW'=>'Palau','PS'=>'Palestinian Territory, Occupied','PA'=>'Panama','PG'=>'Papua New Guinea','PY'=>'Paraguay','PE'=>'Peru','PH'=>'Philippines','PN'=>'Pitcairn','PL'=>'Poland','PT'=>'Portugal','PR'=>'Puerto Rico','QA'=>'Qatar','RE'=>'Reunion','RO'=>'Romania','RU'=>'Russian Federation','RW'=>'Rwanda','BL'=>'Saint Barthelemy','SH'=>'Saint Helena','KN'=>'Saint Kitts And Nevis','LC'=>'Saint Lucia','MF'=>'Saint Martin','PM'=>'Saint Pierre And Miquelon','VC'=>'Saint Vincent And Grenadines','WS'=>'Samoa','SM'=>'San Marino','ST'=>'Sao Tome And Principe','SA'=>'Saudi Arabia','SN'=>'Senegal','RS'=>'Serbia','SC'=>'Seychelles','SL'=>'Sierra Leone','SG'=>'Singapore','SK'=>'Slovakia','SI'=>'Slovenia','SB'=>'Solomon Islands','SO'=>'Somalia','ZA'=>'South Africa','GS'=>'South Georgia And Sandwich Isl.','ES'=>'Spain','LK'=>'Sri Lanka','SD'=>'Sudan','SR'=>'Suriname','SJ'=>'Svalbard And Jan Mayen','SZ'=>'Swaziland','SE'=>'Sweden','CH'=>'Switzerland','SY'=>'Syrian Arab Republic','TW'=>'Taiwan','TJ'=>'Tajikistan','TZ'=>'Tanzania','TH'=>'Thailand','TL'=>'Timor-Leste','TG'=>'Togo','TK'=>'Tokelau','TO'=>'Tonga','TT'=>'Trinidad And Tobago','TN'=>'Tunisia','TR'=>'Turkey','TM'=>'Turkmenistan','TC'=>'Turks And Caicos Islands','TV'=>'Tuvalu','UG'=>'Uganda','UA'=>'Ukraine','AE'=>'United Arab Emirates','GB'=>'United Kingdom','US'=>'United States','UM'=>'United States Outlying Islands','UY'=>'Uruguay','UZ'=>'Uzbekistan','VU'=>'Vanuatu','VE'=>'Venezuela','VN'=>'Viet Nam','VG'=>'Virgin Islands, British','VI'=>'Virgin Islands, U.S.','WF'=>'Wallis And Futuna','EH'=>'Western Sahara','YE'=>'Yemen','ZM'=>'Zambia','ZW'=>'Zimbabwe');
    $form = "";
    foreach ($countries as $key => $value) {
      $form.='<option value="'.$value.'"'.($code == strtolower($value)?' selected':'').'>'.$value.'</option>';
    }
    return $form;
  }
  /**
   * Currency
   * @since 1.0
   **/
 public static function currency($code="",$amount=""){
    $array = array('AUD' => array('label'=>'Australian Dollar','format' => '$%s'),'CAD' => array('label' => 'Canadian Dollar','format' => '$%s'),'EUR' => array('label' => 'Euro','format' => '€ %s'),'GBP' => array('label' => 'Pound Sterling','format' => '£ %s'),'JPY' => array('label' => 'Japanese Yen','format' => '¥ %s'),'USD' => array('label' => 'U.S. Dollar','format' => '$%s'),'NZD' => array('label' => 'N.Z. Dollar','format' => '$%s'),'CHF' => array('label' => 'Swiss Franc','format' => '%s Fr'),'HKD' => array('label' => 'Hong Kong Dollar','format' => '$%s'),'SGD' => array('label' => 'Singapore Dollar','format' => '$%s'),'SEK' => array('label' => 'Swedish Krona','format' => '%s kr'),'DKK' => array('label' => 'Danish Krone','format' => '%s kr'),'PLN' => array('label' => 'Polish Zloty','format' => '%s zł'),'NOK' => array('label' => 'Norwegian Krone','format' => '%s kr'),'HUF' => array('label' => 'Hungarian Forint','format' => '%s Ft'),'CZK' => array('label' => 'Czech Koruna','format' => '%s Kč'),'ILS' => array('label' => 'Israeli New Sheqel','format' => '₪ %s'),'MXN' => array('label' => 'Mexican Peso','format' => '$%s'),'BRL' => array('label' => 'Brazilian Real','format' => 'R$%s'),'MYR' => array('label' => 'Malaysian Ringgit','format' => 'RM %s'),'PHP' => array('label' => 'Philippine Peso','format' => '₱ %s'),'TWD' => array('label' => 'New Taiwan Dollar','format' => 'NT$%s'),'THB' => array('label' => 'Thai Baht','format' => '฿ %s'),'TRY' => array('label' => 'Turkish Lira','format' => 'TRY %s'));
    if(empty($code)) return $array;
    
    $code=strtoupper($code);
    if(isset($array[$code])) return sprintf($array[$code]["format"],$amount);
  }   
 /**
  * Get Domain
  * @since 1.0
  **/  
 public static function domain($url,$scheme=TRUE,$http=TRUE){
   $url=parse_url($url);
   if(!isset($url["host"])) return false;
   return ($scheme ? ($http ? $url["scheme"]."://".$url["host"] : $url["host"] ) : ucfirst(str_replace("www.", "", $url["host"])));
 }
 /**
  * Cache Data
  * @since 4.0
  **/
 public static function cache_set($id,$data,$time){
  if(!self::$config["cache"]) return NULL;
  return phpFastCache::set($id,$data,60*$time);
 }
 /**
  * Cache Get
  * @since 4.0
  **/
 public static function cache_get($id){
  if(!self::$config["cache"]) return NULL;
  return phpFastCache::get($id);
 } 
 /**
  * Plug in
  * @since 5.4
  **/
  public static function plug($area, $param = array()){
    $return = "";
    if(isset(self::$plugin[$area]) && is_array(self::$plugin[$area])) {
      foreach (self::$plugin[$area] as $fn) {
       if(is_array($fn) && class_exists($fn[0]) && method_exists($fn[0], $fn[1])){        
          $f = $fn[1];
          $return .= $fn[0]::$f($param);       
        }elseif(function_exists($fn)){
          $return .= $fn($param);
        }
      }
      return $return;
    }
  }
 /**
  * Register Plug in
  * @since 5.4
  **/
  public static function hook($area, $fn){
    if(is_array($fn) && class_exists($fn[0]) && method_exists($fn[0], $fn[1])){
      self::$plugin[$area][] = $fn;  
      return;    
    }
    if(function_exists($fn)) {
      self::$plugin[$area][] = $fn;  
      return;
    }
  }
/**
  * [devices description]
  * @author KBRmedia <http://gempixel.com>
  * @version 4.3
  * @return  [type] [description]
  */
  public static function devices($code = "") { 
    $os       =   array(
                            'iphone'             =>  'iPhone',
                            'ipad'               =>  'iPad',
                            'android'            =>  'Android',
                            'blackberry'         =>  'BlackBerry',
                            'webos'              =>  'Other mobile'
                        );
    $form="";
    foreach ($os as $key => $value) {
      $form.='<option value="'.$value.'"'.($code == strtolower($value)?' selected':'').'>'.$value.'</option>';
    }
    return $form;
    return $os_platform;
  } 
  /**
  * [os description]
  * @author KBRmedia <http://gempixel.com>
  * @version 4.3
  * @return  [type] [description]
  */
  public static function os() { 
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    $os_platform    =   "Unknown OS";
    $os_array       =   array(
                            '/windows nt 10.0/i'    =>  'Windows 10',
                            '/windows nt 6.3/i'     =>  'Windows 8.1',
                            '/windows nt 6.2/i'     =>  'Windows 8',
                            '/windows nt 6.1/i'     =>  'Windows 7',
                            '/windows nt 6.0/i'     =>  'Windows Vista',
                            '/windows nt 5.2/i'     =>  'Windows Server 2003/XP x64',
                            '/windows nt 5.1/i'     =>  'Windows XP',
                            '/windows xp/i'         =>  'Windows XP',
                            '/windows nt 5.0/i'     =>  'Windows 2000',
                            '/windows me/i'         =>  'Windows ME',
                            '/win98/i'              =>  'Windows 98',
                            '/win95/i'              =>  'Windows 95',
                            '/win16/i'              =>  'Windows 3.11',
                            '/macintosh|mac os x/i' =>  'Mac OS X',
                            '/mac_powerpc/i'        =>  'Mac OS 9',
                            '/linux/i'              =>  'Linux',
                            '/ubuntu/i'             =>  'Ubuntu',
                            '/iphone/i'             =>  'iPhone',
                            '/ipod/i'               =>  'iPod',
                            '/ipad/i'               =>  'iPad',
                            '/android/i'            =>  'Android',
                            '/blackberry/i'         =>  'BlackBerry',
                            '/webos/i'              =>  'Mobile'
                        );
    foreach ($os_array as $regex => $value) { 
        if (preg_match($regex, $user_agent)) {
            $os_platform    =   $value;
        }
    }   
    return $os_platform;
  }
  /**
   * [browser description]
   * @author KBRmedia <http://gempixel.com>
   * @version 4.3
   * @return  [type] [description]
   */
  public static function browser() {
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    $browser        =   "Unknown Browser";
    $browser_array  =   array(
                            '/msie/i'       =>  'Internet Explorer',
                            '/Trident\/7.0; ASU2JS; rv:11.0/i'  =>  'Internet Explorer',
                            '/firefox/i'    =>  'Firefox',
                            '/safari/i'     =>  'Safari',
                            '/chrome/i'     =>  'Chrome',
                            '/opera/i'      =>  'Opera',
                            '/netscape/i'   =>  'Netscape',
                            '/maxthon/i'    =>  'Maxthon',
                            '/konqueror/i'  =>  'Konqueror',
                            '/mobile/i'     =>  'Handheld Browser'
                        );
    foreach ($browser_array as $regex => $value) { 
        if (preg_match($regex, $user_agent)) {
            $browser    =   $value;
        }
    }
    return $browser;
  } 
  /**
   * [save description]
   * @author KBRmedia <https://gempixel.com>
   * @version 5.3
   * @param   [type] $name  [description]
   * @param   [type] $value [description]
   * @return  [type]        [description]
   */
  public static function save($name, $value){
    $_SESSION[$name] = Main::clean($value);
    self::$session[] = $name;
  }
  /**
   * [get description]
   * @author KBRmedia <https://gempixel.com>
   * @version 5.3
   * @param   [type] $name [description]
   * @return  [type]       [description]
   */
  public static function get($name){
    if(isset($_SESSION[$name])) return $_SESSION[$name];
    return NULL;
  }
  /**
   * [clear description]
   * @author KBRmedia <https://gempixel.com>
   * @version 5.3
   * @return  [type] [description]
   */
  public static function clear(){
    foreach (self::$session as $session) {
      unset($_SESSION[$session]);
    }
  }
  /**
   * [checkOriginPolicy description]
   * @author KBRmedia <https://gempixel.com>
   * @version 5.3
   * @param   [type] $url [description]
   * @return  [type]      [description]
   */
  public static function iframePolicy($url){
    $url_headers = get_headers($url);
    foreach ($url_headers as $key => $value)
    {
        $x_frame_options_deny = strpos(strtolower($url_headers[$key]), strtolower('X-Frame-Options: DENY'));
        $x_frame_options_sameorigin = strpos(strtolower($url_headers[$key]), strtolower('X-Frame-Options: SAMEORIGIN'));
        $x_frame_options_allow_from = strpos(strtolower($url_headers[$key]), strtolower('X-Frame-Options: ALLOW-FROM'));
        if ($x_frame_options_deny !== false || $x_frame_options_sameorigin !== false || $x_frame_options_allow_from !== false)
        {
            return TRUE;
        }
    }
    return FALSE;    
  }
  /**
   * [readmore description]
   * @author KBRmedia <https://gempixel.com>
   * @version 1.0
   * @param   [type] $content [description]
   * @param   [type] $url     [description]
   * @param   string $text    [description]
   * @return  [type]          [description]
   */
  public static function readmore($content, $url, $text = "Read more"){
    $content = explode("<!--more-->", $content);
    $content = explode("&lt;!--more--&gt;", $content[0]);
    return strip_tags($content[0], "a")."<p><a href='".$url."' class='btn btn-primary btn-xs'>".e($text)."</a></p>";        
  }   
  /**
   * Share Buttons
   * @author KBRmedia
   * @since  5.7
   */
  public static function share($url, $title, $site = array()){
    $html  = "";
    $url = urlencode($url);
    if(empty($site) || in_array("facebook",$site)){
      $html .="<a href='http://www.facebook.com/sharer.php?u=$url' target='_blank' class='popup'><span class='fab fa-facebook'></span></a>";
    }
    if(empty($site) || in_array("twitter",$site)){
      $html .="<a href='http://twitter.com/share?url=$url&text=$title' target='_blank' class='popup'><span class='fab fa-twitter'></span></a>";
    }
    if(empty($site) || in_array("google",$site)){
      $html .="<a href='https://plus.google.com/share?url=$url' target='_blank' class='popup'><span class='fab fa-google'></span></a>";
    }
    if(empty($site) || in_array("digg",$site)){
      $html .="<a href='http://www.digg.com/submit?url=$url' target='_blank' class='popup'><span class='fab fa-digg'></span></a>";
    }
    if(empty($site) || in_array("reddit",$site)){
      $html .="<a href='http://reddit.com/submit?url=$url&title=$title' target='_blank' class='popup'><span class='fab fa-reddit'></span></a>";
    }
    if(empty($site) || in_array("linkedin",$site)){
      $html .="<a href='http://www.linkedin.com/shareArticle?mini=true&url=$url' target='_blank' class='popup'><span class='fab fa-linkedin'></span></a>";
    }
    if(empty($site) || in_array("stumbleupon",$site)){
      $html .="<a href='http://www.stumbleupon.com/submit?url=$url&title=$title' target='_blank' class='popup'><span class='fab fa-stumbleupon'></span></a>";
    }

    return $html;
  }    
  /**
   * [JSON description]
   * @author KBRmedia <https://gempixel.com>
   * @version 5.7
   * @param   array $array [description]
   */
  public static function JSON(array $array){
    if(isset($array["success"])){
      $array["msg"] = "<div class='alert alert-success'>{$array["success"]}</div><br>";
      unset($array["success"]);
    }
    if(isset($array["danger"])){
      $array["msg"] = "<div class='alert alert-danger'>{$array["danger"]}</div><br>";
      unset($array["danger"]);
    }
    header("content-type: application/json");
    return print json_encode($array);
  }
}