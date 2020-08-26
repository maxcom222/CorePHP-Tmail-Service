<?php

use Abraham\TwitterOAuth\TwitterOAuth;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;
use Facebook\Facebook;

use PhpImap\Exceptions\ConnectionException;
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
 * @subpackage User Class
 */
class User extends App{
    /**
     * Current Language
     * @since 4.0
     **/
    public $lang="";
    /**
     * Items Per Page
     * @since 4.0
     **/
    public $limit=15;
    /**
     * Template Variables
     * @since 4.0
     **/
    protected $isHome=FALSE;
    protected $footerShow=TRUE;
    protected $headerShow=TRUE;
    protected $is404=FALSE;
    protected $isUser=FALSE;
    /**
     * Application Variables
     * @since 4.0
     **/
    protected $page=1, $db, $config = array(), $id="", $parameter = "", $http="http";
    /**
     * User Variables
     * @since 4.0
     **/
    protected $logged=FALSE;
    protected $admin=FALSE, $user=NULL, $userid="0";
    /**
     * Constructor: Checks logged user status
     * @since 4.2.4
     **/
    public function __construct($db,$config){
        $this->config=$config;
        $this->db=$db;
        // Clean Request
        if(isset($_GET)) $_GET=array_map("Main::clean", $_GET);
        if(isset($_GET["page"]) && is_numeric($_GET["page"]) && $_GET["page"]>0) $this->page=Main::clean($_GET["page"]);
        $this->http=((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443)?"https":"http");
        $this->check();
        // Check if banned
        if($this->logged() && $this->user->banned){
            return $this->logout();
        }
    }
    /**
     * Run User Methods
     * @since 4.0
     **/
    protected function initiate($do,$id){
        $this->id = $id;
        return $this->$do();
    }
    /**
     * User Login
     * @since 5.7
     **/
    protected function login(){
        if(!empty($this->id)){
            // Check if private
            if($this->config["private"] || !$this->config["user"] || $this->config["maintenance"]) Main::redirect("?error",array("danger",e("Sorry, we are not accepting users right now.")));
            // Get method
            $fn = "login_{$this->id}";
            if(in_array($this->id, array("facebook","google","twitter", "2FA")) && method_exists("User",$fn)){
                return $this->$fn();
            }else{
                return $this->_404();
            }
        }
        // Login Count
        if(!isset($_SESSION["login_count"])){
            $_SESSION["login_count"]=0;
        }
        // Check if form is posted
        if(isset($_POST["token"])){
            // Prevent Bots from submitting the form
            if(Main::bot()) return $this->_404();
            // Validate CSRF Token
            if(!Main::validate_csrf_token($_POST["token"])){
                return Main::redirect(Main::href("user/login","",FALSE),array("danger",e("Invalid token. Please try again.")));
            }
            // Clean Current Session
            $this->logout(FALSE);
            // Block User
/*            if(Main::cookie("__bl")){
                return Main::redirect(Main::href("user/login","",FALSE),array("danger",e("You have been blocked for 1 hour due to many unsuccessful login attempts.")));
            }*/
            // Validate Email
            if(empty($_POST["email"])) return Main::redirect(Main::href("user/login","",FALSE),array("danger",e("Please enter a valid email or username.")));

            // Validate Password
            if(empty($_POST["password"]) || strlen($_POST["password"]) < 5) return Main::redirect(Main::href("user/login","",FALSE),array("danger",e("Wrong email and password combination.")));

            // Check captcha
            if($this->config["captcha"]){
                $captcha = Main::check_captcha($_POST);
                if($captcha != 'ok'){
                    return Main::redirect(Main::href("user/login","",FALSE),array("danger",e($captcha)));
                }
            }

            // Check if user exists - Check username and email
            if(!Main::email($_POST["email"])){
                $user = $this->db->get("user",array("username"=>"?"),array("limit"=>1),array($_POST["email"]));
            }else{
                $user = $this->db->get("user",array("email"=>"?"),array("limit"=>1),array($_POST["email"]));
            }
            if(!$user){
                return Main::redirect(Main::href("user/login","",FALSE),array("danger",e("Wrong email and password combination")));
            }
            // Upgrade password from MD5
            if($user->password === md5($this->config["security"].Main::clean($_POST["password"],3,FALSE))){
                $this->db->update("user",array("password"=>"?"),array("id"=>$user->id),array(Main::encode($_POST["password"])));
            }else{
                // Check new Password
                if(!Main::validate_pass($_POST["password"],$user->password)){
                    // Login Attempt Count
                    $max=5;
                    $_SESSION["login_count"]++;
                    if($_SESSION["login_count"] >= $max){
                        // Block user for 1 hour
                        Main::cookie("__bl",1,60);
                    }
                    return Main::redirect(Main::href("user/login","",FALSE),array("danger",e("Wrong email and password combination")));
                }
            }
            // Generate a new authkey for enhanced security each login
            $user->auth_key = Main::encode(Main::strrand(12));
            // Update database
            $this->db->update("user",array("auth_key"=>"?"),array("id"=>$user->id),array($user->auth_key));

            // Check if banned
            if($user->banned){
                return Main::redirect(Main::href("user/login","",FALSE),array("warning",e("You have been banned due to abuse. Please contact us for clarification.")));
            }
            // Check if inactive
            if(!$user->active){
                return Main::redirect(Main::href("user/login","",FALSE),array("danger",e("You haven't activated your account. Please check your email for the activation link. If you haven't received any emails from us, please contact us.")));
            }
            // Check if expired
            if(strtotime($user->expiration) < time()){
                $this->db->update("user",array("pro"=>0),array("id"=>$user->id));
            }

            // If not pro set as free plan
            if(!$user->pro){
                if(is_null($user->planid) || $this->db->get("plans", ["id" => $user->planid], ["limit" => 1])){
                    if($plan = $this->db->get("plans", ["free" => "1"], ["order" => "id", "limit" => 1])){
                        $this->db->update("user", ["planid" => $plan->id ], ["id" => $user->id]);
                    }
                }
            }

            if(!empty($user->secret2fa)) {
                $_SESSION["2FAkey"] = md5($user->secret2fa);
                return Main::redirect("user/login/2FA", ["success", e("Please enter the 2FA access code to login.")]);
            }

            // Set Session
            $json=base64_encode(json_encode(array("loggedin"=>TRUE,"key"=>$user->auth_key.$user->id)));
            if(isset($_POST["rememberme"]) && $_POST["rememberme"]=="1"){
                // Set Cookie for 14 days
                setcookie("login",$json, time()+60*60*24*14, "/","",FALSE,TRUE);
            }else{
                $_SESSION["login"]=$json;
            }
            if(isset($_SESSION["redirect"])){
                $r = Main::clean($_SESSION["redirect"], 3, TRUE);
                unset($_SESSION["redirect"]);
                return Main::redirect($r ,array("success",e("You have been successfully logged in.")));
            }
            // Return to /user
            return Main::redirect("",array("success",e("You have been successfully logged in.")));
        }

        // Set meta info
        Main::set("title",e("Login to your account"));
        Main::set("description","Login to your account and bookmark your favorite sites.");
        Main::set("url","{$this->config["url"]}/user/login");

        $this->headerShow=FALSE;
        $this->footerShow=FALSE;
        $this->header();
        include($this->t(__FUNCTION__));
        $this->footer();
    }

    /**
     * [login2FA description]
     * @author KBRmedia <https://gempixel.com>
     * @version 5.7
     * @return  [type] [description]
     */
    protected function login_2FA(){

        if(!isset($_SESSION["2FAkey"])) return Main::redirect("user/login");

        if(!$user = $this->db->get("user", "MD5(secret2fa) = ?", ["limit" => 1], [$_SESSION["2FAkey"]])){
            return Main::redirect("user/login", ["danger", "Invalid token. Please try again."]);
        }

        if(isset($_POST["token"])){
            if(!Main::validate_csrf_token($_POST["token"])){
                return Main::redirect(Main::href("user/login/2FA","",FALSE),array("danger",e("Invalid token. Please try again.")));
            }

            if(strlen($_POST["2fa"]) != 6) return Main::redirect(Main::href("user/login/2FA","",FALSE),array("danger",e("Invalid token. Please try again.")));

            include(ROOT."/includes/library/2FA.load.php");

            $gAuth = new \Sonata\GoogleAuthenticator\GoogleAuthenticator();

            if($gAuth->checkCode($user->secret2fa, Main::clean($_POST["2fa"], 3, TRUE))){
                // Set Session
                $json = base64_encode(json_encode(array("loggedin"=>TRUE, "key"=>$user->auth_key.$user->id)));
                setcookie("login", $json, time()+60*60*24*14, "/","",FALSE,TRUE);

                if(isset($_SESSION["redirect"])){
                    $r = Main::clean($_SESSION["redirect"], 3, TRUE);
                    unset($_SESSION["redirect"]);
                    return Main::redirect($r ,array("success",e("You have been successfully logged in.")));
                }
                // Return to /user
                return Main::redirect("user",array("success",e("You have been successfully logged in.")));
            }
            return Main::redirect(Main::href("user/login/2FA","",FALSE),array("danger",e("Invalid token. Please try again.")));
        }

        Main::set("title",e("Enter your 2FA access code"));
        Main::set("url","{$this->config["url"]}/user/login/2FA");

        $this->headerShow=FALSE;
        $this->footerShow=FALSE;
        $this->header();
        echo '<section>
						<div class="container">
							<div class="centered form">
					      <div class="site_logo">';
        if (!empty($this->config["logo"])){
            echo '<a href="'.$this->config["url"].'"><img src="'.$this->config["url"].'/content/'.$this->config["logo"].'" alt="'.$this->config["title"].'"></a>';
        }else{
            echo '<h3><a href="'.$this->config["url"].'">'.$this->config["title"].'</a></h3>';
        }
        echo '</div>

					      '.Main::message().'
					      <form role="form" class="live_form" method="post" action="'.Main::href("user/login/2FA").'">
					      	<p>'.e("The access code can be found on the Authenticator app. Please enter the code shown for this website. If you lost your phone or can't use the app, please contact us.").'</p>
					        <div class="form-group">
					          <label for="2fa">'.e("2FA Access Code").'</label>
					          <input type="text" class="form-control" id="2fa" placeholder="'.e("2FA Access Code").'" name="2fa" autocomplete="off">             
					        </div>        
					        	'.Main::csrf_token(TRUE).'
					        <button type="submit" class="btn btn-primary">'.e("Validate").'</button>        
					      </form>        
							</div>
						</div>
					</section>';
        $this->footer();
    }
    /**
     * User Login with Facebook
     * @since 5.0
     **/
    private function login_facebook(){
        //Facebook Auth
        if(!$this->config["fb_connect"]) return Main::redirect("",array("danger",e("Sorry, Facebook connect is not available right now.")));
        if(isset($_GET["error"])) return Main::redirect("",array("danger",e("You must grant access to this application to use your facebook account.")));

        include 'library/auth/Facebook/autoload.php';

        $fb = new Facebook\Facebook([
            'app_id' => $this->config["facebook_app_id"],
            'app_secret' => $this->config["facebook_secret"],
            'default_graph_version' => 'v2.12',
        ]);


        $helper = $fb->getRedirectLoginHelper(Main::href("user/login/facebook"));

        try {
            $accessToken = $helper->getAccessToken();
        } catch(Facebook\Exceptions\FacebookResponseException $e) {
            // Graph Error
            error_log($e->getMessage());
            return Main::redirect("",array("danger",e("An error has occured. Please try again later.")));
        } catch(Facebook\Exceptions\FacebookSDKException $e) {
            // SDK Error
            error_log($e->getMessage());
            return Main::redirect("",array("danger",e("An error has occured. Please try again later.")));
        }

        if(isset($accessToken) && !empty($accessToken)) {

            $request = $fb->get('/me?fields=id,email,name', $accessToken);
            $FBuser = $request->getGraphUser();

            if(!$FBuser->getEmail()) return Main::redirect("",array("danger",e("You must grant permission to this application to use your profile information.")));

            // Check if email is already taken
            if($this->db->get("user","auth!='facebook' AND email='".$FBuser->getEmail()."'",array("limit"=> "1"))){
                return Main::redirect("user/login",array("danger",e("The email linked to your account has been already used. If you have used that, please login to your existing account otherwise please contact us.")));
            }

            // Let's see if the user is registered
            if($user = $this->db->get("user","auth='facebook' AND (email='".$FBuser->getEmail()."' OR auth_id='".$FBuser->getId()."')",array("limit" => "1"))){

                // Check Auth Key: If empty generate one
                if(empty($user->auth_key)){
                    $user->auth_key=Main::encode(Main::strrand(12));
                    // Update database
                    $this->db->update("user",array("auth_key" => "?"),array("id"=>$user->id),array($user->auth_key));
                }
                // Inser AuthID
                if(empty($user->auth_id) && $FBuser->getId()){
                    // Update database
                    $this->db->update("user",array("auth_id" => "?"),array("id" => $user->id),array($FBuser->getId()));
                }

                // Check if banned
                if($user->banned){
                    return Main::redirect(Main::href("user/login","",FALSE),array("warning",e("You have been banned due to abuse. Please contact us for clarification.")));
                }
                // Check if inactive
                if(!$user->active){
                    return Main::redirect(Main::href("user/login","",FALSE),array("danger",e("You haven't activated your account. Please check your email for the activation link. If you haven't received any emails from us, please contact us.")));
                }

            }else{
                // Let's register the user
                $auth_key = Main::encode(Main::strrand(12));
                $data = array(
                    ":email" => Main::clean($FBuser->getEmail(),3,TRUE),
                    ":username" => "",
                    ":password" => Main::encode(Main::strrand(12)),
                    ":date" => "NOW()",
                    ":auth" => "facebook",
                    ":auth_id" => $FBuser->getId() ? Main::clean($FBuser->getId(),3,TRUE) : "",
                    ":api" => Main::strrand(12),
                    ":auth_key" => $auth_key
                );
                if($this->db->insert("user", $data)){
                    $user=$this->db->get("user",array("auth"=>"facebook","email" => $FBuser->getEmail()),array("limit" => "1"));
                }
            }
            // Ok Let's login te user
            $json=base64_encode(json_encode(array("loggedin"=>TRUE, "key"=>$user->auth_key.$user->id)));
            $_SESSION["login"] = $json;

            // Return to /user
            return Main::redirect("",array("success",e("You have been successfully logged in.")));
        }else{

            // Redirect to facebook
            $loginUrl = $helper->getLoginUrl(Main::href("user/login/facebook"), ["email"]);
            header("Location: ".$loginUrl);
            return;
        }
    }
    /**
     * User Login with Twitter
     * @since 5.3
     **/
    private function login_twitter(){
        // Check for error
        if(isset($_GET["denied"])) return Main::redirect("",array("danger",e("You must grant permission to this application to use your twitter account.")));

        if(!$this->config["tw_connect"]) return Main::redirect("",array("danger",e("Sorry, Twitter connect is not available right now.")));

        // Get Library
        require(ROOT."/includes/library/auth/Twitter/autoload.php");

        if(!empty($_GET['oauth_verifier']) && !empty($_SESSION['oauth_token']) && !empty($_SESSION['oauth_token_secret'])){

            $twitteroauth = new Abraham\TwitterOAuth\TwitterOAuth($this->config["twitter_key"], $this->config["twitter_secret"], $_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);

            $tw = $twitteroauth->oauth("oauth/access_token", ["oauth_verifier" => $_GET['oauth_verifier']]);


            if(!isset($tw["user_id"])) return Main::redirect("",array("danger",e("And error occured, please try again later.")));
            // Let's see if the user is registered
            if($user = $this->db->get("user","auth='twitter' AND auth_id='{$tw["user_id"]}'",array("limit"=>1))){

                // Check Auth Key: If empty generate one
                if(empty($user->auth_key)){
                    $user->auth_key=Main::encode(Main::strrand(12));
                    // Update database
                    $this->db->update("user",array("auth_key"=>"?"),array("id" => $user->id),array($user->auth_key));
                }

                // Check if banned
                if($user->banned){
                    return Main::redirect(Main::href("user/login","",FALSE),array("warning",e("You have been banned due to abuse. Please contact us for clarification.")));
                }
                // Check if inactive
                if(!$user->active){
                    return Main::redirect(Main::href("user/login","",FALSE),array("danger",e("You haven't activated your account. Please check your email for the activation link. If you haven't received any emails from us, please contact us.")));
                }

            }else{
                // Let's register the user
                $auth_key = Main::encode(Main::strrand(12));
                $data = array(
                    ":email" => "",
                    ":username" => "",
                    ":password" => Main::encode(Main::strrand(12)),
                    ":date" => "NOW()",
                    ":auth" => "twitter",
                    ":auth_id" => isset($tw["user_id"]) ? Main::clean($tw["user_id"],3,TRUE) : "",
                    ":api" => Main::strrand(12),
                    ":auth_key" => $auth_key
                );
                if($this->db->insert("user",$data)){
                    $user=$this->db->get("user",array("auth"=>"twitter","auth_id"=>$tw["user_id"]),array("limit"=>1));
                }
            }
            // Ok Let's login te user
            $json=base64_encode(json_encode(array("loggedin"=>TRUE,"key"=>$user->auth_key.$user->id)));
            $_SESSION["login"]=$json;

            // Return to /user
            return Main::redirect("",array("success",e("You have been successfully logged in.")));

        }
        // The TwitterOAuth instance
        $twitteroauth = new Abraham\TwitterOAuth\TwitterOAuth($this->config["twitter_key"],$this->config["twitter_secret"]);

        // Requesting authentication tokens, the parameter is the URL we will be redirected to
        $request_token = $twitteroauth->oauth("oauth/request_token", ["oauth_callback" => "{$this->config["url"]}/user/login/twitter"]);

        // Saving them into the session
        $_SESSION['oauth_token'] = $request_token['oauth_token'];
        $_SESSION['oauth_token_secret'] = $request_token['oauth_token_secret'];

        // If everything goes well..
        if($twitteroauth->getLastHttpCode() == 200){
            // Let's generate the URL and redirect
            $url = $twitteroauth->url("oauth/authorize", ["oauth_token" => $request_token['oauth_token']]);
            header('Location: '. $url);
            exit;
        } else {
            return Main::redirect("user/login",array('danger','An error has occured! Please make sure that you have set up this application as instructed.'));
        }
    }
    /**
     * User Login with Google
     * @since 4.2
     **/
    private function login_google(){
        // Check to make sure Google Auth is enabled
        if(!$this->config["gl_connect"] || empty($this->config["google_cid"]) || empty($this->config["google_cs"])) {
            return Main::redirect("",array("danger",e("Sorry, Google connect is not available right now.")));
        }
        // Get Class
        require(ROOT."/includes/library/auth/google.php");
        try {
            $google = new Google_Auth($this->config["google_cid"], $this->config["google_cs"], Main::href("user/login/google"), FALSE);

            if(!is_null($google->error)){
                return Main::redirect("",array("danger",$google->error));
            }

            $go = $google->info();
            if($go){
                if(!isset($go->email) || empty($go->email)){
                    echo "Kitty";
                    return Main::redirect("",array("danger",e("You must grant permission to this application to use your google account.")));
                }
                // Check if email is already taken
                if($this->db->get("user","auth!='google' AND email='{$go->email}'",array("limit"=>1))){
                    return Main::redirect("user/login",array("danger",e("The email linked to your account has been already used. If you have used that, please login to you existing account otherwise please contact us.")));
                }

                // Let's see if the user is registered
                if($user=$this->db->get("user",array("auth"=>"google","email"=>$go->email),array("limit"=>1))){

                    // Check Auth Key: If empty generate one
                    if(empty($user->auth_key)){
                        $user->auth_key=Main::encode(Main::strrand(12));
                        // Update database
                        $this->db->update("user",array("auth_key"=>"?"),array("id"=>$user->id),array($user->auth_key));
                    }

                    // Check if banned
                    if($user->banned){
                        return Main::redirect(Main::href("user/login","",FALSE),array("warning",e("You have been banned due to abuse. Please contact us for clarification.")));
                    }
                    // Check if inactive
                    if(!$user->active){
                        return Main::redirect(Main::href("user/login","",FALSE),array("danger",e("You haven't activated your account. Please check your email for the activation link. If you haven't received any emails from us, please contact us.")));
                    }
                }else{
                    // Let's register the user
                    $auth_key = Main::encode(Main::strrand(12));
                    $data = array(
                        ":email" => Main::clean($go->email,3,TRUE),
                        ":username" => isset($go->name) ? Main::slug($go->name).rand(1,100) : "",
                        ":password" => Main::encode(Main::strrand(12)),
                        ":date" => "NOW()",
                        ":auth" => "google",
                        ":api" => Main::strrand(12),
                        ":auth_key" => $auth_key
                    );
                    if($this->db->insert("user",$data)){
                        $user=$this->db->get("user",array("auth"=>"google","email"=>$go->email),array("limit"=>1));
                    }
                }
                // Ok Let's login te user
                $json=base64_encode(json_encode(array("loggedin"=>TRUE,"key"=>$user->auth_key.$user->id)));
                $_SESSION["login"]=$json;

                // Return to /user
                return Main::redirect("",array("success",e("You have been successfully logged in.")));
            }

        } catch(ErrorException $e) {
            return Main::redirect("",array("danger",e("An error occured, please try again later.")));
        }
        exit;
    }
    /**
     * User Logout
     * @since 4.0
     **/
    protected function logout($redirect=TRUE){
        // Destroy Cookie
        if(isset($_COOKIE["login"])) setcookie('login','',time()-3600,'/');
        // Destroy Session
        if(isset($_SESSION["login"])) unset($_SESSION["login"]);
        if($redirect) return Main::redirect("");
    }
    /**
     * User Register
     * @since 5.5
     **/
    protected function register(){
        // If user Module is disabled
        if(!$this->config["user"] || $this->config["private"] || $this->config["maintenance"]) return Main::redirect("",array("danger",e("We are not accepting users at this time.")));

        // Filter ID
        $this->filter($this->id);
        // Check if form is posted
        if(isset($_POST["token"])){
            // Don't let bots register
            if(Main::bot()) return $this->_404();
            // Validate CSRF Token
            if(!Main::validate_csrf_token($_POST["token"])){
                return Main::redirect(Main::href("user/register","",FALSE),array("danger",e("Invalid token. Please try again.")));
            }
            $error="";
            // Validate Email
            if(empty($_POST["email"]) || !Main::email($_POST["email"])) $error.="<span>".e("Please enter a valid email.")."</span>";
            Main::save("email", Main::clean($_POST["email"], 3, TRUE));

            // Check email in database
            if(!empty($_POST["email"]) && $this->db->get("user",array("email"=>"?"),"",array($_POST["email"]))) return Main::redirect(Main::href("user/register","",FALSE),array("danger",e("An account is already associated with this email.")));
            // Check Password
            if(empty($_POST["password"]) || strlen($_POST["password"])<5) $error.="<span>".e("Password must contain at least 5 characters.")."</span>";
            // Check second password
            if(empty($_POST["cpassword"]) || $_POST["password"]!==$_POST["cpassword"]) $error.="<span>".e("Passwords don't match.")."</span>";

            // Check captcha
            if($this->config["captcha"]){
                $captcha=Main::check_captcha($_POST);
                if($captcha!='ok'){
                    $error.="<span>".$captcha."</span>";
                }
            }

            // Check terms
            if(!isset($_POST["terms"]) || (empty($_POST["terms"]) || $_POST["terms"]!=="1")) $error.="<span>".e("You must agree to our terms of service.")."</span>";

            // Generate unique auth key
            $auth_key = Main::encode($this->config["security"].Main::strrand());
            $unique = Main::strrand(12);
            // Prepare Data
            $data = array(
                ":email" => Main::clean($_POST["email"],3),
                ":password" => Main::encode($_POST["password"]),
                ":auth_key" => $auth_key,
                ":api" => $unique,
                ":date" => "NOW()"
            );
            // Validate Name
            if(!empty($_POST["username"])){
                if (!Main::username($_POST["username"])){
                    $error.="<span>".e("Please enter a valid username.")."</span>";
                }elseif($this->db->get("user",array("username"=>"?"),array("limit"=>1),array($_POST["username"]))){
                    $error.="<span>".e("An account is already associated with this username.")."</span>";
                }else{
                    $data[":username"]=Main::slug(Main::clean($_POST["username"],3,TRUE));
                }
                Main::save("username", Main::clean($_POST["username"], 3, TRUE));
            }

            // Return errors
            if(!empty($error)) Main::redirect(Main::href("user/register","",FALSE),array("danger",$error));

            Main::clear();

            // Check if user activation is required
            if($this->config["user_activate"]) $data[":active"]="0";

            // Register User
            if($this->db->insert("user",$data)){

                // Send Activation Email
                if($this->config["user_activate"]){
                    // Send Email
                    $mail["to"] = Main::clean($_POST["email"],3);
                    $key = str_replace("=","",base64_encode("P1U2{$unique}".Main::strrand(5)));
                    $activate = "{$this->config["url"]}/user/activate/$key?email={$mail["to"]}";

                    $mail["subject"] = "[{$this->config["title"]}] Registration has been successful.";

                    $mail["message"] = str_replace("{site.title}", $this->config["title"], $this->config["email.activation"]);
                    $mail["message"] = str_replace("{site.link}", $this->config["url"], $mail["message"]);
                    $mail["message"] = str_replace("{user.username}", "", $mail["message"]);
                    $mail["message"] = str_replace("{user.activation}", $activate, $mail["message"]);
                    $mail["message"] = str_replace("http://http", "http", $mail["message"]);
                    $mail["message"] = str_replace("{user.email}", $data[":email"], $mail["message"]);
                    $mail["message"] = str_replace("{user.date}", date("d-m-Y"), $mail["message"]);

                    Main::send($mail);
                    return Main::redirect(Main::href("user/login","",FALSE),array("success",e("An email has been sent to activate your account. Please check your spam folder if you didn't receive it.")));
                }

                // Send Email
                $mail["to"]=Main::clean($_POST["email"],3);
                $mail["subject"] = "[{$this->config["title"]}] Registration has been successful.";

                $mail["message"] = str_replace("{site.title}", $this->config["title"], $this->config["email.registration"]);
                $mail["message"] = str_replace("{site.link}", $this->config["url"], $mail["message"]);
                $mail["message"] = str_replace("{user.username}", "", $mail["message"]);
                $mail["message"] = str_replace("{user.activation}", "", $mail["message"]);
                $mail["message"] = str_replace("http://http", "http", $mail["message"]);
                $mail["message"] = str_replace("{user.email}", $data[":email"], $mail["message"]);
                $mail["message"] = str_replace("{user.date}", date("d-m-Y"), $mail["message"]);

                Main::send($mail);
                return Main::redirect(Main::href("user/login","",FALSE),array("success",e("You have been successfully registered.")));
            }
        }
        // Set Meta titles
        Main::set("body_class","dark");
        Main::set("title",e("Register and manage your urls."));
        Main::set("description","Register an account and gain control over your urls. Manage them, edit them or remove them without hassle.");
        $this->headerShow=FALSE;
        $this->footerShow=FALSE;

        $this->header();
        include($this->t(__FUNCTION__));
        $this->footer();
    }
    /**
     * User Activate
     * @since 5.5
     **/
    protected function activate(){
        if(Main::bot()) return $this->_404();
        if(!empty($this->id)){
            $email=Main::clean($_GET["email"],3,TRUE);
            $id=str_replace("P1U2","",base64_decode($this->id));
            $id=substr($id, 0,12);
            if($user=$this->db->get("user",array("api"=>"?","active"=>"0","email"=>"?"),array("limit"=>1),array($id,$email))){
                $this->db->update("user",array("active"=>"1"),array("id"=>$user->id));
                // Send Email
                $mail["to"]=Main::clean($user->email,3);
                $mail["subject"]="[{$this->config["title"]}] Your account has been activated.";

                $mail["message"] = str_replace("{site.title}", $this->config["title"], $this->config["email.activated"]);
                $mail["message"] = str_replace("{site.link}", $this->config["url"], $mail["message"]);
                $mail["message"] = str_replace("{user.username}", $user->username, $mail["message"]);
                $mail["message"] = str_replace("{user.activation}", "", $mail["message"]);
                $mail["message"] = str_replace("http://http", "http", $mail["message"]);
                $mail["message"] = str_replace("{user.email}", $user->email, $mail["message"]);
                $mail["message"] = str_replace("{user.date}", date("d-m-Y", strtotime($user->date)), $mail["message"]);


                Main::send($mail);
                return Main::redirect(Main::href("user/login","",FALSE),array("success",e("Your account has been successfully activated.")));
            }
        }
        return Main::redirect(Main::href("user/login","",FALSE),array("danger",e("Wrong activation token or account already activated.")));
    }
    /**
     * User Forgot
     * @since 5.5
     **/
    protected function forgot(){
        // Change Password if valid token
        if(isset($this->id) && !empty($this->id)){
            $new=base64_decode($this->id);
            $key=substr($new, 12);
            $unique=substr($new, 0,12);
            if($key==Main::encode($this->config["security"].": Expires on".strtotime(date('Y-m-d')),"md5")){
                // Change Password
                if(isset($_POST["token"])){
                    // Validate CSRF Token
                    if(!Main::validate_csrf_token($_POST["token"])){
                        return Main::redirect(Main::href("user/forgot/{$this->id}","",FALSE),array("danger",e("Invalid token. Please try again.")));
                    }
                    // Check Password
                    if(empty($_POST["password"]) || strlen($_POST["password"])<5) return Main::redirect(Main::href("user/forgot/{$this->id}","",FALSE),array("danger",e("Password must contain at least 5 characters.")));
                    // Check second password
                    if(empty($_POST["cpassword"]) || $_POST["password"]!==$_POST["cpassword"]) return Main::redirect(Main::href("user/forgot/{$this->id}","",FALSE),array("danger",e("Passwords don't match.")));
                    // Add to database
                    $auth_key=Main::encode(Main::strrand(12));
                    if($this->db->update("user",array("password"=>"?","auth_key"=>"?"),array("api"=>"?"),array(Main::encode($_POST["password"]),$auth_key,$unique))){
                        return Main::redirect(Main::href("user/login","",FALSE),array("success",e("Your password has been changed.")));
                    }
                }
                // Set Meta titles
                Main::set("body_class","dark");
                Main::set("title",e("Reset Password"));
                $this->headerShow=FALSE;
                $this->footerShow=FALSE;

                $this->header();
                include($this->t(__FUNCTION__));
                $this->footer();
                return;
            }
            return Main::redirect(Main::href("user/login#forgot","",FALSE),array("danger",e("Token has expired, please request another link.")));
        }
        // Check if form is posted to send token
        if(isset($_POST["token"])){
            // Validate CSRF Token
            if(!Main::validate_csrf_token($_POST["token"])){
                return Main::redirect(Main::href("user/login#forgot","",FALSE),array("danger",e("Invalid token. Please try again.")));
            }
            // Validate email
            if(empty($_POST["email"]) || !Main::email($_POST["email"])) return Main::redirect(Main::href("user/login#forgot","",FALSE),array("danger",e("Please enter a valid email.")));

            // Check captcha
            if($this->config["captcha"]){
                $captcha = Main::check_captcha($_POST);
                if($captcha != 'ok'){
                    return Main::redirect(Main::href("user/login#forgot","",FALSE),array("danger",e($captcha)));
                }
            }

            // Check email
            if($user=$this->db->get("user",array("email"=>"?","banned"=>"0"),array("limit"=>1),array($_POST["email"]))){
                // Generate key
                $forgot_url=Main::href("user/forgot/".str_replace("=","", base64_encode($user->api.Main::encode($this->config["security"].": Expires on".strtotime(date('Y-m-d')),"md5"))));
                $mail["to"] = Main::clean($user->email);
                $mail["subject"] = "[{$this->config["title"]}] Password Reset Instructions";

                $mail["message"] = str_replace("{site.title}", $this->config["title"], $this->config["email.reset"]);
                $mail["message"] = str_replace("{site.link}", $this->config["url"], $mail["message"]);
                $mail["message"] = str_replace("{user.username}", $user->username, $mail["message"]);
                $mail["message"] = str_replace("{user.activation}", $forgot_url, $mail["message"]);
                $mail["message"] = str_replace("http://http", "http", $mail["message"]);
                $mail["message"] = str_replace("{user.email}", $user->email, $mail["message"]);
                $mail["message"] = str_replace("{user.date}", date("d-m-Y", strtotime($user->date)), $mail["message"]);

                // Send email
                Main::send($mail);
            }
            return Main::redirect(Main::href("user/login","",FALSE),array("success",e("If an active account is associated with this email, you should receive an email shortly.")));
        }
        return Main::redirect(Main::href("user/login#forgot","",FALSE));
    }
    /**
     * [invite description]
     * @author KBRmedia <https://gempixel.com>
     * @version 1.0
     * @return  [type] [description]
     */
    protected function invite(){

        $authkey = Main::clean($this->id, 3, TRUE);

        if(!$user = $this->db->get("user", "MD5(auth_key) = ?", ["limit" => 1], [$authkey])){
            return Main::redirect(Main::href("user/login","",FALSE),array("danger",e("The invitation link has expired or is currently unavailable. Please contact administrator.")));
        }

        if(isset($_POST["token"])){
            // Validate CSRF Token
            if(!Main::validate_csrf_token($_POST["token"])){
                return Main::redirect(Main::href("user/invite/{$this->id}","",FALSE),array("danger",e("Invalid token. Please try again.")));
            }
            $error = "";
            // Validate Name
            if (empty($_POST["username"]) || !Main::username($_POST["username"])){
                $error .= "<span>".e("Please enter a valid username.")."</span>";
            }elseif($this->db->get("user",array("username"=>"?"),array("limit"=>1),array($_POST["username"]))){
                $error .= "<span>".e("An account is already associated with this username.")."</span>";
            }

            // Check Password
            if(empty($_POST["password"]) || strlen($_POST["password"])<5) $error.="<span>".e("Password must contain at least 5 characters.")."</span>";
            // Check second password
            if(empty($_POST["cpassword"]) || $_POST["password"]!==$_POST["cpassword"]) $error.="<span>".e("Passwords don't match.")."</span>";

            // Check captcha
            if($this->config["captcha"]){
                $captcha=Main::check_captcha($_POST);
                if($captcha!='ok'){
                    $error.="<span>".$captcha."</span>";
                }
            }

            // Check terms
            if(!isset($_POST["terms"]) || (empty($_POST["terms"]) || $_POST["terms"]!=="1")) $error.="<span>".e("You must agree to our terms of service.")."</span>";

            if(!empty($error)) return Main::redirect(Main::href("user/invite/{$this->id}","",FALSE),array("danger",$error));

            // Add to database
            $username = Main::slug($_POST["username"]);
            $auth_key = Main::encode($this->config["security"].Main::strrand(12));
            if($this->db->update("user", ["username" => "?", "password"=>"?", "auth_key"=>"?", "active" => "?"], ["id" => "?"], [$username, Main::encode($_POST["password"]), $auth_key, "1", $user->id])){
                return Main::redirect(Main::href("user/login","",FALSE),array("success",e("Your account has been successfully activated.")));
            }
        }

        // Set Meta titles
        Main::set("body_class","dark");
        Main::set("title", e("Accept Invitation"));
        $this->headerShow=FALSE;
        $this->footerShow=FALSE;

        $this->header();
        include($this->t(__FUNCTION__));
        $this->footer();
    }
    /**
     * Search URLs (AJAX only)
     * @since 5.6
     */
    private function search(){
        if(!isset($_POST["token"]) || $_POST["token"]!==$this->config["public_token"]) return $this->_404();
        // Prevent Bots from submitting the form
        if(Main::bot()) return $this->_404();
        $q=Main::clean($_POST["q"],3);
        if(!empty($q) && strlen($q)>=3){
            if($urls=$this->db->search("url",array(array("userid",$this->user->id),"url"=>":q","alias"=>":q","custom"=>":q","meta_title"=>":q","description"=>":q"),array("order"=>"date"),array(":q"=>"%{$q}%"))){
                echo "<a href='#clear' class='btn btn-xs btn-default clear-search'>".e('Clear Search')."</a>";
                foreach ($urls as $url) {
                    include(TEMPLATE."/shared/url_loop.php");
                }
                return;
            }
        }
        echo "<a href='#clear' class='btn btn-xs btn-default clear-search'>".e('Clear Search')."</a>
			<div class='alert alert-danger'>".e('Nothing found.')."</div><br>";
    }
    /**
     * Arhive
     * @since 5.6
     **/
    protected function archive(){
        // Get URLs
        $order=array("date",FALSE,"newest");
        if(isset($_GET["sort"])){
            if(Main::clean($_GET["sort"],3,TRUE)=="popular"){
                $order=array("click",FALSE,"popular");
            }elseif(Main::clean($_GET["sort"],3,TRUE)=="oldest"){
                $order=array("date",TRUE,"oldest");
            }
        }
        $urls=$this->db->get("url",array("userid"=>"?","archived"=>"1"),array("order"=>$order[0],"limit"=>(($this->page-1)*$this->limit).", {$this->limit}","count"=>TRUE,"asc"=>$order[1]),array($this->user->id));

        if(($this->db->rowCount%$this->limit)<>0) {
            $max=floor($this->db->rowCount/$this->limit)+1;
        } else {
            $max=floor($this->db->rowCount/$this->limit);
        }
        if($this->page > 1 && $this->page > $max) Main::redirect("user",array("danger","No URLs found."));
        $pagination = Main::pagination($max,$this->page,Main::href("user/archive?filter={$order[2]}&amp;page=%d"));

        // Show Template
        $this->isUser=TRUE;
        $archive = TRUE;
        Main::cdn("datepicker");
        Main::set("title",e("Archived URLs"));
        $this->header();
        include($this->t("user"));
        $this->footer();
    }
    /**
     * Expired URLs
     * @author KBRmedia <http://gempixel.com>
     * @version 5.6
     * @return  [type] [description]
     */
    protected function expired(){
        // Get URLs
        $order = array("date",FALSE,"newest");

        if(isset($_GET["sort"])){
            if(Main::clean($_GET["sort"],3,TRUE)=="popular"){
                $order=array("click",FALSE,"popular");
            }elseif(Main::clean($_GET["sort"],3,TRUE)=="oldest"){
                $order=array("date",TRUE,"oldest");
            }
        }

        $urls = $this->db->get("url","userid = '{$this->user->id}' AND expiry < DATE(CURDATE()) AND archived = '0'", array("order"=>$order[0], "limit"=>(($this->page-1)*$this->limit).", {$this->limit}","count"=>TRUE,"asc"=>$order[1]),array($this->user->id));

        if(($this->db->rowCount%$this->limit)<>0) {
            $max=floor($this->db->rowCount/$this->limit)+1;
        } else {
            $max=floor($this->db->rowCount/$this->limit);
        }
        if($this->page > 1 && $this->page > $max) Main::redirect("user",array("danger","No URLs found."));
        $pagination = Main::pagination($max,$this->page,Main::href("user/expired?filter={$order[2]}&amp;page=%d"));

        // Show Template
        $this->isUser=TRUE;
        Main::cdn("datepicker");
        Main::set("title",e("Expired URLs"));
        $this->header();
        include($this->t("user"));
        $this->footer();
    }

    function formatSizeUnits($bytes)
    {
        if ($bytes >= 1073741824)
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        elseif ($bytes >= 1048576)
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        elseif ($bytes >= 1024)
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        elseif ($bytes > 1)
            $bytes = $bytes . ' bytes';
        elseif ($bytes == 1)
            $bytes = $bytes . ' byte';
        else
            $bytes = '0 bytes';
        return $bytes;
    }

    protected function tmails()
    {
        if(isset($_POST['action_type']) && $_POST['action_type'] == "download")
        {
            error_reporting ( E_ALL ) ;
            $url = $this->config["incoming"];
            $port = $this->config["incoming_port"];
            $email = $this->config["based_email"];
            $password = $this->config["password"];
            $hosturl = '{'.$url.':'.$port.'/imap/ssl}INBOX';
            $temp = $_POST['tmailBT'];
            if($temp == "SentMail")
            {
                $hosturl = '{'.$url.':'.$port.'/imap/ssl}INBOX.Sent';
            }
            $mailbox = new PhpImap\Mailbox($hosturl, $email, $password, ROOT.'/content', 'UTF-8');
            // try {
            //     $mailsIds = $mailbox->searchMailbox('ALL');
            //     $mails = $mailbox->getMailsInfo($mailsIds);
            //     for ($i=0; $i < sizeof($mails); $i++) {
            //         $oneMail = $mails[$i];
            //         $msgid = $mailsIds[$i];
            //         echo "<pre>";
            //         var_dump($oneMail);
            //         echo "</pre>";
            //         if ($_POST['uid'] == $oneMail->uid)
            //         {
            //             $mail = $mailbox->getMailMboxFormat($uid);
            //             file_put_contents(ROOT."/static/email.eml", $mail);
            //             echo "<pre>";
            //             var_dump($mail);
            //             echo "</pre>";
            //             exit(ROOT."/static/email_5.eml");
            //             break;
            //         }
            //     }
            // } catch(PhpImap\Exceptions\ConnectionException $ex) {
            //     echo "IMAP connection failed: " . $ex;
            //     die();
            // }
            // exit("error");
            if (!$mailbox) {
                echo "Cannot connect to mailbox\n";
                exit;
            } else {
                $uid = $_POST['uid'];
                $mmm = $mailbox->getMailMboxFormat($uid);
                file_put_contents(ROOT."/static/email_{$uid}.eml", $mmm);
                exit("/static/email_{$uid}.eml");
            }
        }
        $tmailcount = 0;
        $tmails = array();
        $user_name =  $this->db->get("user",array("id"=>"?"), array("count"=>TRUE), array($this->user->id));
        if(count($user_name) > 0) {
            $tmails[$tmailcount] = $user_name[0]->username . "@" . $this->config["tmail_domain"];
            $tmailcount++;
        }
        $user_urls = $this->db->get("url",array("userid"=>"?"),array("count"=>TRUE),array($this->user->id));
        if(count($user_urls))
        {
            foreach ($user_urls as $user_url)
            {
                $tmails[$tmailcount]=strtolower($user_url->alias) . "@" . $this->config["tmail_domain"];
            }
        }
        $this->isUser=TRUE;
        Main::set("title",e("Temporary E-Mail Service"));
        Main::set("description","Temporary E-Mail Service.");
        $this->header();
        include($this->t(__FUNCTION__));
        $this->footer();
    }

    /**
     * Bundles
     * @since 5.6
     **/
    protected function bundles(){
        if(isset($_POST["token"])){
            if($this->id=="add"){
                // Validate CSRF Token
                if(!Main::validate_csrf_token($_POST["token"])){
                    return Main::redirect(Main::href("user/bundles","",FALSE),array("danger",e("Invalid token. Please try again.")));
                }
                if($this->isTeam() && !$this->teamPermission("bundle.create")){
                    return Main::redirect("user",array("danger",e("You do not have this permission. Please contact your team administrator.")));
                }
                if(empty($_POST["name"]) || strlen(Main::clean($_POST["name"]))<2){
                    return Main::redirect(Main::href("user/bundles","",FALSE),array("danger",e("Bundle name cannot be empty and must have at least 2 characters.")));
                }
                if($this->db->get("bundle",array("name"=>"?","userid"=>"?"),"",array(Main::clean(trim($_POST["name"])),$this->user->id))){
                    return Main::redirect(Main::href("user/bundles","",FALSE),array("danger",e("You already have a bundle with that name.")));
                }
                $slug = "";
                if(!empty($_POST["slug"])){
                    $slug = Main::slug($_POST["slug"]);
                    if($this->db->get("bundle", ["slug" => $slug], ["limit" => 1])){
                        return Main::redirect(Main::href("user/bundles","",FALSE),array("danger",e("This slug is currently not available.")));
                    }
                }

                $data = array(
                    ":name"=> ucfirst(Main::clean($_POST["name"],3,TRUE)),
                    ":slug"=> $slug,
                    ":access"=> in_array(Main::clean($_POST["access"],3,TRUE), array("public","private"))?Main::clean($_POST["access"],3,TRUE):"private",
                    ":userid"=> $this->user->id,
                    ":date"=>"NOW()"
                );
                if($this->db->insert("bundle",$data)){
                    return Main::redirect(Main::href("user/bundles","",FALSE),array("success",e("Bundle was successfully created. You may start adding URLs in it now.")));
                }
            }
            if($this->id=="edit"){
                // Validate CSRF Token
                if(!Main::validate_csrf_token($_POST["token"])){
                    return Main::redirect(Main::href("user/bundles","",FALSE),array("danger",e("Invalid token. Please try again.")));
                }
                if($this->isTeam() && !$this->teamPermission("bundle.edit")){
                    return Main::redirect("user",array("danger",e("You do not have this permission. Please contact your team administrator.")));
                }
                if(empty($_POST["name"]) || strlen(Main::clean($_POST["name"]))<2){
                    return Main::redirect(Main::href("user/bundles","",FALSE),array("danger",e("Bundle name cannot be empty and must have at least 2 characters.")));
                }
                if($this->db->get("bundle","name=? AND userid=? AND id!=?","",array(Main::clean(trim($_POST["name"])),$this->user->id,Main::clean($_POST["id"])))){
                    return Main::redirect(Main::href("user/bundles","",FALSE),array("danger",e("You already have a bundle with that name.")));
                }
                $slug = "";
                if(!empty($_POST["slug"])){
                    $slug = Main::slug($_POST["slug"]);
                    if($bundle = $this->db->get("bundle", ["id" => "?"], ["limit" => "1"], [$_POST["id"]])){
                        if($_POST["slug"] != $bundle->slug && $this->db->get("bundle", ["slug" => $slug], ["limit" => 1])){
                            return Main::redirect(Main::href("user/bundles","",FALSE),array("danger",e("This slug is currently not available.")));
                        }
                    }
                }
                $data = array(
                    ":name"=> ucfirst(Main::clean($_POST["name"],3,TRUE)),
                    ":slug" => $slug,
                    ":access"=> in_array(Main::clean($_POST["access"],3,TRUE), array("public","private"))?Main::clean($_POST["access"],3,TRUE):"private"
                );
                if($this->db->update("bundle","",array("userid" => $this->user->id, "id" => Main::clean($_POST["id"],3,TRUE)), $data)){
                    return Main::redirect(Main::href("user/bundles","",FALSE),array("success",e("Bundle has been updated.")));
                }
            }
            if($this->id=="update"){
                if(!Main::validate_csrf_token($_POST["token"])){
                    Main::redirect(Main::href("user/bundles","",FALSE),array("danger",e("Something went wrong, please try again.")));
                    return;
                }
                // Check if user owns bundle
                if(!empty($_POST["bundle_id"]) && !$this->db->get("bundle",array("id"=>"?","userid"=>"?"),"",array(Main::clean($_POST["bundle_id"],3,TRUE),$this->user->id))){
                    return Main::redirect(Main::href("user/bundles","",FALSE),array("danger",e("Something went wrong, please try again.")));
                }
                if($this->db->update("url",array("bundle"=>"?"),array("id"=>"?","userid"=>"?"),array(Main::clean($_POST["bundle_id"],3,TRUE),Main::clean($_POST["url_id"],3,TRUE),$this->user->id))){
                    Main::redirect(Main::href("user/bundles","",FALSE),array("success",e("This URL has been added to the bundle.")));
                    return;
                }
            }
            return Main::redirect(Main::href("user/bundles","",FALSE));
        }

        $this->filter($this->id);
        $bundles = $this->db->get("bundle",array("userid"=>"?"),array("order"=>"date","count"=>TRUE,"limit"=>($this->page-1)*$this->limit.", {$this->limit}"),array($this->user->id));

        if(($this->db->rowCount%$this->limit)<>0) {
            $max=floor($this->db->rowCount/$this->limit)+1;
        } else {
            $max=floor($this->db->rowCount/$this->limit);
        }

        if($this->page > 1 && $this->page > $max) Main::redirect("user/bundles",array("danger",e("No URLs found.")));
        $pagination = Main::pagination($max,$this->page,Main::href("user/bundles?page=%d"));
        // Show Template
        $this->isUser=TRUE;
        Main::set("title",e("Manage your bundles"));
        Main::set("description","Manage your bundles, share them or delete them.");
        $this->header();
        include($this->t(__FUNCTION__));
        $this->footer();
    }
    /**
     * Delete URL
     * @since 5.6
     **/
    private function delete(){

        // Mass Delete URLs
        if(isset($_POST["token"]) && isset($_POST["delete-id"]) && is_array($_POST["delete-id"])){

            if($this->isTeam() && !$this->teamPermission("links.delete")){
                return Main::redirect("user",array("danger",e("You do not have this permission. Please contact your team administrator.")));
            }
            // Validate Token
            if(!Main::validate_csrf_token($_POST["token"])){
                return Main::redirect(Main::href("user","",FALSE),array("danger",e("Invalid token. Please try again.")));
            }
            $query="(";
            $query2="(";
            $c=count($_POST["delete-id"]);
            $p = [];
            $i = 1;
            foreach ($_POST["delete-id"] as $id) {
                if($i>=$c){
                    $query.="(`alias` = :id$i OR `custom`= :id$i)";
                    $query2.="`short` = :id$i";
                }else{
                    $query.="(`alias` = :id$i OR `custom`= :id$i) OR ";
                    $query2.="`short` = :id$i OR ";
                }

                $p[':id'.$i]=$id;
                $i++;
            }
            $p[":user"]=$this->user->id;
            $query.=") AND userid=:user";
            $query2.=") AND urluserid=:user";
            $this->db->delete("url", $query, $p);
            $this->db->delete("stats", $query2, $p);
            return Main::redirect(Main::href("user","",FALSE),array("success",e("Selected URLs have been deleted.")));
        }
        // Delete single URL
        if(!empty($this->id) && is_numeric($this->id)){
            // Validated Nonce
            if(Main::validate_nonce("delete_url-{$this->id}")){

                if($this->isTeam() && !$this->teamPermission("links.delete")){
                    return Main::redirect("user",array("danger",e("You do not have this permission. Please contact your team administrator.")));
                }

                $url=$this->db->get("url",array("id"=>"?","userid"=>"?"),array("limit"=>1),array($this->id,$this->user->id));
                $this->db->delete("url",array("id"=>"?","userid"=>"?"),array($this->id,$this->user->id));
                $this->tmail_delete(strtolower($url->alias));
                $this->db->delete("stats",array("short"=>"?"),array($url->alias.$url->custom));
                return Main::redirect(Main::href("user","",FALSE),array("success",e("URL has been deleted.")));
            }
            // Validated Nonce
            if(Main::validate_nonce("delete_bundle-{$this->id}")){
                if($this->isTeam() && !$this->teamPermission("bundle.delete")){
                    return Main::redirect("user",array("danger",e("You do not have this permission. Please contact your team administrator.")));
                }
                $this->db->update("url",array("bundle"=>"?"),array("userid"=>"?","bundle"=>"?"),array(NULL,$this->user->id,$this->id));
                $this->db->delete("bundle",array("userid"=>"?","id"=>"?"),array($this->user->id,$this->id));
                return Main::redirect(Main::href("user/bundles","",FALSE),array("success",e("This bundle has been deleted.")));
            }
            // Validated Nonce
            if(Main::validate_nonce("delete_splash-{$this->id}")){
                if($this->isTeam() && !$this->teamPermission("splash.delete")){
                    return Main::redirect("user",array("danger",e("You do not have this permission. Please contact your team administrator.")));
                }
                return $this->splash_delete();
            }
        }
        return Main::redirect(Main::href("user","",FALSE),array("danger",e("Security token expired. Please try again.")));
    }
    /**
     * Edit URLs
     * @since 5.6.5
     **/
    private function edit(){
        // Edit URL
        if(isset($_POST["token"])){
            if(!Main::validate_csrf_token($_POST["token"])) {
                Main::redirect(Main::href("user/edit/{$this->id}","",FALSE),array("danger",e("Something went wrong, please try again.")));
                return;
            }
            if($this->config["demo"]){
                Main::redirect("user/settings",array("danger",e("Feature disabled in demo.")));
                return;
            }

            if($this->isTeam() && !$this->teamPermission("links.edit")){
                return Main::redirect("user",array("danger",e("You do not have this permission. Please contact your team administrator.")));
            }

            if($this->pro()){
                if(empty($_POST["url"]) || !Main::is_url($_POST["url"])) {
                    return Main::redirect(Main::href("user/edit/{$this->id}","",FALSE),array("danger",e("Please enter a valid URL.")));
                }else{
                    // Check if URL safe or Phish
                    include("includes/Short.class.php");
                    $url = new Short($this->db,$this->config);
                    // Check with Google
                    if(!$url->safe($_POST["url"])){
                        return Main::redirect(Main::href("user/edit/{$this->id}","",FALSE),array("danger",e("URL is suspected to contain malwares and other harmful content.")));
                    }
                    // Check with Phish
                    if($url->phish($_POST["url"])){
                        return Main::redirect(Main::href("user/edit/{$this->id}","",FALSE),array("danger",e("URL is suspected to contain malwares and other harmful content.")));
                    }
                }
            }

            if(!empty($_POST['location'][0]) && !empty($_POST['target'][0])){
                foreach ($_POST['location'] as $i => $country) {
                    if(!empty($country) && !empty($_POST['target'][$i]) && Main::is_url($_POST['target'][$i])){
                        $countries[strtolower(Main::clean($country,3,TRUE))] = Main::clean($_POST['target'][$i], 3, TRUE);
                    }
                }
                $countries=json_encode($countries);
            }else{
                $countries='';
            }

            // Generate formatted list of devices
            if(!empty($_POST['device'][0]) && !empty($_POST['dtarget'][0])){
                foreach ($_POST['device'] as $i => $device) {
                    if(!empty($device) && !empty($_POST['dtarget'][$i]) && Main::is_url($_POST['dtarget'][$i])){
                        $devices[strtolower(Main::clean($device))] = Main::clean($_POST['dtarget'][$i],3,FALSE);
                    }
                }
                $devices=json_encode($devices);
            }else{
                $devices='';
            }


            // Generate formatted list of devices
            if(!empty($_POST['paramname'][0]) && !empty($_POST['paramvalue'][0])){
                foreach ($_POST['paramname'] as $i => $parameter) {
                    if(!empty($parameter) && !empty($_POST['paramvalue'][$i])){
                        $parameters[$parameter] = Main::clean($_POST['paramvalue'][$i],3,FALSE);
                    }
                }
                $parameters = json_encode($parameters);
            }else{
                $parameters = '';
            }

            $data = array(
                ":location" => $countries,
                ":devices" => $devices,
                ":parameters" => $parameters,
                ":pass" => Main::clean($_POST["pass"],3,TRUE),
                ":description"=> Main::clean($_POST["description"],3,TRUE),
                ":meta_description"=> Main::clean($_POST["meta_description"],3,TRUE),
                ":meta_title"=> Main::clean($_POST["meta_title"],3,TRUE),
                ":public" => in_array($_POST["public"], array("0","1")) ? Main::clean($_POST["public"]) : 0,
                ":domain" => (isset($_POST["domain"]) && !empty($_POST["domain"]) && $this->validate_domain_names($_POST["domain"])) ? Main::clean($_POST["domain"],TRUE,3) : ""
            );
            // Pro users only
            if($this->pro()){
                $data[":url"] = Main::clean($_POST["url"],3,TRUE);
                //Edit URL
                if(in_array($_POST["type"], array("direct","frame","splash")) || is_numeric($_POST["type"])){
                    $data[":type"]=Main::clean($_POST["type"],3,TRUE);
                }
                if(preg_match("~overlay-(.*)~", $_POST["type"])){
                    $data[":type"] = $_POST["type"];
                }
                $pixels = "";
                if(isset($_POST["pixels"]) && is_array($_POST["pixels"])){
                    $data[":pixels"] = implode(",", $_POST["pixels"]);
                }

                if(!empty($_POST["domain"]) && $this->db->get("domains", ["domain" => "?", "userid" => "?"], ["limit" => "1"], [$_POST["domain"], $this->user->id])){
                    $data[":domain"] = Main::clean($_POST["domain"],TRUE,3);
                }
            }
            if($this->db->update("url","",array("id"=>$this->id, "userid"=>$this->user->id),$data)){
                return Main::redirect(Main::href("user/edit/{$this->id}","",FALSE),array("success",e("This URL has been successfully updated.")));
            }
            return Main::redirect(Main::href("user/edit/{$this->id}","",FALSE));
        }
        if(empty($this->id) || !is_numeric($this->id)) return Main::redirect("user",array("danger",e("This URL doesn't exist.")));
        if(!$url = $this->db->get("url",array("id"=>"?", "userid" => "?"),array("limit"=>1),array($this->id, $this->user->id))) return Main::redirect("user",array("danger",e("This URL doesn't exist.")));

        if($this->config["multiple_domains"]){
            $domain_list='<div class="form-group"><label class="col-sm-3 control-label">'.e("Domain Name").'</label><div class="col-sm-9"><select name="domain" id="domains">';
            $domain_list.='<optgroup label="'.e('Custom Domain').'" />';

            $userdomains = $this->db->get("domains", ["userid" => $this->user->id]);

            if($userdomains){
                foreach ($userdomains as $userdomain) {
                    $domain_list .= '<option value="'.strtolower($userdomain->domain).'" '.(($url->domain  == strtolower($userdomain->domain))?'selected':'').'>'.ucfirst(str_replace("https://","",str_replace("http://", "",$userdomain->domain))).'</option>';
                }
            }
            $domain_list.='<optgroup label="'.e('Choose Domain').'" />';
            $domains=explode("\n", $this->config["domain_names"]);
            if($this->config["root_domain"]){
                $domain_list.='<option value="" '.(($url->domain == $this->config["url"] || empty($url->domain) || !$url->domain)?'selected':'').'>'.ucfirst(str_replace("https://","",str_replace("http://", "",$this->config["url"]))).'</option>';
            }


            foreach ($domains as $domain) {
                $domain_list.='<option value="'.strtolower(rtrim($domain)).'" '.($url->domain == strtolower(rtrim($domain))?'selected':'').'>'.ucfirst(str_replace("https://","",str_replace("http://", "", trim($domain)))).'</option>';
            }
            $domain_list.='</select></div></div> ';
        }else{
            $domain_list="";
        }

        $before="<div class='form-group country hide' style='display:none'>
                <div class='col-sm-6'>
                  <label>Country</label>
                    <select name='location[]'>
                      ".Main::countries()."
                    </select>
                </div>
                <div class='col-sm-6'>
                <label>URL</label>
                  <input type='text' class='form-control' name='target[]' id='meta_description' value=''>                          
                </div>
              </div>
              <div class='form-group devices hide' style='display:none'>
                  <div class='col-sm-6'>
                    	<label>".e("Device")."</label>
                      <select name='device[]'>
                        ".Main::devices()."
                      </select>
                  </div>
                  <div class='col-sm-6'>
                  <label>".e("Long URL")."</label>
                    <input type='text' class='form-control' name='dtarget[]' id='target_url' value=''>                          
                  </div>
                </div>
               <div class='form-group parameters hide' style='display:none'>
                  <div class='col-sm-6'>
                    <label>".e("Parameter Name")."</label>
                    <input type='text' class='form-control' name='paramname[]' value=''>
                  </div>
                  <div class='col-sm-6'>
                  	<label>".e("Parameter value")."</label>
                    <input type='text' class='form-control' name='paramvalue[]' value=''>                          
                  </div>   
                </div>";

        $header=e("Edit URL");
        $content="      
	    <form action='".Main::href("user/edit/{$url->id}")."' method='post' class='form-horizontal' role='form'>
	      <div class='form-group'>
	        <label for='url' class='col-sm-3 control-label'>".e("Long URL")."</label>
	        <div class='col-sm-9'>
	          <input type='url' class='form-control' name='url' id='url' value='{$url->url}' ".(!$this->pro() ? "disabled":"").">
	          <p class='help-block'>Please note that only pro users can edit URLs once they are shortened.</p>
	        </div>
	      </div>  

	      <div class='form-group'>
	        <label for='alias' class='col-sm-3 control-label'>Alias</label>
	        <div class='col-sm-9'>
	          <input type='text' class='form-control' name='alias' id='alias' value='{$url->alias}' disabled>
	          <p class='help-block'>The short alias cannot be changed.</p>
	        </div>
	      </div>  

	      <div class='form-group'>
	        <label for='custom' class='col-sm-3 control-label'>Custom</label>
	        <div class='col-sm-9'>
	          <input type='text' class='form-control' id='custom' value='{$url->custom}' disabled>
	          <p class='help-block'>The custom alias cannot be changed.</p>
	        </div>
	      </div> 

	      <div class='form-group'>
	        <label for='pass' class='col-sm-3 control-label'>".e("Password")."</label>
	        <div class='col-sm-9'>
	          <input type='text' class='form-control' name='pass' id='pass' value='{$url->pass}'>
	          <div class='help-block'>".e("Note that the password might be encrypted. To update this simply enter the password again.")."</div>
	        </div>
	      </div>

	      <div class='form-group'>
	        <label for='description' class='col-sm-3 control-label'>".e("Note")." (".e("optional").")</label>
	        <div class='col-sm-9'>
	          <input type='text' class='form-control' name='description' id='description' value='{$url->description}'>
	        </div>
	      </div>
	      $domain_list";
        if($this->pro()){
            $splash = $this->db->get("splash",array("userid"=>"?"),array("order"=>"date"),array($this->user->id));
            $overlay = $this->db->get("overlay",array("userid"=>"?"),array("order"=>"date"),array($this->user->id));
            $content.="<hr><div class='form-group'>
	        <label for='description' class='col-sm-3 control-label'>".e("Redirection")."</label>
	        <div class='col-sm-9'>
			      <select name='type'>
			      	<optgroup label='".e("Redirection")."'>
			        <option value='direct'".($url->type=="direct" || $url->type=="" ?" selected":"").">".e('Direct')."</option>
			        <option value='frame'".($url->type=="frame"?" selected":"").">".e('Frame')."</option>
			        <option value='splash'".($url->type=="splash"?" selected":"").">".e('Splash')."</option>";
            if($splash){
                $content.='<optgroup label="'.e('Custom Splash').'">';
                foreach ($splash as $type) {
                    $content.='<option value="'.$type->id.'"'.($url->type==$type->id?" selected":"").'>'.ucfirst($type->name).'</option>"';
                }
                $content.="</optgroup>";
            }
            if($overlay){
                $content.='<optgroup label="'.e('Custom Overlay').'">';
                foreach ($overlay as $type) {
                    $content.='<option value="overlay-'.$type->id.'" '.($url->type=="overlay-".$type->id?" selected":"").'>'.ucfirst($type->name).'</option>"';
                }
                $content.="</optgroup>";
            }
            $content.="</select>	          
			        </div>
			      </div>	      
				    <div class='form-group'>
			        <label for='meta_title' class='col-sm-3 control-label'>".e("Meta Title")."</label>
			        <div class='col-sm-9'>
			          <input type='text' class='form-control' name='meta_title' id='meta_title' value='{$url->meta_title}'>
			        </div>
			      </div>
			      <div class='form-group'>
			        <label for='meta_description' class='col-sm-3 control-label'>".e("Meta Description")."</label>
			        <div class='col-sm-9'>
			          <textarea class='form-control' name='meta_description' id='meta_description'>{$url->meta_description}</textarea>
			        </div>
			      </div>";
        }
        $content.="<hr>";
        if($this->permission("geo") !== FALSE){
            $content .="
		      <h4>".e("Geotargeting Data")." <a href='#' class='btn btn-primary btn-xs pull-right add_geo'>".e("Add a Field")."</a></h4>
		      <div id='geo'>";
            if(!empty($url->location)){
                $geo=json_decode($url->location);
                foreach ($geo as $country => $link){
                    $content.="<div class='form-group'>
	                      <div class='col-sm-6'>
	                        <label>".e("Country")."</label>
	                          <select name='location[]'>
	                            ".Main::countries($country)."
	                          </select>
	                      </div>
	                      <div class='col-sm-6'>
	                      <label>".e("Long URL")."</label>
	                        <input type='text' class='form-control' name='target[]' id='meta_description' value='$link'>                          
	                      </div>
	                    </div><p><a href='#' class='btn btn-danger btn-xs delete_geo' data-holder='div.form-group'>".e("Delete")."</a></p>";
                }
            }
            $content.="<div class='form-group'>
                  <div class='col-sm-6'>
                    <label>".e("Country")."</label>
                      <select name='location[]'>
                        ".Main::countries()."
                      </select>
                  </div>
                  <div class='col-sm-6'>
                  <label>".e("Long URL")."</label>
                    <input type='text' class='form-control' name='target[]' id='target_url' value=''>                          
                  </div>
                </div>";
            $content.="</div><hr>";
        }
        if($this->permission("device") !== FALSE){
            $content.="<hr>
		      <h4>".e("Device Data")." <a href='#' class='btn btn-primary btn-xs pull-right add_device'>".e("Add a Field")."</a></h4>
		      <div id='device'>";
            if(!empty($url->devices)){
                $devices = json_decode($url->devices);
                foreach ($devices as $device => $link){
                    $content.="<div class='form-group'>
	                      <div class='col-sm-6'>
	                        <label>".e("Device")."</label>
	                          <select name='device[]'>
	                            ".Main::devices($device)."
	                          </select>
	                      </div>
	                      <div class='col-sm-6'>
	                      <label>".e("Long URL")."</label>
	                        <input type='text' class='form-control' name='dtarget[]' id='meta_description' value='$link'>                          
	                      </div>
	                    </div><p><a href='#' class='btn btn-danger btn-xs delete_geo' data-holder='div.form-group'>".e("Delete")."</a></p>";
                }
            }
            $content.="<div class='form-group'>
                  <div class='col-sm-6'>
                    	<label>".e("Device")."</label>
                      <select name='device[]'>
                        ".Main::devices()."
                      </select>
                  </div>
                  <div class='col-sm-6'>
                  <label>".e("Long URL")."</label>
                    <input type='text' class='form-control' name='dtarget[]' id='target_url' value=''>                          
                  </div>
                </div>";
            $content.="</div><hr>";
        }

        if($this->permission("pixels") !== FALSE){

            $activePixels = explode(",", $url->pixels);

            $content.="<h4>".e("Targeting Pixels")."</h4>
						     <div id='pixels'>";

            $content .="<div class='row devices'>
              <div class='col-md-12'>
                <div class='input-group'>
                  <span class='input-group-addon'><i class='glyphicon glyphicon-filter'></i></span>
                  <select name='pixels[]' data-placeholder='Your Pixels' multiple>";
            if($fbpixel = json_decode($this->user->fbpixel)){
                $content .= "<optgroup label='Facebook'>";
                foreach ($fbpixel as $key => $ad){
                    $content .= "<option value='fbpixel-{$key}' ".(in_array("fbpixel-{$key}", $activePixels) ? "selected": "").">{$ad->name}</option>";
                }
                $content .= "</optgroup>";
            }
            if($adwordspixel = json_decode($this->user->adwordspixel)){
                $content .=' <optgroup label="Adwords">';
                foreach ($adwordspixel as $key => $ad){
                    $content .= "<option value='adwordspixel-{$key}' ".(in_array("adwordspixel-{$key}", $activePixels) ? "selected": "").">{$ad->name}</option>";
                }
                $content .= "</optgroup>";
            }
            if($linkedinpixel = json_decode($this->user->linkedinpixel)){
                $content .=' <optgroup label="LinkedIn">';
                foreach ($linkedinpixel as $key => $ad){
                    $content .= "<option value='linkedinpixel-{$key}' ".(in_array("linkedinpixel-{$key}", $activePixels) ? "selected": "").">{$ad->name}</option>";
                }
                $content .= "</optgroup>";
            }
            if($twitterpixel = json_decode($this->user->twitterpixel)){
                $content .=' <optgroup label="Twitter">';
                foreach ($twitterpixel as $key => $ad){
                    $content .= "<option value='twitterpixel-{$key}' ".(in_array("twitterpixel-{$key}", $activePixels) ? "selected": "").">{$ad->name}</option>";
                }
                $content .= "</optgroup>";
            }
            if($adrollpixel = json_decode($this->user->adrollpixel)){
                $content .=' <optgroup label="AdRoll">';
                foreach ($adrollpixel as $key => $ad){
                    $content .= "<option value='adrollpixel-{$key}' ".(in_array("adrollpixel-{$key}", $activePixels) ? "selected": "").">{$ad->name}</option>";
                }
                $content .= "</optgroup>";
            }
            $content .="</select>
                </div>              
              </div>
            </div>";
            $content .= "</div><hr>";
        }

        if($this->permission("parameters") !== FALSE){
            $content.="<hr>
		      <h4>".e("Parameter Builder")." <a href='#' class='btn btn-primary btn-xs pull-right add_parameter'>".e("Add parameters")."</a></h4>
		      <div id='parameters'>";
            if(!empty($url->parameters)){
                $parameters = json_decode($url->parameters, TRUE);
                foreach ($parameters as $parameter => $value){
                    $content.="<div class='form-group'>
	                      <div class='col-sm-6'>
	                        <label>".e("Parameter Name")."</label>
                          <input type='text' class='form-control' name='paramname[]' value='$parameter'>
	                      </div>
	                      <div class='col-sm-6'>
	                      	<label>".e("Parameter value")."</label>
	                        <input type='text' class='form-control' name='paramvalue[]' value='$value'>                          
	                      </div>
	                    </div><p><a href='#' class='btn btn-danger btn-xs delete_geo' data-holder='div.form-group'>".e("Delete")."</a></p>";
                }
            }
            $content.="<div class='form-group'>
                  <div class='col-sm-6'>
                    <label>".e("Parameter Name")."</label>
                    <input type='text' class='form-control' name='paramname[]' value=''>
                  </div>
                  <div class='col-sm-6'>
                  	<label>".e("Parameter value")."</label>
                    <input type='text' class='form-control' name='paramvalue[]' value=''>                          
                  </div>   
                </div>";
            $content.="</div><hr>";
        }

        $url->domain = (!empty($url->domain) ? $url->domain : $this->config["url"]);
        $content.="
	    <ul class='form_opt' data-id='public'>
	      <li class='text-label'>".e("URL Access")." <small>".e("If you set it to private, only you can access the URLs").".</small></li>
	      <li><a href='' class='last".(!$url->public?' current':'')."' data-value='0'>".e("Private")."</a></li>
	      <li><a href='' class='first".($url->public?' current':'')."' data-value='1'>".e("Public")."</a></li>
	    </ul>
	    <input type='hidden' name='public' id='public' value='".$url->public."' />             
	    ".Main::csrf_token(TRUE)."
	    <input type='submit' value='".e("Update")."' class='btn btn-primary' />
	    <a href='".Main::href("{$url->alias}{$url->custom}+")."' class='btn btn-success' target='_blank'>".e("Stats")."</a>
	    <a href='".Main::href("user/delete/{$url->id}").Main::nonce("delete_url-{$url->id}")."' class='btn btn-danger delete pull-right'>".e("Delete")."</a>";
        // Add widget

        $widgets = $this->widget_countries(array("urlid"=>$url->alias.$url->custom));
        $widgets.='<div class="panel panel-default panel-body" id="'.__FUNCTION__.'">';
        $widgets.='<h3>'.e("URL Info").'</h3>';
        $widgets.='<p><em>'.$url->click.'</em> '.e("Clicks").' '.e("since").' '.date("F, d Y",strtotime($url->date)).'</p>';
        $widgets.="<p><i class='glyphicon glyphicon-link'></i> {$url->domain}/{$url->alias}{$url->custom} <a href='#' class='inline-copy copy' data-clipboard-text='{$url->domain}/{$url->alias}{$url->custom}'>".e("Copy")."</a></p>";
        $widgets.="<p><i class='glyphicon glyphicon-qrcode'></i> {$url->domain}/{$url->alias}{$url->custom}/qr <a href='#' class='inline-copy copy' data-clipboard-text='{$url->domain}/{$url->alias}{$url->custom}/qr'>".e("Copy")."</a></p>";
        $widgets.="<p>".e("To change the dimension of the QR code, add the size parameter to the url and then your desired dimension. (Max 500x500)")." e.g. ?size=300x300</p>";
        $widgets.='</div>';
        $widgets.= $this->widgets("export",$url->id);

        // Show Template
        $this->isUser=TRUE;
        Main::set("title",e("Edit URL"));
        $this->header();
        include($this->t("shared/user_template"));
        $this->footer();
    }
    /**
     * Splash Pages
     * @since 5.6
     **/
    private function splash(){
        if($this->permission("splash") === FALSE) return Main::redirect("upgrade",array("warning",e("Please choose a premium package to unlock this feature.")));

        // Create page
        if($this->id=="create") return $this->splash_create();
        // Edit
        if(is_numeric($this->id)) return $this->splash_edit();

        $splashs = $this->db->get("splash",array("userid"=>"?"),array("order"=>"date"),array($this->user->id));
        Main::set("title",e("Create a Custom Splash Page"));
        Main::set("description","Customize the splash page to attract more customers to your product or site.");

        $before="";
        if($splashs){
            $content='<ul class="list-group bundles">';
            foreach ($splashs as $splash) {
                $content.='<li class="list-group-item">';
                $content.= "<h4>{$splash->name}</h4>";
                $content.= "<p class='list-group-item-text'>
														".(!$this->isTeam() || ($this->isTeam() && $this->teamPermission("splash.edit")) ? "<a href='".Main::href("user/splash/{$splash->id}")."'>".e("Edit")."</a>&nbsp;&nbsp;&bullet;&nbsp;&nbsp; " : "")."
														".(!$this->isTeam() || ($this->isTeam() && $this->teamPermission("splash.delete")) ? "<a href='".Main::href("user/delete/{$splash->id}").Main::nonce("delete_splash-{$splash->id}")."' class='delete'>".e("Delete")."</a>&nbsp;&nbsp;&bullet;&nbsp;&nbsp; " : "")."
														".Main::timeago($splash->date)."
											    </p>";
                $content.='</li>';
            }
            $content.='</ul>';
        }else{
            $content = "<p class='center'>".e("You don't have any active splash pages.")."</p>";
        }

        $header = e("Create a Custom Splash Page");

        if(!$this->isTeam() || ($this->isTeam() && $this->teamPermission("splash.create"))){
            $header .= "<a href='".Main::href("user/splash/create")."' class='btn btn-primary btn-xs pull-right'>".e("Create")."</a>";
        }
        $widgets='<div class="panel panel-default panel-body">';
        $widgets.='<h3>'.e("Info").'</h3>';
        $count = $this->permission("splash");

        if($count == "0"){
            $widgets.="<p>".e("A custom splash page is a transitional page where you can add a banner and an avatar along with a message to represent your brand or company. You can have unlimited splash pages and you can choose one for each URL.")."</p>";
        }else{
            $widgets.="<p>".e("A custom splash page is a transitional page where you can add a banner and an avatar along with a message to represent your brand or company. You can have up to a maximum of $count splash pages and you can choose one for each URL.")."</p>";
            if($this->pro() && $count > 0){
                $p = $this->db->count("splash","userid='{$this->user->id}'") / $count*100;
                $widgets.='<br><div class="progress side-stats">
									  <div class="progress-bar'.($p >= 80 ?' progress-bar-danger':'').'" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" style="width: '.$p.'%;">
									  </div>
									</div>';
            }
        }

        $widgets.='</div>';

        $this->isUser=TRUE;
        $this->header();
        include($this->t("shared/user_template"));
        $this->footer();
    }
    /**
     * Create Splash Page
     * @since 5.6
     **/
    private function splash_create(){

        if($this->isTeam() && !$this->teamPermission("splash.create")){
            return Main::redirect("user",array("danger",e("You do not have this permission. Please contact your team administrator.")));
        }

        // Check if max limit is reached
        $count = $this->permission("splash");

        if($count > 0 && $this->db->count("splash","userid='{$this->user->id}'") >= $count) return Main::redirect(Main::href("user/splash","",FALSE),array("danger",e("You have reached your max limit.")));

        // Upload splash page
        if(isset($_POST["token"])){
            if(!Main::validate_csrf_token($_POST["token"])){
                Main::redirect(Main::href("user/splash/create","",FALSE),array("danger",e("Something went wrong, please try again.")));
                return;
            }
            if($this->config["demo"]){
                Main::redirect("user/settings",array("danger",e("Feature disabled in demo.")));
                return;
            }

            $upload_path=ROOT."/content/";
            $ext=array("image/png"=>"png","image/jpeg"=>"jpg","image/jpg"=>"jpg");

            $_POST["title"]		=	Main::clean($_POST["title"],3,TRUE);
            $_POST["message"]	=	Main::clean($_POST["message"],3,TRUE);
            $_POST["product"]	=	Main::clean($_POST["product"],3,TRUE);

            if(empty($_POST["name"])) return Main::redirect("user/splash/create",array("danger",e("Please enter unique name.")));

            if(!filter_var($_POST["product"],FILTER_VALIDATE_URL) || !Main::is_url($_POST["product"])) return Main::redirect("user/splash/create",array("danger",e("Please enter a valid URL.")));
            if(strlen($_POST["message"])>140) return Main::redirect("user/splash/create",array("danger",e("Message is too long. It must be less than 140 characters.")));

            if(!isset($ext[$_FILES["avatar"]["type"]])) return Main::redirect("user/splash/create",array("danger",e("Avatar must be either a PNG or a JPEG.")));
            if(!isset($ext[$_FILES["banner"]["type"]])) return Main::redirect("user/splash/create",array("danger",e("Banner must be either a PNG or a JPEG.")));

            if($_FILES["avatar"]["size"]>300*1024) return Main::redirect("user/splash/create",array("danger",e("Avatar must be either a 100x100 PNG or a JPEG (Max 300KB).")));
            if($_FILES["banner"]["size"]>500*1024) return Main::redirect("user/splash/create",array("danger",e("Banner must be either a 980x300 PNG or a JPEG (Max 500KB).")));

            list($width, $height) = getimagesize($_FILES["avatar"]["tmp_name"]);
            if(($width < 500 && $height < 500) || ($width/$height) != 1)	return Main::redirect("user/splash/create",array("danger",e("Avatar must be either a 100x100 PNG or a JPEG (Max 300KB).")));

            list($width, $height) = getimagesize($_FILES["banner"]["tmp_name"]);
            if($width < 980 || ($height < 250 || $height > 900))	return Main::redirect("user/splash/create",array("danger",e("Banner must be either a 980x300 PNG or a JPEG (Max 500KB).")));

            $unique=Main::strrand(8);
            $avatar=$unique."_avatar.".$ext[$_FILES["avatar"]["type"]];
            $banner=$unique."_banner.".$ext[$_FILES["banner"]["type"]];
            move_uploaded_file($_FILES["avatar"]['tmp_name'], $upload_path.$avatar);
            move_uploaded_file($_FILES["banner"]['tmp_name'], $upload_path.$banner);

            $array=array(
                "title" => Main::truncate($_POST["title"],50),
                "message" => Main::truncate($_POST["message"],140),
                "banner"=> $unique."_banner.".$ext[$_FILES["banner"]["type"]],
                "avatar"=> $unique."_avatar.".$ext[$_FILES["avatar"]["type"]],
                "product" => $_POST["product"]
            );
            $array=json_encode($array);
            $data = array(
                ":userid" => $this->user->id,
                ":name" => Main::clean($_POST["name"],3,TRUE),
                ":data" => $array,
                ":date" => "NOW()"
            );
            if($this->db->insert("splash",$data)){
                return Main::redirect(Main::href("user/splash","",FALSE),array("success",e("Splash page has been created.")));
            }
            return Main::redirect(Main::href("user/splash","",FALSE),array("danger",e("Security token expired. Please try again.")));
        }
        Main::set("title",e("Create a Custom Splash Page"));
        Main::set("description","Customize the splash page to attract more customers to your product or site.");
        $before="";
        $header=e("Create your splash page");
        $widgets='<div class="panel panel-default panel-body" id="'.__FUNCTION__.'">';
        $widgets.='<h3>'.e("Info").'</h3>';
        $widgets.="<p>".e("A custom splash page is a transitional page where you can add a banner and an avatar along with a message to represent your brand or company. You can have up to a maximum of 5 splash pages and you can choose one for each URL.")."</p>";
        $widgets.='</div>';
        // Upload form
        $content="
					<form action='".Main::href("user/splash/create")."' class='form' method='post' enctype='multipart/form-data'>
						<div class='form-group'>
							<label for='name'>".e('Unique name')."</label>
							<input type='text' class='form-control' name='name' id='name'  placeholder='e.g. ".e("My Brand")."'>
						</div>						
						<div class='form-group'>
							<label for='product'>".e('Link to Product')."</label>
							<input type='text' class='form-control' name='product' id='product'  placeholder='e.g. http://domain.com/'>
						</div>
						<div class='form-group'>
							<label for='avatar'>".e('Upload Avatar')." (100x100, PNG or JPEG, MAX 300KB)</label>
							<input type='file' class='form-control' name='avatar' id='avatar'  placeholder='e.g. http://domain.com/avatar.jpg'>
						</div>
						<div class='form-group'>
							<label for='banner'>".e('Upload Banner')."</label>
							<input type='file' class='form-control' name='banner' id='banner' placeholder='e.g. http://domain.com/banner.jpg'>
							<div class='help-block'>".e("The minimum width must be 980px and the height must be between 250 and 500. The format must be a PNG or a JPG. Maximum size is 500KB.")."</div>
						</div>
						<div class='form-group'>
							<label for='title'>".e('Custom Title')."</label>
							<input type='text' class='form-control' name='title' id='title' placeholder='e.g. ".e("Get a $10 discount")."'>
						</div>
						<div class='form-group'>
							<label for='message'>".e('Custom Message')." (Max: 140 chars)</label>
							<textarea name='message' id='message' cols='30' rows='5' class='form-control' placeholder='e.g. ".e("Get a $10 discount with any purchase more than $50")."'></textarea>
						</div>
						".Main::csrf_token(TRUE)."	
						<button class='btn btn-primary'>".e('Create Splash Page')."</button>
					</form><!-- /.form -->";

        $this->isUser=TRUE;
        $this->header();
        include($this->t("shared/user_template"));
        $this->footer();
    }
    /**
     * Create Splash Page
     * @since 5.6
     **/
    private function splash_edit(){

        if($this->isTeam() && !$this->teamPermission("splash.edit")){
            return Main::redirect("user",array("danger",e("You do not have this permission. Please contact your team administrator.")));
        }

        if(!$splash = $this->db->get("splash",array("userid"=>"?","id"=>"?"),array("limit"=>1),array($this->user->id,$this->id))) return Main::redirect(Main::href("user/splash","",FALSE),array("danger",e("This splash page doesn't exist.")));
        $data = json_decode($splash->data);
        // Edit Splash
        if(isset($_POST["token"])){
            if($this->config["demo"]){
                Main::redirect("user/settings",array("danger",e("Feature disabled in demo.")));
                return;
            }
            if(!Main::validate_csrf_token($_POST["token"])){
                Main::redirect(Main::href("user/splash/{$this->id}","",FALSE),array("danger",e("Something went wrong, please try again.")));
                return;
            }

            $upload_path=ROOT."/content/";
            $ext=array("image/png"=>"png","image/jpeg"=>"jpg","image/jpg"=>"jpg");

            $_POST["title"]=Main::clean($_POST["title"],3,TRUE);
            $_POST["message"]=Main::clean($_POST["message"],3,TRUE);
            $_POST["product"]=Main::clean($_POST["product"],3,TRUE);

            if(empty($_POST["title"]) || empty($_POST["message"]) || empty($_POST["product"]) || empty($_POST["name"])) return Main::redirect(Main::href("user/splash/{$this->id}","",FALSE),array("danger",e("The name, title, message and link cannot be empty.")));
            if(!Main::is_url($_POST["product"])) return Main::redirect("user/splash/{$this->id}",array("danger",e("Please enter a valid URL.")));

            $array=array(
                "title" => Main::truncate($_POST["title"],50),
                "message" => Main::truncate($_POST["message"],140),
                "product" => $_POST["product"],
                "avatar" => $data->avatar,
                "banner" => $data->banner
            );
            $avatar = $banner = 0;
            // Valid avatar
            if(!empty($_FILES["avatar"]["name"])){
                if(!isset($ext[$_FILES["avatar"]["type"]])) return Main::redirect("user/splash/{$this->id}",array("danger",e("Avatar must be either a PNG or a JPEG.")));

                if($_FILES["avatar"]["size"]>300*1024) return Main::redirect("user/splash/{$this->id}",array("danger",e("Avatar must be either a 100x100 PNG or a JPEG (Max 300KB).")));

                list($width, $height) = getimagesize($_FILES["avatar"]["tmp_name"]);
                if($width!=100 && $height!=100)	return Main::redirect("user/splash/{$this->id}",array("danger",e("Avatar must be either a 100x100 PNG or a JPEG (Max 300KB).")));

                $name = explode(".", $data->avatar);
                $array["avatar"] = $name[0].".".$ext[$_FILES["banner"]["type"]];
                $avatar = 1;
            }
            // Valid Banner
            if(!empty($_FILES["banner"]["name"])){
                if(!isset($ext[$_FILES["banner"]["type"]])) return Main::redirect("user/splash/{$this->id}",array("danger",e("Banner must be either a PNG or a JPEG.")));

                if($_FILES["banner"]["size"]>500*1024) return Main::redirect("user/splash/{$this->id}",array("danger",e("Banner must be either a 980x300 PNG or a JPEG (Max 500KB).")));

                list($width, $height) = getimagesize($_FILES["banner"]["tmp_name"]);
                if($width < 980 || ($height<250 || $height >500))	return Main::redirect("user/splash/{$this->id}",array("danger",e("Banner must be either a 980x300 PNG or a JPEG (Max 500KB).")));

                $name = explode(".", $data->banner);
                $array["banner"] = $name[0].".".$ext[$_FILES["banner"]["type"]];
                $banner = 1;
            }
            if($avatar){
                move_uploaded_file($_FILES["avatar"]['tmp_name'], $upload_path.$array["avatar"]);
            }
            if($banner){
                move_uploaded_file($_FILES["banner"]['tmp_name'], $upload_path.$array["banner"]);
            }
            $array=json_encode($array);
            $data = array(
                ":userid" => $this->user->id,
                ":name" => Main::clean($_POST["name"],3,TRUE),
                ":data" => $array,
            );
            if($this->db->update("splash","",array("id"=>$this->id,"userid"=>$this->user->id),$data)){
                return Main::redirect(Main::href("user/splash/{$this->id}","",FALSE),array("success",e("Splash page has been updated.")));
            }
            return Main::redirect(Main::href("user/splash/{$this->id}","",FALSE),array("danger",e("Security token expired. Please try again.")));
        }
        Main::set("title",e("Edit Custom Splash Page"));

        $header=e("Edit splash page");
        $data->banner = Main::href("content/{$data->banner}");
        $data->avatar = Main::href("content/{$data->avatar}");

        $before="<div class='custom-splash panel panel-default' id='splash'>
									<div class='banner'><a href='{$data->product}' rel='nofollow' target='_blank'><img src='{$data->banner}'></a></div><!-- /.banner -->
									<div class='custom-message'>
										<div class='c-avatar'><img src='{$data->avatar}'></div><!-- /.avatar -->
										<div class='c-message'>
											<h2>{$data->title}</h2>
											{$data->message}
											<p><a href='{$data->product}' rel='nofollow' target='_blank' class='btn btn-primary btn-xs'>".e('View site')."</a></p>
										</div><!-- /.messsage -->
										<div class='c-countdown'><span>5</span>seconds</div><!-- /.c-countdown -->
									</div><!-- /.custom-message -->
								</div><!-- /.custom-splash -->";

        $widgets='<div class="panel panel-default panel-body" id="'.__FUNCTION__.'">';
        $widgets.='<h3>'.e("Info").'</h3>';
        $widgets.="<p>".e("A custom splash page is a transitional page where you can add a banner and an avatar along with a message to represent your brand or company. You can have up to a maximum of 5 splash pages and you can choose one for each URL.")."</p>";
        $widgets.='</div>';

        // Upload form
        $content="
					<form action='".Main::href("user/splash/{$this->id}")."' class='form' method='post' enctype='multipart/form-data'>
						<div class='form-group'>
							<label for='name'>".e('Unique name')."</label>
							<input type='text' class='form-control' name='name' id='name' value='{$splash->name}'>
						</div>						
						<div class='form-group'>
							<label for='product'>".e('Link to Product')."</label>
							<input type='text' class='form-control' name='product' id='product'  value='{$data->product}' placeholder='e.g. http://domain.com/'>
						</div>
						<div class='form-group'>
							<label for='avatar'>".e('Upload Avatar')." (100x100, PNG or JPEG, MAX 300KB)</label>
							<input type='file' class='form-control' name='avatar' id='avatar'  placeholder='e.g. http://domain.com/avatar.jpg'>
						</div>
						<div class='form-group'>
							<label for='banner'>".e('Upload Banner')."</label>
							<input type='file' class='form-control' name='banner' id='banner' placeholder='e.g. http://domain.com/banner.jpg'>
							<div class='help-block'>".e("The minimum width must be 980px and the height must be between 250 and 500. The format must be a PNG or a JPG. Maximum size is 500KB.")."</div>							
						</div>
						<div class='form-group'>
							<label for='title'>".e('Custom Title')."</label>
							<input type='text' class='form-control' name='title' id='title' value='{$data->title}'>
						</div>
						<div class='form-group'>
							<label for='message'>".e('Custom Message')." (Max: 140 chars)</label>
							<textarea name='message' id='message' cols='30' rows='5' class='form-control'>{$data->message}</textarea>
						</div>
						".Main::csrf_token(TRUE)."	
						<button class='btn btn-primary'>".e('Update Splash Page')."</button>
					</form><!-- /.form -->";

        $this->isUser=TRUE;
        $this->header();
        include($this->t("shared/user_template"));
        $this->footer();
    }
    /**
     * Delete Splash Page
     * @since 5.6
     **/
    private function splash_delete(){
        if($this->config["demo"]){
            Main::redirect("user/settings",array("danger",e("Feature disabled in demo.")));
            return;
        }
        if($this->isTeam() && !$this->teamPermission("delete")){
            return Main::redirect("user",array("danger",e("You do not have this permission. Please contact your team administrator.")));
        }
        // Delete single Splash
        if(!empty($this->id) && is_numeric($this->id)){
            // Validated Nonce
            if(Main::validate_nonce("delete_splash-{$this->id}")){
                $splash = $this->db->get("splash",array("id"=>"?","userid"=>"?"),array("limit"=>1),array($this->id,$this->user->id));
                if($splash){
                    $data=json_decode($splash->data);
                    if(is_file(ROOT."/content/{$data->avatar}") && is_file(ROOT."/content/{$data->banner}")){
                        unlink(ROOT."/content/{$data->avatar}");
                        unlink(ROOT."/content/{$data->banner}");
                    }
                    $this->db->update("url",array("type"=>"?"),array("userid"=>"?","type"=>"?"),array($splash->id,$this->user->id,$this->id));
                    $this->db->delete("splash",array("userid"=>"?","id"=>"?"),array($this->user->id,$this->id));
                    return Main::redirect(Main::href("user/splash","",FALSE),array("success",e("The splash page has been deleted.")));
                }
            }
        }
        return Main::redirect(Main::href("user/splash","",FALSE),array("danger",e("Security token expired. Please try again.")));
    }
    /**
     * [overlay description]
     * @author KBRmedia <https://gempixel.com>
     * @version 5.7
     * @return  [type] [description]
     */
    private function overlay(){
        if($this->permission("overlay") === FALSE) return Main::redirect("upgrade",array("warning",e("Please choose a premium package to unlock this feature.")));

        // Edit
        if(is_numeric($this->id)) return $this->overlay_edit();

        if(!empty($this->id) && preg_match("~delete-(.*)~", $this->id)) return $this->overlay_delete(str_replace("delete-", "", $this->id));

        // Create page
        if($this->id){
            $fn = "overlay_{$this->id}";
            if(method_exists(__CLASS__, $fn)) {
                return $this->{$fn}();
            }else{
                return $this->_404();
            }
        }

        $overlays = $this->db->get("overlay", array("userid"=>"?"), array("order"=>"date"), array($this->user->id));
        Main::set("title",e("Create a Custom Overlay Page"));
        Main::set("description","Customize the overlay page to attract more customers to your product or site.");

        $before="";
        if($overlays){
            $content='<ul class="list-group bundles">';
            foreach ($overlays as $overlay) {
                $content.='<li class="list-group-item">';
                $overlay->type = ucfirst($overlay->type);
                $content.= "<h4>{$overlay->name} <small>{$overlay->type}</small></h4>";
                $content.= "<p class='list-group-item-text'>
														".(!$this->isTeam() || ($this->isTeam() && $this->teamPermission("overlay.edit")) ? "<a href='".Main::href("user/overlay/{$overlay->id}")."'>".e("Edit")."</a> &nbsp;&nbsp;&bullet;&nbsp;&nbsp; " : "")."
														".(!$this->isTeam() || ($this->isTeam() && $this->teamPermission("overlay.delete")) ? "<a href='".Main::href("user/overlay/delete-{$overlay->id}").Main::nonce("delete_overlay-{$overlay->id}")."' class='delete'>".e("Delete")."</a> &nbsp;&nbsp;&bullet;&nbsp;&nbsp;" : "")."
														".Main::timeago($overlay->date)."
											    </p>";
                $content.='</li>';
            }
            $content.='</ul>';
        }else{
            $content = "<p class='center'>".e("You don't have any active overlay pages.")."</p>";
        }

        $header=e("Create a Custom Overlay Page");

        if(!$this->isTeam() || ($this->isTeam() && $this->teamPermission("overlay.create"))){
            $header .= "<a href='".Main::href("user/overlay/create")."' class='btn btn-primary btn-xs pull-right'>".e("Create")."</a>";
        }

        $widgets='<div class="panel panel-default panel-body" id="'.__FUNCTION__.'">';
        $widgets.='<h3>'.e("Info").'</h3>';
        $count = $this->permission("overlay");

        if($count == "0"){
            $widgets.="<p>".e("An overlay page allows you to display a small non-intrusive overlay on the destination website to advertise your product or your services. You can also use this feature to send a message to your users. You can customize the message and the appearance of the overlay right from this page. As soon as you save it, the changes will be applied immediately across all your URLs using this type. Please note that some secured and sensitive websites such as google.com or facebook.com do not work with this feature. You can have unlimited overlay pages and you can choose one for each URL.")."</p>";
        }else{
            $widgets.="<p>".e("An overlay page allows you to display a small non-intrusive overlay on the destination website to advertise your product or your services. You can also use this feature to send a message to your users. You can customize the message and the appearance of the overlay right from this page. As soon as you save it, the changes will be applied immediately across all your URLs using this type. Please note that some secured and sensitive websites such as google.com or facebook.com do not work with this feature.")."</p>";
            if($this->pro() && $count > 0){
                $p = $this->db->count("overlay","userid='{$this->user->id}'") / $count*100;
                $widgets.='<br><div class="progress side-stats">
									  <div class="progress-bar'.($p >= 80 ?' progress-bar-danger':'').'" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" style="width: '.$p.'%;">
									  </div>
									</div>';
            }
        }

        $widgets.='</div>';

        $this->isUser=TRUE;
        $this->header();
        include($this->t("shared/user_template"));
        $this->footer();
    }
    /**
     * Create Overlays
     * @author KBRmedia <https://gempixel.com>
     * @version 5.7
     * @return  [type] [description]
     */
    private function overlay_create(){
        if($this->isTeam() && !$this->teamPermission("overlay.create")){
            return Main::redirect("user",array("danger",e("You do not have this permission. Please contact your team administrator.")));
        }

        if($this->permission("overlay") === FALSE) return Main::redirect("upgrade",array("warning",e("Please choose a premium package to unlock this feature.")));

        Main::set("title",e("Create Overlay"));

        Main::set("description","Customize the overlay to attract more customers to your product or site.");

        $content="
				<div class='row'>
					<div class='col-sm-4'>
						<a href='".Main::href("user/overlay/message")."' class='overlay-type'>
							<i class='fa fa-comment-alt'></i>
							<span class='title'>".e("Message")."</span>
							<span class='description'>".e("Create a small popup with a message and a link to a page or a product.")."</span>
						</a>
					</div>
					<div class='col-sm-4'>
						<a href='".Main::href("user/overlay/contact")."' class='overlay-type'>
							<i class='fa fa-envelope-open'></i>
							<span class='title'>".e("Contact Form")."</span>
							<span class='description'>".e("Create a contact form where users will be able to contact you via email.")."</span>
						</a>
					</div>
					<div class='col-sm-4'>
						<a href='".Main::href("user/overlay/poll")."' class='overlay-type'>
							<i class='fa fa-poll'></i>
							<span class='title'>".e("Poll")."</span>
							<span class='description'>".e("Create a quick poll where users will be able to answer it upon visit.")."</span>
						</a>
					</div>
				</div>				
				";

        $header = e("Create Overlay");
        $before = "";
        $widgets='<div class="panel panel-default panel-body" id="'.__FUNCTION__.'">';
        $widgets.='<h3>'.e("Info").'</h3>';
        $widgets.="<p>".e("An overlay page allows you to display a small non-intrusive overlay on the destination website to advertise your product or your services. You can also use this feature to send a message to your users. You can customize the message and the appearance of the overlay right from this page. As soon as you save it, the changes will be applied immediately across all your URLs using this type. Please note that some secured and sensitive websites such as google.com or facebook.com do not work with this feature.")."</p>";
        $widgets.='</div>';
        $this->isUser=TRUE;
        $this->header();
        include($this->t("shared/user_template"));
        $this->footer();
    }
    /**
     * Create Message Overlay
     * @author KBRmedia <https://gempixel.com>
     * @version 5.7
     * @return  [type] [description]
     */
    private function overlay_message(){

        if($this->isTeam() && !$this->teamPermission("overlay.create")){
            return Main::redirect("user",array("danger",e("You do not have this permission. Please contact your team administrator.")));
        }

        if($this->permission("overlay") === FALSE) return Main::redirect("upgrade",array("warning",e("Please choose a premium package to unlock this feature.")));

        // Check if max limit is reached
        $count = $this->permission("overlay");

        if($count > 0 && $this->db->count("overlay","userid='{$this->user->id}'") >= $count) return Main::redirect(Main::href("user/overlay","",FALSE),array("danger",e("You have reached your max limit.")));

        // Edit Splash
        $message = e("Your text here");
        $label = e("Promo");
        $link = "";
        $text = e("Learn more");
        $bg = "#008aff";
        $color = "#fff";
        $btnbg = "#fff";
        $btncolor = "#000";
        $labelbg = "#fff";
        $labelcolor = "#000";
        $position = "bl";

        if(isset($_POST["token"])){
            if($this->config["demo"]){
                return Main::redirect("user/settings",array("danger",e("Feature disabled in demo.")));
            }
            if(!Main::validate_csrf_token($_POST["token"])){
                return Main::redirect(Main::href("user/overlay/message","",FALSE),array("danger",e("Something went wrong, please try again.")));
            }

            $_POST["message"]	=	Main::clean($_POST["message"],3,TRUE);

            if(empty($_POST["message"])) return Main::redirect(Main::href("user/overlay/message","",FALSE),array("danger",e("The message field cannot be empty.")));
            if(!empty($_POST["link"]) && !Main::is_url($_POST["link"])) return Main::redirect("user/overlay/message",array("danger",e("Please enter a valid URL.")));

            $upload_path=ROOT."/content/";
            $logo = "";
            $ext = ["image/png"=>"png","image/jpeg"=>"jpg","image/jpg"=>"jpg"];

            if(isset($_FILES["logo"]["type"]) && !empty($_FILES["logo"]["type"])){

                if(!isset($ext[$_FILES["logo"]["type"]])) return Main::redirect("user/overlay/message",array("danger",e("Avatar must be either a PNG or a JPEG.")));

                if($_FILES["logo"]["size"]>300*1024) return Main::redirect("user/overlay/message",array("danger",e("Avatar must be either a maximum size of 100x100 PNG or a JPEG (Max 300KB).")));

                list($width, $height) = getimagesize($_FILES["logo"]["tmp_name"]);

                if($width > 100 || $height > 100)	return Main::redirect("user/overlay/message",array("danger",e("Avatar must be either a 100x100 PNG or a JPEG (Max 300KB).")));

                $logo = Main::strrand(8)."_overlay.".$ext[$_FILES["logo"]["type"]];
                move_uploaded_file($_FILES["logo"]['tmp_name'], $upload_path.$logo);
            }

            $_POST["bg"] = Main::clean($_POST["bg"],3,TRUE);
            $_POST["color"] = Main::clean($_POST["color"],3,TRUE);
            $_POST["btncolor"] = Main::clean($_POST["btncolor"],3,TRUE);
            $_POST["btnbg"] = Main::clean($_POST["btnbg"],3,TRUE);
            $_POST["labelcolor"] = Main::clean($_POST["labelcolor"],3,TRUE);
            $_POST["labelbg"] = Main::clean($_POST["labelbg"],3,TRUE);

            $array = array(
                "message" => Main::truncate($_POST["message"],140),
                "link" => Main::clean($_POST["link"],3,TRUE),
                "label" => Main::clean($_POST["label"],3,TRUE),
                "text" => Main::clean($_POST["text"],3,TRUE),
                "bg" => (!empty($_POST["bg"]) && strlen($_POST["bg"]) < 8) ? Main::clean($_POST["bg"],3,TRUE) : $bg,
                "color" => (!empty($_POST["color"]) && strlen($_POST["color"]) < 8) ? Main::clean($_POST["color"],3,TRUE) : $color,
                "btnbg" => (!empty($_POST["btnbg"]) && strlen($_POST["btnbg"]) < 8) ? Main::clean($_POST["btnbg"],3,TRUE) : $btnbg,
                "btncolor" => (!empty($_POST["btncolor"]) && strlen($_POST["btncolor"]) < 8) ? Main::clean($_POST["btncolor"],3,TRUE) : $btncolor,
                "labelbg" => (!empty($_POST["labelbg"]) && strlen($_POST["labelbg"]) < 8) ? Main::clean($_POST["labelbg"],3,TRUE) : $labelbg,
                "labelcolor" => (!empty($_POST["labelcolor"]) && strlen($_POST["labelcolor"]) < 8) ? Main::clean($_POST["labelcolor"],3,TRUE) : $labelcolor,
                "position" => Main::clean($_POST["position"],3,TRUE),
                "image" => $logo
            );

            $array = json_encode($array);
            $data = array(
                ":userid" => $this->user->id,
                ":name" => Main::clean($_POST["name"],3,TRUE),
                ":data" => $array,
                ":type" => "message",
                ":date" => "NOW()"
            );

            if($this->db->insert("overlay",$data)){
                return Main::redirect("user/overlay",array("success",e("Overlay has been saved.")));
            }
            return Main::redirect(Main::href("user/overlay/message","",FALSE),array("danger",e("Something went wrong, please try again.")));
        }



        Main::cdn("spectrum");

        Main::add('<script type="text/javascript">
							  $("input[name=logo]").change(function(e){
							    if(!e.target.files[0]) return $(".custom-img img").remove();							  	
							    var type = e.target.files[0].type;
							    if(type == "image/png" || type == "image/jpeg"){
							      var reader = new FileReader();							      
							      reader.onload = function (e) {							          
						         $(".custom-img").html("<img src=\'"+e.target.result+"\'>");
							      }
							      reader.readAsDataURL(e.target.files[0]);      
							    }
							  });  							
					  		function bgColor(element, color, e) {
						        $(element).css("background-color", (color ? color.toHexString() : ""));
						        e.val(color.toHexString());
						    }
					  		function Color(element, color, e) {
						        $(element).css("color", (color ? color.toHexString() : ""));
						        e.val(color.toHexString());
						    }		
						    $("#message").keyup(function(e){
						    	if($(this).val().length > 140) return false;
									$(".custom-message .custom-text").text($(this).val());
						    });		
						    $("#label").keyup(function(e){
						    	if($(this).val().length > 8) return false;
						    	if($(this).val().length < 1) return $(".custom-message .custom-label").hide();
						    	$(".custom-message .custom-label").show();
									$(".custom-message .custom-label").text($(this).val());
						    });	
						    $("#text").keyup(function(e){
						    	if($(this).val().length > 35) return false;
						    	if($(this).val().length < 1) return $(".custom-message .btn").hide();
						    	return $(".custom-message .btn").show();
									$(".custom-message .btn").text($(this).val());
						    });							    						    				    
					  		$("#bg").spectrum({
					        color: "'.$bg.'",
					        showInput: true,
					        preferredFormat: "hex",
					        move: function (color) { bgColor(".custom-message", color, $(this)); },
					        hide: function (color) { bgColor(".custom-message", color, $(this)); }
					    	}); 
					  		$("#color").spectrum({
					        color: "'.$color.'",
					        showInput: true,
					        preferredFormat: "hex",
					        move: function (color) { Color(".custom-message .custom-text", color, $(this)); },
					        hide: function (color) { Color(".custom-message .custom-text", color, $(this)); }
					    	});
					  		$("#btnbg").spectrum({
					        color: "'.$btnbg.'",
					        showInput: true,
					        preferredFormat: "hex",
					        move: function (color) { bgColor(".custom-message .btn", color, $(this)); },
					        hide: function (color) { bgColor(".custom-message .btn", color, $(this)); }
						    });  
					  		$("#btncolor").spectrum({
					        color: "'.$btncolor.'",
					        showInput: true,
					        preferredFormat: "hex",
					        move: function (color) { Color(".custom-message .btn", color, $(this)); },
					        hide: function (color) { Color(".custom-message .btn", color, $(this)); }
					    	});
					  		$("#labelbg").spectrum({
					        color: "'.$labelbg.'",
					        showInput: true,
					        preferredFormat: "hex",					        
					        move: function (color) { bgColor(".custom-message .custom-label", color, $(this)); },
					        hide: function (color) { bgColor(".custom-message .custom-label", color, $(this)); }
						    });  
					  		$("#labelcolor").spectrum({
					        color: "'.$labelcolor.'",
					        showInput: true,
					        preferredFormat: "hex",
					        move: function (color) { Color(".custom-message .custom-label", color, $(this)); },
					        hide: function (color) { Color(".custom-message .custom-label", color, $(this)); }
					       });					    	
					    </script>', "custom", TRUE);

        Main::set("title",e("Customize Message Overlay"));

        Main::set("description","Customize message overlay to attract more customers to your product or site.");

        $content="
				<form action='".Main::href("user/overlay/message")."' class='form' method='post' enctype='multipart/form-data'>
					<div class='form-group'>
						<label for='message'>".e('Name')."</label>
						<input type='text' class='form-control' name='name' id='name'  placeholder='e.g. Promo' value=''>
					</div>				
					<div class='form-group'>
						<label for='message'>".e('Custom Message')." (Max: 140 chars)</label>
						<textarea name='message' id='message' cols='30' rows='5' class='form-control' placeholder='e.g. ".e("Get a $10 discount with any purchase more than $50")."'>$message</textarea>
					</div>				
					<div class='form-group'>
						<label for='label'>".e('Overlay label')." (".e("leave empty to disable").")</label>
						<input type='text' class='form-control' name='label' id='label'  placeholder='e.g. Promo' value='$label'>
					</div>				
					<div class='form-group'>
						<label for='label'>".e('Avatar')."</label>
						<input type='file' class='form-control' name='logo'>
						<p class='help-block'>".e("Avatar should be square with a maximum size of 100x100. To remove the image, click on the upload field and then cancel it.")."</p>
					</div>											
					<div class='form-group'>
						<label for='link'>".e('Button Link')." (".e("leave empty to disable").")</label>
						<input type='text' class='form-control' name='link' id='link'  placeholder='e.g. http://domain.com/' value='$link'>
						<p class='help-block'>".e("If you remove the button text below but add a link here, the whole overlay will be linked to this when clicked.")."</p>
					</div>
					<div class='form-group'>
						<label for='text'>".e('Button Text')." (".e("leave empty to disable").")</label>
						<input type='text' class='form-control' name='text' id='text'  placeholder='e.g. ".e("Learn more")."' value='$text'>
					</div>
					<div class='form-group'>
						<label for='bg'>".e('Overlay Background Color')."</label> <br>
						<input type='input' name='bg' id='bg'>
					</div>			
					<div class='form-group'>
						<label for='color'>".e('Overlay Text Color')."</label><br>
						<input type='input' name='color' id='color'>
					</div>	
					<div class='form-group'>
						<label for='btnbg'>".e('Button Background Color')."</label><br>
						<input type='input' name='btnbg' id='btnbg'>
					</div>		
					<div class='form-group'>
						<label for='btncolor'>".e('Button Text Color')."</label><br>
						<input type='input' name='btncolor' id='btncolor'>
					</div>		
					<div class='form-group'>
						<label for='labelbg'>".e('Label Background Color')."</label><br>
						<input type='input' name='labelbg' id='labelbg'>
					</div>		
					<div class='form-group'>
						<label for='labelcolor'>".e('Label Text Color')."</label><br>
						<input type='input' name='labelcolor' id='labelcolor'>
					</div>						
					<div class='form-group'>
						<label for='position'>".e('Overlay Position')."</label>
						<select name='position' id='position' class='form-control'>
							<option value='tl' ".($position == "tl" ? "selected" : "").">".e("Top Left")."</option>
							<option value='tr' ".($position == "tr" ? "selected" : "").">".e("Top Right")."</option>
							<option value='bl' ".($position == "bl" ? "selected" : "").">".e("Bottom Left")."</option>
							<option value='br' ".($position == "br" ? "selected" : "").">".e("Bottom Right")."</option>							
						</select>
					</div>																								
					".Main::csrf_token(TRUE)."	
					<button class='btn btn-primary'>".e('Save overlay')."</button>
				</form><!-- /.form -->";

        $header = e("Customize Message Overlay");
        $before = "";
        $widgets="<div class='custom-overlay panel panel-default panel-body' id='overlay'>
								<h3>".e("Live Preview")."</h3>
								<div class='custom-message' style='background-color: $bg;'>
										<div class='custom-label'>$label</div>
										<span class='custom-img'></span>
										<p style='color: $color'>
											<span class='custom-text'>$message</span>											
											<a href='#' class='btn btn-xs' style='background-color: $btnbg;color: $btncolor'>$text</a>
										</p>
								</div><!-- /.custom-message -->
							</div><!-- /.custom-overlay -->";
        $widgets.='<div class="panel panel-default panel-body" id="'.__FUNCTION__.'">';
        $widgets.='<h3>'.e("Info").'</h3>';
        $widgets.="<p>".e("An overlay page allows you to display a small non-intrusive overlay on the destination website to advertise your product or your services. You can also use this feature to send a message to your users. You can customize the message and the appearance of the overlay right from this page. As soon as you save it, the changes will be applied immediately across all your URLs using this type. Please note that some secured and sensitive websites such as google.com or facebook.com do not work with this feature.")."</p>";
        $widgets.='</div>';

        $this->isUser=TRUE;
        $this->header();
        include($this->t("shared/user_template"));
        $this->footer();
    }

    /**
     * Create Contact Overlay
     * @author KBRmedia <https://gempixel.com>
     * @version 5.7
     * @return  [type] [description]
     */
    private function overlay_contact(){

        if($this->isTeam() && !$this->teamPermission("overlay.create")){
            return Main::redirect("user",array("danger",e("You do not have this permission. Please contact your team administrator.")));
        }

        if($this->permission("overlay") === FALSE) return Main::redirect("upgrade",array("warning",e("Please choose a premium package to unlock this feature.")));

        // Check if max limit is reached
        $count = $this->permission("overlay");

        if($count > 0 && $this->db->count("overlay","userid='{$this->user->id}'") >= $count) return Main::redirect(Main::href("user/overlay","",FALSE),array("danger",e("You have reached your max limit.")));

        // Edit Splash
        $label = e("Need Help?");
        $description = "Duis esse aliqua culpa laborum aliqua sunt proident sint officia adipisicing deserunt ullamco commodo in consectetur occaecat.";
        $bg = "#fff";
        $color = "#000";
        $inputbg = "#fff";
        $inputcolor = "#000";
        $btnbg = "#000";
        $btncolor = "#fff";
        $position = "bl";

        if(isset($_POST["token"])){
            if($this->config["demo"]){
                return Main::redirect("user/settings",array("danger",e("Feature disabled in demo.")));
            }
            if(!Main::validate_csrf_token($_POST["token"])){
                return Main::redirect(Main::href("user/overlay/contact","",FALSE),array("danger",e("Something went wrong, please try again.")));
            }

            $_POST["label"]	=	Main::clean($_POST["label"],3,TRUE);
            $_POST["content"]	=	Main::clean($_POST["content"],3,TRUE);
            $_POST["webhook"]	=	Main::clean($_POST["webhook"],3,TRUE);

            foreach ($_POST["lang"] as $key => $lang) {
                $_POST["lang"][$key] = Main::clean($lang, 3, TRUE);
            }

            if(empty($_POST["name"])) return Main::redirect(Main::href("user/overlay/contact","",FALSE),array("danger",e("The name field cannot be empty.")));

            if(empty($_POST["email"] || !Main::email($_POST["email"]))) return Main::redirect(Main::href("user/overlay/contact","",FALSE),array("danger",e("Please enter a valid email.")));


            $_POST["bg"] = Main::clean($_POST["bg"],3,TRUE);
            $_POST["color"] = Main::clean($_POST["color"],3,TRUE);
            $_POST["btncolor"] = Main::clean($_POST["btncolor"],3,TRUE);
            $_POST["btnbg"] = Main::clean($_POST["btnbg"],3,TRUE);
            $_POST["inputcolor"] = Main::clean($_POST["inputcolor"],3,TRUE);
            $_POST["inputbg"] = Main::clean($_POST["inputbg"],3,TRUE);

            $array = array(
                "email" => $_POST["email"],
                "subject" => $_POST["subject"],
                "label" => Main::clean($_POST["label"],3,TRUE),
                "content" => Main::clean($_POST["content"],3,TRUE),
                "lang" => $_POST["lang"],
                "bg" => (!empty($_POST["bg"]) && strlen($_POST["bg"]) < 8) ? Main::clean($_POST["bg"],3,TRUE) : $bg,
                "color" => (!empty($_POST["color"]) && strlen($_POST["color"]) < 8) ? Main::clean($_POST["color"],3,TRUE) : $color,
                "btnbg" => (!empty($_POST["btnbg"]) && strlen($_POST["btnbg"]) < 8) ? Main::clean($_POST["btnbg"],3,TRUE) : $btnbg,
                "btncolor" => (!empty($_POST["btncolor"]) && strlen($_POST["btncolor"]) < 8) ? Main::clean($_POST["btncolor"],3,TRUE) : $btncolor,
                "inputbg" => (!empty($_POST["inputbg"]) && strlen($_POST["inputbg"]) < 8) ? Main::clean($_POST["inputbg"],3,TRUE) : $inputbg,
                "inputcolor" => (!empty($_POST["inputcolor"]) && strlen($_POST["inputcolor"]) < 8) ? Main::clean($_POST["inputcolor"],3,TRUE) : $inputcolor,
                "position" => Main::clean($_POST["position"],3,TRUE),
                "webhook" => $_POST["webhook"]
            );

            $array = json_encode($array);
            $data = array(
                ":userid" => $this->user->id,
                ":name" => Main::clean($_POST["name"],3,TRUE),
                ":data" => $array,
                ":type" => "contact",
                ":date" => "NOW()"
            );

            if($this->db->insert("overlay",$data)){
                return Main::redirect("user/overlay",array("success",e("Overlay has been saved.")));
            }
            return Main::redirect(Main::href("user/overlay/contact","",FALSE),array("danger",e("Something went wrong, please try again.")));
        }



        Main::cdn("spectrum");

        Main::add('<script type="text/javascript">							
					  		function bgColor(element, color, e) {
						        $(element).css("background-color", (color ? color.toHexString() : ""));
						        e.val(color.toHexString());
						    }
					  		function Color(element, color, e) {
						        $(element).css("color", (color ? color.toHexString() : ""));
						        e.val(color.toHexString());
						    }		
						    $("#name-p").keyup(function(e){
									$("label[for=contact-name]").text($(this).val());
						    });		
						    $("#email-p").keyup(function(e){
									$("label[for=contact-email]").text($(this).val());
						    });			
						    $("#message-p").keyup(function(e){
									$("label[for=contact-message]").text($(this).val());
						    });			
						    $("#button-p").keyup(function(e){
									$("button[type=submit]").text($(this).val());
						    });							    				    				    					    
						    $("#label").keyup(function(e){
						    	if($(this).val().length > 20) return false;
						    	if($(this).val().length < 1) return $(".contact-box .contact-label").hide();
						    	$(".contact-box .contact-label").show();
									$(".contact-box .contact-label").text($(this).val());
						    });	
						    $("#content").keyup(function(e){
						    	if($(this).val().length > 144) return false;
						    	if($(this).val().length < 1) return $(".contact-box .contact-description").hide();
						    	$(".contact-box .contact-description").show();
									$(".contact-box .contact-description").text($(this).val());
						    });							    						    				    
					  		$("#bg").spectrum({
					        color: "'.$bg.'",
					        showInput: true,
					        preferredFormat: "hex",
					        move: function (color) { bgColor(".contact-box", color, $(this)); },
					        hide: function (color) { bgColor(".contact-box", color, $(this)); }
					    	}); 
					  		$("#color").spectrum({
					        color: "'.$color.'",
					        showInput: true,
					        preferredFormat: "hex",
					        move: function (color) { Color(".contact-label,.contact-description,.contact-box label", color, $(this)); },
					        hide: function (color) { Color(".contact-label,.contact-description,.contact-box label", color, $(this)); }
					    	});
					  		$("#inputbg").spectrum({
					        color: "'.$inputbg.'",
					        showInput: true,
					        preferredFormat: "hex",
					        move: function (color) { bgColor(".contact-box .form-control", color, $(this)); },
					        hide: function (color) { bgColor(".contact-box .form-control", color, $(this)); }
						    });  
					  		$("#inputcolor").spectrum({
					        color: "'.$inputcolor.'",
					        showInput: true,
					        preferredFormat: "hex",
					        move: function (color) { Color(".contact-box .form-control", color, $(this)); },
					        hide: function (color) { Color(".contact-box .form-control", color, $(this)); }
					    	});				    	
					  		$("#btnbg").spectrum({
					        color: "'.$btnbg.'",
					        showInput: true,
					        preferredFormat: "hex",
					        move: function (color) { bgColor(".contact-box button", color, $(this)); },
					        hide: function (color) { bgColor(".contact-box button", color, $(this)); }
						    });  
					  		$("#btncolor").spectrum({
					        color: "'.$btncolor.'",
					        showInput: true,
					        preferredFormat: "hex",
					        move: function (color) { Color(".contact-box button", color, $(this)); },
					        hide: function (color) { Color(".contact-box button", color, $(this)); }
					    	});						    	
					    </script>', "custom", TRUE);

        Main::set("title",e("Customize Contact Overlay"));

        Main::set("description","Customize contact overlay to attract more customers to your product or site.");

        $content="
				<form action='".Main::href("user/overlay/contact")."' class='form validate' method='post' enctype='multipart/form-data'>
					<div class='form-group'>
						<label for='name'>".e('Name')."</label>
						<input type='text' class='form-control' name='name' id='name'  placeholder='e.g. Promo' value='' data-required='true'>
					</div>		
					<div class='form-group'>
						<label for='email'>".e('Send Email Address')."</label>
						<input type='email' class='form-control' name='email' id='email'  placeholder='".e("Emails from the form will be send to this address")."' data-required='true'>
					</div>		
					<div class='form-group'>
						<label for='subject'>".e('Email Subject')."</label>
						<input type='text' class='form-control' name='subject' id='subject'  placeholder='".e("Something you would know where it comes from.")."' data-required='true'>
					</div>	
					<div class='form-group'>
						<label for='label'>".e('Form Label')." ".e("(leave empty to disable)")."</label>
						<input type='text' class='form-control' name='label' id='label'  placeholder='".e("e.g. Need help?")."'>
					</div>	
					<div class='form-group'>
						<label for='content'>".e('Form Description')." ".e("(leave empty to disable)")."</label>
						<textarea class='form-control' name='content' id='content' placeholder='".e("(optional) Provide a description or anything you want to add to the form.")."'></textarea>
					</div>
					<hr>
					<div class='row'>
						<div class='col-md-3'>
							<div class='form-group'>
								<label for='name-p'>".e('Name Placeholder')."</label>
								<input type='text' class='form-control' name='lang[name]' id='name-p' value='Name'>
								<p class='help-block'>".e("If you want to use a different language, change these.")."</p>
							</div>					
						</div>
						<div class='col-md-3'>
							<div class='form-group'>
								<label for='email-p'>".e('Email Placeholder')."</label>
								<input type='text' class='form-control' name='lang[email]' id='email-p' value='Email'>
								<p class='help-block'>".e("If you want to use a different language, change these.")."</p>
							</div>							
						</div>
						<div class='col-md-3'>
							<div class='form-group'>
								<label for='message-p'>".e('Message Placeholder')."</label>
								<input type='text' class='form-control' name='lang[message]' id='message-p' value='Message'>
								<p class='help-block'>".e("If you want to use a different language, change these.")."</p>
							</div>							
						</div>
						<div class='col-md-3'>
							<div class='form-group'>
								<label for='message-p'>".e('Send Button Placeholder')."</label>
								<input type='text' class='form-control' name='lang[button]' id='button-p' value='Send'>
								<p class='help-block'>".e("If you want to use a different language, change these.")."</p>
							</div>							
						</div>						
					</div>
					<hr>
					<div class='form-group'>
						<label for='bg'>".e('Form Background Color')."</label> <br>
						<input type='text' name='bg' id='bg'>
					</div>			
					<div class='form-group'>
						<label for='color'>".e('Form Text Color')."</label><br>
						<input type='text' name='color' id='color'>
					</div>	
					<div class='form-group'>
						<label for='inputbg'>".e('Input Background Color')."</label><br>
						<input type='text' name='inputbg' id='inputbg'>
					</div>		
					<div class='form-group'>
						<label for='inputcolor'>".e('Input Text Color')."</label><br>
						<input type='text' name='inputcolor' id='inputcolor'>
					</div>			
					<div class='form-group'>
						<label for='btnbg'>".e('Button Background Color')."</label><br>
						<input type='text' name='btnbg' id='btnbg'>
					</div>		
					<div class='form-group'>
						<label for='btncolor'>".e('Button Text Color')."</label><br>
						<input type='text' name='btncolor' id='btncolor'>
					</div>									
					<div class='form-group'>
						<label for='position'>".e('Overlay Position')."</label>
						<select name='position' id='position' class='form-control'>
							<option value='bl' ".($position == "bl" ? "selected" : "").">".e("Bottom Left")."</option>
							<option value='br' ".($position == "br" ? "selected" : "").">".e("Bottom Right")."</option>							
						</select>
					</div>	
					<hr>
					<div class='form-group'>
						<label for='webhook'>".e('Webhook Notification')."</label><br>
						<input type='text' name='webhook' id='webhook' class='form-control' placeholder='e.g. https://domain.com/path/to/webhook-receiver'>
						<p class='help-block'>".e("If you want to receive a notification directly to your app, add the url to your app's handler and as soon as there is a submission, we will send a notification to this url as well as an email to the address provided above. For more information, please check on the right.")."</p>
					</div>																													
					".Main::csrf_token(TRUE)."	
					<button class='btn btn-primary'>".e('Save overlay')."</button>
				</form><!-- /.form -->";

        $header = e("Customize Contact Overlay");
        $before = "";
        $widgets="<div class='panel panel-default panel-body' id='overlay'>
								<h3>".e("Live Preview")."</h3>
								<div class='contact-box'>
									<h1 class='contact-label'>Form Label</h1>
									<p class='contact-description'>Form Description</p>
									<div class='form-group'>
										<label for='contact-name' class='control-label'>Name</label>
										<input type='text' class='form-control' id='contact-name' placeholder='John Smith'>
									</div>
									<div class='form-group'>
										<label for='contact-email' class='control-label'>Email</label>
										<input type='text' class='form-control' id='contact-email' placeholder='johnsmith@company.com'>
									</div>		
									<div class='form-group'>
										<label for='contact-message' class='control-label'>Message</label>
										<textarea class='form-control' id='contact-message' placeholder='...'></textarea>
									</div>
									<button type='submit' class='contact-btn'>Send</button>																
								</div>
							</div><!-- /.custom-overlay -->";
        $widgets.='<div class="panel panel-default panel-body" id="'.__FUNCTION__.'">';
        $widgets.='<h3>'.e("Info").'</h3>';
        $widgets.="<p>".e("An overlay page allows you to display a small non-intrusive overlay on the destination website to advertise your product or your services. You can use this type of overlay to insert a popup-style contact form to shortened page. Please note that some secured and sensitive websites such as google.com or facebook.com do not work with this feature.")."</p>";
        $widgets.='</div>';

        $widgets.='<div class="panel panel-default panel-body" id="'.__FUNCTION__.'">';
        $widgets.='<h3>'.e("Webhook Notification").'</h3>';
        $widgets.="<p>".e("If you add a webhook url, we will send a notification to that url with the contact form data. You will be able to integrate it with your own app or a third-party app. Below is a sample data that will be send in <code>JSON</code> format via a <code>POST</code> request.")."</p>";
        $widgets.='<pre>{<br> "type": "contact",<br> "data":{<br>&nbsp;&nbsp;&nbsp;"name":"John Smith",<br>&nbsp;&nbsp;&nbsp;"email":"johnsmith@company.com",<br>&nbsp;&nbsp;&nbsp;"message":"Consequat incididunt elit do sed duis culpa sint consectetur dolore non esse veniam.",<br>&nbsp;&nbsp;&nbsp;"date":"2020-01-01 12:00"<br> &nbsp;}<br> }</pre>';
        $widgets.='</div>';

        $this->isUser=TRUE;
        $this->header();
        include($this->t("shared/user_template"));
        $this->footer();
    }
    /**
     * [overlay_poll description]
     * @author KBRmedia <https://gempixel.com>
     * @version 5.7
     * @return  [type] [description]
     */
    private function overlay_poll(){

        if($this->isTeam() && !$this->teamPermission("overlay.create")){
            return Main::redirect("user",array("danger",e("You do not have this permission. Please contact your team administrator.")));
        }

        if($this->permission("overlay") === FALSE) return Main::redirect("upgrade",array("warning",e("Please choose a premium package to unlock this feature.")));

        // Check if max limit is reached
        $count = $this->permission("overlay");

        if($count > 0 && $this->db->count("overlay","userid='{$this->user->id}'") >= $count) return Main::redirect(Main::href("user/overlay","",FALSE),array("danger",e("You have reached your max limit.")));

        // Edit Splash
        $bg = "#fff";
        $color = "#000";
        $inputbg = "#fff";
        $inputcolor = "#000";
        $btnbg = "#000";
        $btncolor = "#fff";
        $position = "bl";

        if(isset($_POST["token"])){
            if($this->config["demo"]){
                return Main::redirect("user/settings",array("danger",e("Feature disabled in demo.")));
            }
            if(!Main::validate_csrf_token($_POST["token"])){
                return Main::redirect(Main::href("user/overlay/poll","",FALSE),array("danger",e("Something went wrong, please try again.")));
            }

            $_POST["question"]	=	Main::clean($_POST["question"],3,TRUE);
            $answers = [];
            foreach ($_POST["answer"] as $key => $answer) {
                if(empty($answer)) continue;
                $answers[$key]["option"] = Main::clean($answer, 3, TRUE);
                $answers[$key]["votes"] = 0;
            }

            if(count($_POST["answer"]) < 2) return Main::redirect(Main::href("user/overlay/poll","",FALSE),array("danger",e("A minimum of two options is required.")));

            if(empty($_POST["name"])) return Main::redirect(Main::href("user/overlay/poll","",FALSE),array("danger",e("The name field cannot be empty.")));


            $_POST["bg"] = Main::clean($_POST["bg"],3,TRUE);
            $_POST["color"] = Main::clean($_POST["color"],3,TRUE);
            $_POST["btncolor"] = Main::clean($_POST["btncolor"],3,TRUE);
            $_POST["btnbg"] = Main::clean($_POST["btnbg"],3,TRUE);

            $array = array(
                "question" => $_POST["question"],
                "answers" => $answers,
                "bg" => (!empty($_POST["bg"]) && strlen($_POST["bg"]) < 8) ? Main::clean($_POST["bg"],3,TRUE) : $bg,
                "color" => (!empty($_POST["color"]) && strlen($_POST["color"]) < 8) ? Main::clean($_POST["color"],3,TRUE) : $color,
                "btnbg" => (!empty($_POST["btnbg"]) && strlen($_POST["btnbg"]) < 8) ? Main::clean($_POST["btnbg"],3,TRUE) : $btnbg,
                "btncolor" => (!empty($_POST["btncolor"]) && strlen($_POST["btncolor"]) < 8) ? Main::clean($_POST["btncolor"],3,TRUE) : $btncolor,
                "position" => Main::clean($_POST["position"],3,TRUE)
            );

            $array = json_encode($array);
            $data = array(
                ":userid" => $this->user->id,
                ":name" => Main::clean($_POST["name"],3,TRUE),
                ":data" => $array,
                ":type" => "poll",
                ":date" => "NOW()"
            );
            if($this->db->insert("overlay",$data)){
                return Main::redirect("user/overlay",array("success",e("Overlay has been saved.")));
            }
            return Main::redirect(Main::href("user/overlay/poll","",FALSE),array("danger",e("Something went wrong, please try again.")));
        }

        Main::cdn("spectrum");

        Main::add('<script type="text/javascript">
					  		function bgColor(element, color, e) {
						        $(element).css("background-color", (color ? color.toHexString() : ""));
						        e.val(color.toHexString());
						    }
					  		function Color(element, color, e) {
						        $(element).css("color", (color ? color.toHexString() : ""));
						        e.val(color.toHexString());
						    }		
						    $("#question").keyup(function(e){
						    	if($(this).val().length > 144) return false;
									$(".poll-question").text($(this).val());
						    });		
						    $(".poll-answer").keyup(function(e){
									$("label[for=contact-email]").text($(this).val());
						    });			
					  		$("#bg").spectrum({
					        color: "'.$bg.'",
					        showInput: true,
					        preferredFormat: "hex",
					        move: function (color) { bgColor(".poll-box", color, $(this)); },
					        hide: function (color) { bgColor(".poll-box", color, $(this)); }
					    	}); 
					  		$("#color").spectrum({
					        color: "'.$color.'",
					        showInput: true,
					        preferredFormat: "hex",
					        move: function (color) { Color(".poll-box .poll-question,.poll-answers li", color, $(this)); },
					        hide: function (color) { Color(".poll-box .poll-question,.poll-answers li", color, $(this)); }
					    	});					  				    	
					  		$("#btnbg").spectrum({
					        color: "'.$btnbg.'",
					        showInput: true,
					        preferredFormat: "hex",
					        move: function (color) { bgColor(".poll-box button", color, $(this)); },
					        hide: function (color) { bgColor(".poll-box button", color, $(this)); }
						    });  
					  		$("#btncolor").spectrum({
					        color: "'.$btncolor.'",
					        showInput: true,
					        preferredFormat: "hex",
					        move: function (color) { Color(".poll-box button", color, $(this)); },
					        hide: function (color) { Color(".poll-box button", color, $(this)); }
					    	});						    	
					    </script>', "custom", TRUE);

        Main::set("title",e("Customize Poll Overlay"));

        Main::set("description","Customize poll overlay to attract more customers to your product or site.");

        $content="
				<form action='".Main::href("user/overlay/poll")."' class='form validate' method='post' enctype='multipart/form-data'>
					<div class='form-group'>
						<label for='name'>".e('Name')."</label>
						<input type='text' class='form-control' name='name' id='name'  placeholder='e.g. Promo' value='' data-required='true'>
					</div>		
					<div class='form-group'>
						<label for='question'>".e('Question')." (max 144)</label>
						<input type='text' class='form-control' name='question' id='question'  placeholder='' data-required='true'>
					</div>
					<hr>
					<h3>".e("Options")." <small>(max 10)</small> <a href='' class='addA btn btn-xs btn-default pull-right'>".e("Add Option")."</a></h3>
					<p class='help-block'>".e("You can add up to 10 options for each poll. To add an extra option click Add Option above. To ignore a field, leave it empty.")."</p>
					<div class='poll-options'>
						<div class='form-group'>
							<input type='text' class='form-control' name='answer[]' id='answer[]'  placeholder='#1' data-id='1'>
						</div>	
						<div class='form-group'>
							<input type='text' class='form-control' name='answer[]' id='answer[]'  placeholder='#2' data-id='2'>
						</div>						
					</div>													
					<hr>
					<div class='form-group'>
						<label for='bg'>".e('Background Color')."</label> <br>
						<input type='text' name='bg' id='bg'>
					</div>			
					<div class='form-group'>
						<label for='color'>".e('Text Color')."</label><br>
						<input type='text' name='color' id='color'>
					</div>		
					<div class='form-group'>
						<label for='btnbg'>".e('Button Background Color')."</label><br>
						<input type='text' name='btnbg' id='btnbg'>
					</div>		
					<div class='form-group'>
						<label for='btncolor'>".e('Button Text Color')."</label><br>
						<input type='text' name='btncolor' id='btncolor'>
					</div>									
					<div class='form-group'>
						<label for='position'>".e('Overlay Position')."</label>
						<select name='position' id='position' class='form-control'>
							<option value='bl' ".($position == "bl" ? "selected" : "").">".e("Bottom Left")."</option>
							<option value='br' ".($position == "br" ? "selected" : "").">".e("Bottom Right")."</option>								
						</select>
					</div>																													
					".Main::csrf_token(TRUE)."	
					<button class='btn btn-primary'>".e('Save overlay')."</button>
				</form>";

        $header = e("Customize Contact Overlay");
        $before = "";
        $widgets="<div class='panel panel-default panel-body' id='overlay'>
								<h3>".e("Live Preview")."</h3>
								<div class='poll-box'>
									<p class='poll-question'>".e("Your question here?")."</p>
									<ol class='poll-answers'>
										<li data-id='1'>#1</li>
										<li data-id='2'>#2</li>
									</ol>
									<button type='submit' class='poll-btn'>".e("Vote")."</button>																
								</div>
							</div><!-- /.custom-overlay -->";
        $widgets.='<div class="panel panel-default panel-body" id="'.__FUNCTION__.'">';
        $widgets.='<h3>'.e("Info").'</h3>';
        $widgets.="<p>".e("An overlay page allows you to display a small non-intrusive overlay on the destination website to advertise your product or your services. You can use this type of overlay to insert a poll to shortened page. Please note that some secured and sensitive websites such as google.com or facebook.com do not work with this feature.")."</p>";
        $widgets.='</div>';

        $this->isUser=TRUE;
        $this->header();
        include($this->t("shared/user_template"));
        $this->footer();
    }
    /**
     * [overlay_edit description]
     * @author KBRmedia <https://gempixel.com>
     * @version 5.6
     * @return  [type] [description]
     */
    private function overlay_edit(){

        if($this->isTeam() && !$this->teamPermission("overlay.edit")){
            return Main::redirect("user",array("danger",e("You do not have this permission. Please contact your team administrator.")));
        }

        if($this->permission("overlay") === FALSE) return Main::redirect("upgrade",array("warning",e("Please choose a premium package to unlock this feature.")));

        if(!$overlay = $this->db->get("overlay", ["id" => $this->id, "userid" => $this->user->id], ["limit" => "1"])){
            return Main::redirect("overlay",array("danger",e("Overlay page does not exist.")));
        }

        $fn = "overlay_{$overlay->type}_edit";
        return $this->{$fn}($overlay);
    }
    /**
     * [overlay_message_edit description]
     * @author KBRmedia <https://gempixel.com>
     * @version 5.7
     * @return  [type] [description]
     */
    private function overlay_message_edit($overlay){

        $overlay->data = json_decode($overlay->data);
        // Edit Splash
        $message = $overlay->data->message;
        $label = $overlay->data->label;
        $link = $overlay->data->link;
        $text = $overlay->data->text;
        $bg = $overlay->data->bg;
        $color = $overlay->data->color;
        $btnbg = $overlay->data->btnbg;
        $btncolor = $overlay->data->btncolor;
        $labelbg = $overlay->data->labelbg;
        $labelcolor = $overlay->data->labelcolor;
        $position = $overlay->data->position;

        if(isset($_POST["token"])){
            if($this->config["demo"]){
                return Main::redirect("user/settings",array("danger",e("Feature disabled in demo.")));
            }
            if(!Main::validate_csrf_token($_POST["token"])){
                return Main::redirect(Main::href("user/overlay/{$overlay->id}","",FALSE),array("danger",e("Something went wrong, please try again.")));
            }

            $_POST["message"]	=	Main::clean($_POST["message"],3,TRUE);

            if(empty($_POST["message"])) return Main::redirect(Main::href("user/overlay/{$overlay->id}","",FALSE),array("danger",e("The message field cannot be empty.")));
            if(!empty($_POST["link"]) && !Main::is_url($_POST["link"])) return Main::redirect("user/overlay/{$overlay->id}",array("danger",e("Please enter a valid URL.")));

            $upload_path=ROOT."/content/";
            $logo = $overlay->data->image;
            $ext = ["image/png"=>"png","image/jpeg"=>"jpg","image/jpg"=>"jpg"];

            if(isset($_FILES["logo"]["type"]) && !empty($_FILES["logo"]["type"])){

                if(!isset($ext[$_FILES["logo"]["type"]])) return Main::redirect("user/overlay/message",array("danger",e("Avatar must be either a PNG or a JPEG.")));

                if($_FILES["logo"]["size"]>300*1024) return Main::redirect("user/overlay/message",array("danger",e("Avatar must be either a maximum size of 100x100 PNG or a JPEG (Max 300KB).")));

                list($width, $height) = getimagesize($_FILES["logo"]["tmp_name"]);

                if($width > 100 || $height > 100)	return Main::redirect("user/overlay/message",array("danger",e("Avatar must be either a 100x100 PNG or a JPEG (Max 300KB).")));

                $logo = Main::strrand(8)."_overlay.".$ext[$_FILES["logo"]["type"]];
                move_uploaded_file($_FILES["logo"]['tmp_name'], $upload_path.$logo);
                if($overlay->data->image){
                    unlink( $upload_path.$overlay->data->image);
                }
            }

            $_POST["bg"] = Main::clean($_POST["bg"],3,TRUE);
            $_POST["color"] = Main::clean($_POST["color"],3,TRUE);
            $_POST["btncolor"] = Main::clean($_POST["btncolor"],3,TRUE);
            $_POST["btnbg"] = Main::clean($_POST["btnbg"],3,TRUE);
            $_POST["labelcolor"] = Main::clean($_POST["labelcolor"],3,TRUE);
            $_POST["labelbg"] = Main::clean($_POST["labelbg"],3,TRUE);

            $array = array(
                "message" => Main::truncate($_POST["message"],140),
                "link" => Main::clean($_POST["link"],3,TRUE),
                "label" => Main::clean($_POST["label"],3,TRUE),
                "text" => Main::clean($_POST["text"],3,TRUE),
                "bg" => (!empty($_POST["bg"]) && strlen($_POST["bg"]) < 8) ? Main::clean($_POST["bg"],3,TRUE) : $bg,
                "color" => (!empty($_POST["color"]) && strlen($_POST["color"]) < 8) ? Main::clean($_POST["color"],3,TRUE) : $color,
                "btnbg" => (!empty($_POST["btnbg"]) && strlen($_POST["btnbg"]) < 8) ? Main::clean($_POST["btnbg"],3,TRUE) : $btnbg,
                "btncolor" => (!empty($_POST["btncolor"]) && strlen($_POST["btncolor"]) < 8) ? Main::clean($_POST["btncolor"],3,TRUE) : $btncolor,
                "labelbg" => (!empty($_POST["labelbg"]) && strlen($_POST["labelbg"]) < 8) ? Main::clean($_POST["labelbg"],3,TRUE) : $labelbg,
                "labelcolor" => (!empty($_POST["labelcolor"]) && strlen($_POST["labelcolor"]) < 8) ? Main::clean($_POST["labelcolor"],3,TRUE) : $labelcolor,
                "position" => Main::clean($_POST["position"],3,TRUE),
                "image" => $logo
            );

            $array = json_encode($array);
            $data = array(
                ":name" => Main::clean($_POST["name"],3,TRUE),
                ":data" => $array,
            );

            if($this->db->update("overlay","",array("id"=>$this->id,"userid"=>$this->user->id),$data)){
                return Main::redirect("user/overlay/{$overlay->id}",array("success",e("Overlay has been saved.")));
            }
            return Main::redirect(Main::href("user/overlay/{$overlay->id}","",FALSE),array("danger",e("Something went wrong, please try again.")));
        }

        Main::cdn("spectrum");

        Main::add('<script type="text/javascript">
						  	$("input[name=logo]").change(function(e){
							    if(!e.target.files[0]) return $(".custom-img img").remove();							  	
							    var type = e.target.files[0].type;
							    if(type == "image/png" || type == "image/jpeg"){
							      var reader = new FileReader();							      
							      reader.onload = function (e) {							          
						         $(".custom-img").html("<img src=\'"+e.target.result+"\'>");
							      }
							      reader.readAsDataURL(e.target.files[0]);      
							    }
							  });       
					  		function bgColor(element, color, e) {
						        $(element).css("background-color", (color ? color.toHexString() : ""));
						        e.val(color.toHexString());
						    }
					  		function Color(element, color, e) {
						        $(element).css("color", (color ? color.toHexString() : ""));
						        e.val(color.toHexString());
						    }		
						    $("#message").keyup(function(e){
						    	if($(this).val().length > 140) return false;
									$(".custom-message p").text($(this).val());
						    });		
						    $("#label").keyup(function(e){
						    	if($(this).val().length > 8) return false;
									$(".custom-message .custom-label").text($(this).val());
						    });	
						    $("#text").keyup(function(e){
						    	if($(this).val().length > 35) return false;
									$(".custom-message .btn").text($(this).val());
						    });							    						    				    
					  		$("#bg").spectrum({
					        color: "'.$bg.'",
					        showInput: true,
					        preferredFormat: "hex",
					        move: function (color) { bgColor(".custom-message", color, $(this)); },
					        hide: function (color) { bgColor(".custom-message", color, $(this)); }
					    	}); 
					  		$("#color").spectrum({
					        color: "'.$color.'",
					        showInput: true,
					        preferredFormat: "hex",
					        move: function (color) { Color(".custom-message p", color, $(this)); },
					        hide: function (color) { Color(".custom-message p", color, $(this)); }
					    	});
					  		$("#btnbg").spectrum({
					        color: "'.$btnbg.'",
					        showInput: true,
					        preferredFormat: "hex",
					        move: function (color) { bgColor(".custom-message .btn", color, $(this)); },
					        hide: function (color) { bgColor(".custom-message .btn", color, $(this)); }
						    });  
					  		$("#btncolor").spectrum({
					        color: "'.$btncolor.'",
					        showInput: true,
					        preferredFormat: "hex",
					        move: function (color) { Color(".custom-message .btn", color, $(this)); },
					        hide: function (color) { Color(".custom-message .btn", color, $(this)); }
					    	});
					  		$("#labelbg").spectrum({
					        color: "'.$labelbg.'",
					        showInput: true,
					        preferredFormat: "hex",					        
					        move: function (color) { bgColor(".custom-message .custom-label", color, $(this)); },
					        hide: function (color) { bgColor(".custom-message .custom-label", color, $(this)); }
						    });  
					  		$("#labelcolor").spectrum({
					        color: "'.$labelcolor.'",
					        showInput: true,
					        preferredFormat: "hex",
					        move: function (color) { Color(".custom-message .custom-label", color, $(this)); },
					        hide: function (color) { Color(".custom-message .custom-label", color, $(this)); }
					       });					    	
					    </script>', "custom", TRUE);

        Main::set("title",e("Customize Message Overlay"));

        Main::set("description","Customize message overlay to attract more customers to your product or site.");

        $content="
				<form action='".Main::href("user/overlay/{$overlay->id}")."' class='form' method='post' enctype='multipart/form-data'>
					<div class='form-group'>
						<label for='message'>".e('Name')."</label>
						<input type='text' class='form-control' name='name' id='name'  placeholder='e.g. Promo' value='{$overlay->name}'>
					</div>				
					<div class='form-group'>
						<label for='message'>".e('Custom Message')." (Max: 140 chars)</label>
						<textarea name='message' id='message' cols='30' rows='5' class='form-control'>$message</textarea>
					</div>				
					<div class='form-group'>
						<label for='label'>".e('Overlay label')." </label>
						<input type='text' class='form-control' name='label' id='label'  placeholder='e.g. Promo' value='$label'>
					</div>						
					<div class='form-group'>
						<label for='label'>".e('Avatar')."</label>
						<input type='file' class='form-control' name='logo'>
						<p class='help-block'>".e("Avatar should be square with a maximum size of 100x100. To remove the image, click on the upload field and then cancel it.")."</p>
					</div>				
					<div class='form-group'>
						<label for='link'>".e('Button Link')." (".e("leave empty to disable").")</label>
						<input type='text' class='form-control' name='link' id='link'  placeholder='e.g. http://domain.com/' value='$link'>
					</div>
					<div class='form-group'>
						<label for='text'>".e('Button Text')." (".e("leave empty to disable").")</label>
						<input type='text' class='form-control' name='text' id='text' value='$text'>
					</div>
					<div class='form-group'>
						<label for='bg'>".e('Overlay Background Color')."</label> <br>
						<input type='input' name='bg' id='bg'>
					</div>			
					<div class='form-group'>
						<label for='color'>".e('Overlay Text Color')."</label><br>
						<input type='input' name='color' id='color'>
					</div>	
					<div class='form-group'>
						<label for='btnbg'>".e('Button Background Color')."</label><br>
						<input type='input' name='btnbg' id='btnbg'>
					</div>		
					<div class='form-group'>
						<label for='btncolor'>".e('Button Text Color')."</label><br>
						<input type='input' name='btncolor' id='btncolor'>
					</div>		
					<div class='form-group'>
						<label for='labelbg'>".e('Label Background Color')."</label><br>
						<input type='input' name='labelbg' id='labelbg'>
					</div>		
					<div class='form-group'>
						<label for='labelcolor'>".e('Label Text Color')."</label><br>
						<input type='input' name='labelcolor' id='labelcolor'>
					</div>						
					<div class='form-group'>
						<label for='position'>".e('Overlay Position')."</label>
						<select name='position' id='position' class='form-control'>
							<option value='tl' ".($position == "tl" ? "selected" : "").">Top Left</option>
							<option value='tr' ".($position == "tr" ? "selected" : "").">Top Right</option>
							<option value='bl' ".($position == "bl" ? "selected" : "").">Bottom Left</option>
							<option value='br' ".($position == "br" ? "selected" : "").">Bottom Right</option>							
						</select>
					</div>																								
					".Main::csrf_token(TRUE)."	
					<button class='btn btn-primary'>".e('Save overlay')."</button>
				</form><!-- /.form -->";

        $header = e("Customize your overlay page");
        $before = "";
        $widgets="<div class='custom-overlay panel panel-default panel-body' id='overlay'>
								<h3>".e("Live Preview")."</h3><br>
								<div class='custom-message' style='background-color: $bg;'>
										<div class='custom-label'>$label</div>
										<span class='custom-img'>".(isset($overlay->data->image) && $overlay->data->image ? "<img src='{$this->config["url"]}/content/{$overlay->data->image}'>" : "")."</span>
										<p style='color: $color'>
											<span class='custom-text'>$message</span>											
											<a href='#' class='btn btn-xs' style='background-color: $btnbg;color: $btncolor'>$text</a>
										</p>
								</div><!-- /.custom-message -->
							</div><!-- /.custom-overlay -->";
        $widgets.='<div class="panel panel-default panel-body" id="'.__FUNCTION__.'">';
        $widgets.='<h3>'.e("Info").'</h3>';
        $widgets.="<p>".e("An overlay page allows you to display a small non-intrusive overlay on the destination website to advertise your product or your services. You can also use this feature to send a message to your users. You can customize the message and the appearance of the overlay right from this page. As soon as you save it, the changes will be applied immediately across all your URLs using this type. Please note that some secured and sensitive websites such as google.com or facebook.com do not work with this feature.")."</p>";
        $widgets.='</div>';

        $this->isUser=TRUE;
        $this->header();
        include($this->t("shared/user_template"));
        $this->footer();
    }
    /**
     * [overlay_contact_edit description]
     * @author KBRmedia <https://gempixel.com>
     * @version 5.7
     * @param   [type] $overlay [description]
     * @return  [type]          [description]
     */
    private function overlay_contact_edit($overlay){

        // Edit Splash
        $overlay->data = json_decode($overlay->data);

        // Edit Splash
        $label = $overlay->data->label;
        $content = $overlay->data->content;
        $bg = $overlay->data->bg;
        $color = $overlay->data->color;
        $btnbg = $overlay->data->btnbg;
        $btncolor = $overlay->data->btncolor;
        $inputcolor = $overlay->data->inputcolor;
        $inputbg = $overlay->data->inputbg;
        $position = $overlay->data->position;

        if(isset($_POST["token"])){
            if($this->config["demo"]){
                return Main::redirect("user/settings",array("danger",e("Feature disabled in demo.")));
            }
            if(!Main::validate_csrf_token($_POST["token"])){
                return Main::redirect(Main::href("user/overlay/{$overlay->id}","",FALSE),array("danger",e("Something went wrong, please try again.")));
            }

            $_POST["label"]	=	Main::clean($_POST["label"],3,TRUE);
            $_POST["content"]	=	Main::clean($_POST["content"],3,TRUE);
            $_POST["webhook"]	=	Main::clean($_POST["webhook"],3,TRUE);

            foreach ($_POST["lang"] as $key => $lang) {
                $_POST["lang"][$key] = Main::clean($lang, 3, TRUE);
            }

            if(empty($_POST["name"])) return Main::redirect(Main::href("user/overlay/{$overlay->id}","",FALSE),array("danger",e("The name field cannot be empty.")));

            if(empty($_POST["email"] || !Main::email($_POST["email"]))) return Main::redirect(Main::href("user/overlay/{$overlay->id}","",FALSE),array("danger",e("Please enter a valid email.")));


            $_POST["bg"] = Main::clean($_POST["bg"],3,TRUE);
            $_POST["color"] = Main::clean($_POST["color"],3,TRUE);
            $_POST["btncolor"] = Main::clean($_POST["btncolor"],3,TRUE);
            $_POST["btnbg"] = Main::clean($_POST["btnbg"],3,TRUE);
            $_POST["inputcolor"] = Main::clean($_POST["inputcolor"],3,TRUE);
            $_POST["inputbg"] = Main::clean($_POST["inputbg"],3,TRUE);

            $array = array(
                "email" => $_POST["email"],
                "subject" => $_POST["subject"],
                "label" => Main::clean($_POST["label"],3,TRUE),
                "content" => Main::clean($_POST["content"],3,TRUE),
                "lang" => $_POST["lang"],
                "bg" => (!empty($_POST["bg"]) && strlen($_POST["bg"]) < 8) ? Main::clean($_POST["bg"],3,TRUE) : $bg,
                "color" => (!empty($_POST["color"]) && strlen($_POST["color"]) < 8) ? Main::clean($_POST["color"],3,TRUE) : $color,
                "btnbg" => (!empty($_POST["btnbg"]) && strlen($_POST["btnbg"]) < 8) ? Main::clean($_POST["btnbg"],3,TRUE) : $btnbg,
                "btncolor" => (!empty($_POST["btncolor"]) && strlen($_POST["btncolor"]) < 8) ? Main::clean($_POST["btncolor"],3,TRUE) : $btncolor,
                "inputbg" => (!empty($_POST["inputbg"]) && strlen($_POST["inputbg"]) < 8) ? Main::clean($_POST["inputbg"],3,TRUE) : $inputbg,
                "inputcolor" => (!empty($_POST["inputcolor"]) && strlen($_POST["inputcolor"]) < 8) ? Main::clean($_POST["inputcolor"],3,TRUE) : $inputcolor,
                "position" => Main::clean($_POST["position"],3,TRUE),
                "webhook" => $_POST["webhook"]
            );

            $array = json_encode($array);
            $data = array(
                ":name" => Main::clean($_POST["name"],3,TRUE),
                ":data" => $array
            );

            if($this->db->update("overlay","", ["id" => $this->id, "userid" => $this->user->id], $data)){
                return Main::redirect("user/overlay/{$overlay->id}",array("success",e("Overlay has been saved.")));
            }
            return Main::redirect(Main::href("user/overlay/{$overlay->id}","",FALSE),array("danger",e("Something went wrong, please try again.")));
        }

        Main::cdn("spectrum");

        Main::add('<script type="text/javascript">							
					  		function bgColor(element, color, e) {
						        $(element).css("background-color", (color ? color.toHexString() : ""));
						        e.val(color.toHexString());
						    }
					  		function Color(element, color, e) {
						        $(element).css("color", (color ? color.toHexString() : ""));
						        e.val(color.toHexString());
						    }		
						    $("#name-p").keyup(function(e){
									$("label[for=contact-name]").text($(this).val());
						    });		
						    $("#email-p").keyup(function(e){
									$("label[for=contact-email]").text($(this).val());
						    });			
						    $("#message-p").keyup(function(e){
									$("label[for=contact-message]").text($(this).val());
						    });			
						    $("#button-p").keyup(function(e){
									$("button[type=submit]").text($(this).val());
						    });							    				    				    					    
						    $("#label").keyup(function(e){
						    	if($(this).val().length > 20) return false;
						    	if($(this).val().length < 1) return $(".contact-box .contact-label").hide();
						    	$(".contact-box .contact-label").show();
									$(".contact-box .contact-label").text($(this).val());
						    });	
						    $("#content").keyup(function(e){
						    	if($(this).val().length > 144) return false;
						    	if($(this).val().length < 1) return $(".contact-box .contact-description").hide();
						    	$(".contact-box .contact-description").show();
									$(".contact-box .contact-description").text($(this).val());
						    });							    						    				    
					  		$("#bg").spectrum({
					        color: "'.$bg.'",
					        showInput: true,
					        preferredFormat: "hex",
					        move: function (color) { bgColor(".contact-box", color, $(this)); },
					        hide: function (color) { bgColor(".contact-box", color, $(this)); }
					    	}); 
					  		$("#color").spectrum({
					        color: "'.$color.'",
					        showInput: true,
					        preferredFormat: "hex",
					        move: function (color) { Color(".contact-label,.contact-description,.contact-box label", color, $(this)); },
					        hide: function (color) { Color(".contact-label,.contact-description,.contact-box label", color, $(this)); }
					    	});
					  		$("#inputbg").spectrum({
					        color: "'.$inputbg.'",
					        showInput: true,
					        preferredFormat: "hex",
					        move: function (color) { bgColor(".contact-box .form-control", color, $(this)); },
					        hide: function (color) { bgColor(".contact-box .form-control", color, $(this)); }
						    });  
					  		$("#inputcolor").spectrum({
					        color: "'.$inputcolor.'",
					        showInput: true,
					        preferredFormat: "hex",
					        move: function (color) { Color(".contact-box .form-control", color, $(this)); },
					        hide: function (color) { Color(".contact-box .form-control", color, $(this)); }
					    	});				    	
					  		$("#btnbg").spectrum({
					        color: "'.$btnbg.'",
					        showInput: true,
					        preferredFormat: "hex",
					        move: function (color) { bgColor(".contact-box button", color, $(this)); },
					        hide: function (color) { bgColor(".contact-box button", color, $(this)); }
						    });  
					  		$("#btncolor").spectrum({
					        color: "'.$btncolor.'",
					        showInput: true,
					        preferredFormat: "hex",
					        move: function (color) { Color(".contact-box button", color, $(this)); },
					        hide: function (color) { Color(".contact-box button", color, $(this)); }
					    	});						    	
					    </script>', "custom", TRUE);

        Main::set("title",e("Customize Contact Overlay"));

        Main::set("description","Customize contact overlay to attract more customers to your product or site.");

        $content="
				<form action='".Main::href("user/overlay/{$overlay->id}")."' class='form validate' method='post' enctype='multipart/form-data'>
					<div class='form-group'>
						<label for='name'>".e('Name')."</label>
						<input type='text' class='form-control' name='name' id='name'  placeholder='e.g. Promo' value='{$overlay->name}' data-required='true'>
					</div>		
					<div class='form-group'>
						<label for='email'>".e('Send Email Address')."</label>
						<input type='email' class='form-control' name='email' id='email'  placeholder='".e("Emails from the form will be send to this address")."' data-required='true' value='{$overlay->data->email}'>
					</div>		
					<div class='form-group'>
						<label for='subject'>".e('Email Subject')."</label>
						<input type='text' class='form-control' name='subject' id='subject'  placeholder='".e("Something you would know where it comes from.")."' data-required='true' value='{$overlay->data->subject}'>
					</div>	
					<div class='form-group'>
						<label for='label'>".e('Form Label')." ".e("(leave empty to disable)")."</label>
						<input type='text' class='form-control' name='label' id='label'  placeholder='".e("e.g. Need help?")."' value='{$overlay->data->label}'>
					</div>	
					<div class='form-group'>
						<label for='content'>".e('Form Description')." ".e("(leave empty to disable)")."</label>
						<textarea class='form-control' name='content' id='content' placeholder='".e("(optional) Provide a description or anything you want to add to the form.")."'>{$overlay->data->content}</textarea>
					</div>
					<hr>
					<div class='row'>
						<div class='col-md-3'>
							<div class='form-group'>
								<label for='name-p'>".e('Name Placeholder')."</label>
								<input type='text' class='form-control' name='lang[name]' id='name-p' value='Name' value='{$overlay->data->lang->name}'>
								<p class='help-block'>".e("If you want to use a different language, change these.")."</p>
							</div>					
						</div>
						<div class='col-md-3'>
							<div class='form-group'>
								<label for='email-p'>".e('Email Placeholder')."</label>
								<input type='text' class='form-control' name='lang[email]' id='email-p' value='Email' value='{$overlay->data->lang->email}'>
								<p class='help-block'>".e("If you want to use a different language, change these.")."</p>
							</div>							
						</div>
						<div class='col-md-3'>
							<div class='form-group'>
								<label for='message-p'>".e('Message Placeholder')."</label>
								<input type='text' class='form-control' name='lang[message]' id='message-p' value='Message' value='{$overlay->data->lang->message}'>
								<p class='help-block'>".e("If you want to use a different language, change these.")."</p>
							</div>							
						</div>
						<div class='col-md-3'>
							<div class='form-group'>
								<label for='message-p'>".e('Send Button Placeholder')."</label>
								<input type='text' class='form-control' name='lang[button]' id='button-p' value='Send' value='{$overlay->data->lang->button}'>
								<p class='help-block'>".e("If you want to use a different language, change these.")."</p>
							</div>							
						</div>						
					</div>
					<hr>
					<div class='form-group'>
						<label for='bg'>".e('Form Background Color')."</label> <br>
						<input type='text' name='bg' id='bg'>
					</div>			
					<div class='form-group'>
						<label for='color'>".e('Form Text Color')."</label><br>
						<input type='text' name='color' id='color'>
					</div>	
					<div class='form-group'>
						<label for='inputbg'>".e('Input Background Color')."</label><br>
						<input type='text' name='inputbg' id='inputbg'>
					</div>		
					<div class='form-group'>
						<label for='inputcolor'>".e('Input Text Color')."</label><br>
						<input type='text' name='inputcolor' id='inputcolor'>
					</div>			
					<div class='form-group'>
						<label for='btnbg'>".e('Button Background Color')."</label><br>
						<input type='text' name='btnbg' id='btnbg'>
					</div>		
					<div class='form-group'>
						<label for='btncolor'>".e('Button Text Color')."</label><br>
						<input type='text' name='btncolor' id='btncolor'>
					</div>									
					<div class='form-group'>
						<label for='position'>".e('Overlay Position')."</label>
						<select name='position' id='position' class='form-control'>
							<option value='bl' ".($position == "bl" ? "selected" : "").">Bottom Left</option>
							<option value='br' ".($position == "br" ? "selected" : "").">Bottom Right</option>							
						</select>
					</div>	
					<hr>
					<div class='form-group'>
						<label for='webhook'>".e('Webhook Notification')."</label><br>
						<input type='text' name='webhook' id='webhook' class='form-control' placeholder='e.g. https://domain.com/path/to/webhook-receiver' value='{$overlay->data->webhook}'>
						<p class='help-block'>".e("If you want to receive a notification directly to your app, add the url to your app's handler and as soon as there is a submission, we will send a notification to this url as well as an email to the address provided above. For more information, please check on the right.")."</p>
					</div>																													
					".Main::csrf_token(TRUE)."	
					<button class='btn btn-primary'>".e('Save overlay')."</button>
				</form><!-- /.form -->";

        $header = e("Customize Contact Overlay");
        $before = "";
        $widgets="<div class='panel panel-default panel-body' id='overlay'>
								<h3>".e("Live Preview")."</h3>
								<div class='contact-box' style='color: {$overlay->data->color};background-color:{$overlay->data->bg} !important'>
									<h1 class='contact-label' style='color: {$overlay->data->color} !important'>{$label}</h1>
									<p class='contact-description' style='color: {$overlay->data->color} !important'>{$overlay->data->content}</p>
									<div class='form-group'>
										<label for='contact-name' class='control-label' style='color: {$overlay->data->color} !important'>{$overlay->data->lang->name}</label>
										<input style='color:{$overlay->data->inputcolor};background-color:{$overlay->data->inputbg} !important' type='text' class='form-control' id='contact-name' placeholder='John Smith'>
									</div>
									<div class='form-group'>
										<label for='contact-email' class='control-label' style='color: {$overlay->data->color} !important'>{$overlay->data->lang->email}</label>
										<input style='color:{$overlay->data->inputcolor};background-color:{$overlay->data->inputbg} !important' type='text' class='form-control' id='contact-email' placeholder='johnsmith@company.com'>
									</div>		
									<div class='form-group'>
										<label for='contact-message' class='control-label' style='color: {$overlay->data->color} !important'>{$overlay->data->lang->message}</label>
										<textarea style='color:{$overlay->data->inputcolor};background-color:{$overlay->data->inputbg} !important' class='form-control' id='contact-message' placeholder='Your message'></textarea>
									</div>
									<button type='submit' class='contact-btn' style='color:{$overlay->data->btncolor};background-color:{$overlay->data->btnbg} !important'>{$overlay->data->lang->button}</button>																
								</div>
							</div><!-- /.custom-overlay -->";
        $widgets.='<div class="panel panel-default panel-body" id="'.__FUNCTION__.'">';
        $widgets.='<h3>'.e("Info").'</h3>';
        $widgets.="<p>".e("An overlay page allows you to display a small non-intrusive overlay on the destination website to advertise your product or your services. You can use this type of overlay to insert a popup-style contact form to shortened page. Please note that some secured and sensitive websites such as google.com or facebook.com do not work with this feature.")."</p>";
        $widgets.='</div>';

        $widgets.='<div class="panel panel-default panel-body" id="'.__FUNCTION__.'">';
        $widgets.='<h3>'.e("Webhook Notification").'</h3>';
        $widgets.="<p>".e("If you add a webhook url, we will send a notification to that url with the contact form data. You will be able to integrate it with your own app or a third-party app. Below is a sample data that will be send in <code>JSON</code> format via a <code>POST</code> request.")."</p>";
        $widgets.='<pre>{<br> "type": "contact",<br> "data":{<br>&nbsp;&nbsp;&nbsp;"name":"John Smith",<br>&nbsp;&nbsp;&nbsp;"email":"johnsmith@company.com",<br>&nbsp;&nbsp;&nbsp;"message":"Consequat incididunt elit do sed duis culpa sint consectetur dolore non esse veniam.",<br>&nbsp;&nbsp;&nbsp;"date":"2020-01-01 12:00"<br> &nbsp;}<br> }</pre>';
        $widgets.='</div>';

        $this->isUser=TRUE;
        $this->header();
        include($this->t("shared/user_template"));
        $this->footer();
    }
    /**
     * [overlay_poll_edit description]
     * @author KBRmedia <https://gempixel.com>
     * @version 5.7
     * @param   [type] $overlay [description]
     * @return  [type]          [description]
     */
    private function overlay_poll_edit($overlay){

        $overlay->data = json_decode($overlay->data);
        // Edit Splash
        $bg = $overlay->data->bg;
        $color = $overlay->data->color;
        $btnbg = $overlay->data->btnbg;
        $btncolor = $overlay->data->btncolor;
        $position = $overlay->data->position;

        if(isset($_POST["token"])){
            if($this->config["demo"]){
                return Main::redirect("user/settings",array("danger",e("Feature disabled in demo.")));
            }
            if(!Main::validate_csrf_token($_POST["token"])){
                return Main::redirect(Main::href("user/overlay/{$overlay->id}","",FALSE),array("danger",e("Something went wrong, please try again.")));
            }

            $_POST["question"]	=	Main::clean($_POST["question"],3,TRUE);
            $answers = [];
            foreach ($_POST["answer"] as $key => $answer) {
                if(empty($answer)) continue;
                $answers[$key]["option"] = Main::clean($answer, 3, TRUE);
                $answers[$key]["votes"] = isset($overlay->data->answers[$key]) ? $overlay->data->answers[$key]->votes : 0;
            }

            if(count($_POST["answer"]) < 2) return Main::redirect(Main::href("user/overlay/{$overlay->id}","",FALSE),array("danger",e("A minimum of two options is required.")));

            if(empty($_POST["name"])) return Main::redirect(Main::href("user/overlay/{$overlay->id}","",FALSE),array("danger",e("The name field cannot be empty.")));


            $_POST["bg"] = Main::clean($_POST["bg"],3,TRUE);
            $_POST["color"] = Main::clean($_POST["color"],3,TRUE);
            $_POST["btncolor"] = Main::clean($_POST["btncolor"],3,TRUE);
            $_POST["btnbg"] = Main::clean($_POST["btnbg"],3,TRUE);

            $array = array(
                "question" => $_POST["question"],
                "answers" => $answers,
                "bg" => (!empty($_POST["bg"]) && strlen($_POST["bg"]) < 8) ? Main::clean($_POST["bg"],3,TRUE) : $bg,
                "color" => (!empty($_POST["color"]) && strlen($_POST["color"]) < 8) ? Main::clean($_POST["color"],3,TRUE) : $color,
                "btnbg" => (!empty($_POST["btnbg"]) && strlen($_POST["btnbg"]) < 8) ? Main::clean($_POST["btnbg"],3,TRUE) : $btnbg,
                "btncolor" => (!empty($_POST["btncolor"]) && strlen($_POST["btncolor"]) < 8) ? Main::clean($_POST["btncolor"],3,TRUE) : $btncolor,
                "position" => Main::clean($_POST["position"],3,TRUE)
            );

            $array = json_encode($array);
            $data = array(
                ":name" => Main::clean($_POST["name"],3,TRUE),
                ":data" => $array
            );
            if($this->db->update("overlay","",["id" => $this->id, "userid"=>$this->user->id], $data)){
                return Main::redirect("user/overlay/{$overlay->id}",array("success",e("Overlay has been saved.")));
            }
            return Main::redirect(Main::href("user/overlay/{$overlay->id}","",FALSE),array("danger",e("Something went wrong, please try again.")));
        }

        Main::cdn("spectrum");

        Main::add('<script type="text/javascript">
					  		function bgColor(element, color, e) {
						        $(element).css("background-color", (color ? color.toHexString() : ""));
						        e.val(color.toHexString());
						    }
					  		function Color(element, color, e) {
						        $(element).css("color", (color ? color.toHexString() : ""));
						        e.val(color.toHexString());
						    }		
						    $("#question").keyup(function(e){
						    	if($(this).val().length > 144) return false;
									$(".poll-question").text($(this).val());
						    });		
						    $(".poll-answer").keyup(function(e){
									$("label[for=contact-email]").text($(this).val());
						    });			
					  		$("#bg").spectrum({
					        color: "'.$bg.'",
					        showInput: true,
					        preferredFormat: "hex",
					        move: function (color) { bgColor(".poll-box", color, $(this)); },
					        hide: function (color) { bgColor(".poll-box", color, $(this)); }
					    	}); 
					  		$("#color").spectrum({
					        color: "'.$color.'",
					        showInput: true,
					        preferredFormat: "hex",
					        move: function (color) { Color(".poll-box .poll-question,.poll-answers li", color, $(this)); },
					        hide: function (color) { Color(".poll-box .poll-question,.poll-answers li", color, $(this)); }
					    	});					  				    	
					  		$("#btnbg").spectrum({
					        color: "'.$btnbg.'",
					        showInput: true,
					        preferredFormat: "hex",
					        move: function (color) { bgColor(".poll-box button", color, $(this)); },
					        hide: function (color) { bgColor(".poll-box button", color, $(this)); }
						    });  
					  		$("#btncolor").spectrum({
					        color: "'.$btncolor.'",
					        showInput: true,
					        preferredFormat: "hex",
					        move: function (color) { Color(".poll-box button", color, $(this)); },
					        hide: function (color) { Color(".poll-box button", color, $(this)); }
					    	});						    	
					    </script>', "custom", TRUE);

        Main::set("title",e("Customize Poll Overlay"));

        Main::set("description","Customize poll overlay to attract more customers to your product or site.");

        $content="
				<form action='".Main::href("user/overlay/{$overlay->id}")."' class='form validate' method='post' enctype='multipart/form-data'>
					<div class='form-group'>
						<label for='name'>".e('Name')."</label>
						<input type='text' class='form-control' name='name' id='name'  placeholder='e.g. Promo' value='{$overlay->name}' data-required='true'>
					</div>		
					<div class='form-group'>
						<label for='question'>".e('Question')." (max 144)</label>
						<input type='text' class='form-control' name='question' id='question'  value='{$overlay->data->question}' data-required='true'>
					</div>
					<hr>
					<h3>".e("Options")." <small>(max 10)</small> <a href='' class='addA btn btn-xs btn-default pull-right'>".e("Add Option")."</a></h3>
					<p class='help-block'>".e("You can add up to 10 options for each poll. To add an extra option click Add Option above. To ignore a field, leave it empty.")."</p>
					<div class='poll-options'>";
        foreach ($overlay->data->answers as $key => $answer) {
            $key++;
            $content.="<div class='form-group'>
							<div class='row'>
								<div class='col-md-6'>
									<input type='text' class='form-control' name='answer[]' id='answer[]'  placeholder='Your option {$key}' value='{$answer->option}' data-id='{$key}'>
								</div>
								<div class='col-md-6'>
									<strong style='display:block;padding-top:10px;'>{$answer->votes} ".e("Votes")."</strong>
								</div>
							</div>
						</div>";
        }
        $content.="</div>													
					<hr>
					<div class='form-group'>
						<label for='bg'>".e('Background Color')."</label> <br>
						<input type='text' name='bg' id='bg'>
					</div>			
					<div class='form-group'>
						<label for='color'>".e('Text Color')."</label><br>
						<input type='text' name='color' id='color'>
					</div>		
					<div class='form-group'>
						<label for='btnbg'>".e('Button Background Color')."</label><br>
						<input type='text' name='btnbg' id='btnbg'>
					</div>		
					<div class='form-group'>
						<label for='btncolor'>".e('Button Text Color')."</label><br>
						<input type='text' name='btncolor' id='btncolor'>
					</div>									
					<div class='form-group'>
						<label for='position'>".e('Overlay Position')."</label>
						<select name='position' id='position' class='form-control'>
							<option value='bl' ".($position == "bl" ? "selected" : "").">Bottom Left</option>
							<option value='br' ".($position == "br" ? "selected" : "").">Bottom Right</option>							
						</select>
					</div>																													
					".Main::csrf_token(TRUE)."	
					<button class='btn btn-primary'>".e('Save overlay')."</button>
				</form>";

        $header = e("Customize Contact Overlay");
        $before = "";
        $widgets="<div class='panel panel-default panel-body' id='overlay'>
								<h3>".e("Live Preview")."</h3>
								<div class='poll-box' style='color: {$overlay->data->color};background-color:{$overlay->data->bg} !important'>
									<p class='poll-question' style='color: {$overlay->data->color} !important'>{$overlay->data->question}</p>
									<ol class='poll-answers'>";
        foreach ($overlay->data->answers as $key => $answer) {
            $key++;
            $widgets.="<li data-id='{$key}' style='color: {$overlay->data->color} !important'>{$answer->option}</li>";
        }
        $widgets.="</ol>
									<button type='submit' class='poll-btn' style='color: {$overlay->data->btncolor};background-color:{$overlay->data->btnbg} !important'>Vote</button>																
								</div>
							</div><!-- /.custom-overlay -->";
        $widgets.='<div class="panel panel-default panel-body" id="'.__FUNCTION__.'">';
        $widgets.='<h3>'.e("Info").'</h3>';
        $widgets.="<p>".e("An overlay page allows you to display a small non-intrusive overlay on the destination website to advertise your product or your services. You can use this type of overlay to insert a poll to shortened page. Please note that some secured and sensitive websites such as google.com or facebook.com do not work with this feature.")."</p>";
        $widgets.='</div>';

        $this->isUser=TRUE;
        $this->header();
        include($this->t("shared/user_template"));
        $this->footer();
    }
    /**
     * [overlay_delete description]
     * @author KBRmedia <https://gempixel.com>
     * @version 5.6
     * @return  [type] [description]
     */
    private function overlay_delete($id){
        if($this->config["demo"]){
            return Main::redirect("user/settings",array("danger",e("Feature disabled in demo.")));
        }
        if($this->isTeam() && !$this->teamPermission("overlay.delete")){
            return Main::redirect("user",array("danger",e("You do not have this permission. Please contact your team administrator.")));
        }
        // Delete single overlay
        if(!empty($id) && is_numeric($id)){
            // Validated Nonce
            if(Main::validate_nonce("delete_overlay-{$id}")){
                $this->db->update("url",array("type"=>"?"),array("userid"=>"?","type"=>"?"),array("", $this->user->id, "overlay-".$id));
                $this->db->delete("overlay",array("userid"=>"?","id"=>"?"),array($this->user->id,$id));
                return Main::redirect(Main::href("user/overlay","",FALSE),array("success",e("The overlay page has been deleted.")));
            }
        }
        return Main::redirect(Main::href("user/overlay","",FALSE),array("danger",e("Security token expired. Please try again.")));
    }
    /**
     * [tools description]
     * @author KBRmedia <http://gempixel.com>
     * @version 5.6
     * @return  [type] [description]
     */
    protected function tools(){

        if(!$this->config["api"]){
            return Main::href("");
        }

        if($this->permission("api") === FALSE){
            return Main::redirect("upgrade",array("warning",e("Please choose a premium package to unlock this feature.")));
        }

        if($this->isTeam() && !$this->teamPermission("api.create")){
            return Main::redirect("user",array("danger",e("You do not have this permission. Please contact your team administrator.")));
        }

        if($this->id == "regenerate"){
            if(!Main::validate_nonce("regenerate_api")){
                return Main::redirect(Main::href("user/tools#api","",FALSE),array("danger",e("Something went wrong, please try again.")));
            }

            $api = Main::strrand(4).$this->user->id.Main::strrand(7);
            $this->db->update("user", ["api" => $api], ["id" => $this->user->id]);
            return Main::redirect(Main::href("user/tools#api","",FALSE),array("success",e("Your API key has been updated.")));
        }

        // Filter ID
        $this->filter($this->id);
        // Meta regenrate_api
        Main::set("title",e("Tools"));
        // Get Template
        $this->isUser=TRUE;
        $this->header();
        include($this->t(__FUNCTION__));
        $this->footer();
    }
    /**
     * [cancel description]
     * @author KBRmedia <http://gempixel.com>
     * @version 5.3
     * @return  [type] [description]
     */
    protected function cancel(){
        if(!$this->pro()) return Main::redirect(Main::href("user/settings","",FALSE),array("danger",e("Something went wrong, please try again.")));
        if($this->admin()) return Main::redirect(Main::href("user/settings","",FALSE),array("danger",e("Wow there. You are an admin. You can't cancel your membership.")));
        if($this->config["pt"] != "stripe") return Main::redirect(Main::href("user/settings","",FALSE),array("danger",e("Something went wrong, please try again.")));

        if(isset($_POST["token"])){
            if(!Main::validate_csrf_token($_POST["token"])) {
                return Main::redirect(Main::href("user/settings","",FALSE),array("danger",e("Something went wrong, please try again.")));
            }
            $user = $this->db->get("user", ["id" => $this->user->id], ["limit" => 1]);
            $subscription = $this->db->get("subscription", ["userid" => $this->user->id], ["limit" => 1, "order" => "date"]);

            if(!Main::validate_pass($_POST["password"], $user->password)){
                return Main::redirect(Main::href("user/settings","",FALSE),array("danger",e("Your password is incorrect.")));
            }

            include(STRIPE);
            \Stripe\Stripe::setApiKey($this->config["stsk"]);

            if($this->sandbox) \Stripe\Stripe::setVerifySslCerts(false);

            try {

                $Sub = \Stripe\Subscription::retrieve($subscription->tid);

            } catch (Exception $e) {

                return Main::redirect("user/settings",array("danger",e("An error has occured, please contact us.")));
            }

            if($Sub->plan->interval == "yearly"){
                $Inv = \Stripe\Invoice::all(["subscription" => $subscription->tid]);
                $Charge = $Inv->data[0]->charge;
                $Amount = $Inv->data[0]->total / 100;

                $start = $Sub->current_period_start;
                $end = $Sub->current_period_end;

                $yStart = date('Y', $start);
                $yEnd = date('Y', $end);

                $mStart = date('m', $start);
                $mEnd = date('m', $end);

                $diff = (($yEnd - $yStart) * 12) + ($mEnd - $mStart);

                $refund = round(($diff - 1) * $Amount / 12, 2);

                $re = \Stripe\Refund::create(array(
                    "charge" => $Charge,
                    "amount" => $refund * 100
                ));

                $data[":expiry"] = date("Y-m-d H:i:s", strtotime("now"));
                $data[":status"] = "Canceled";
                $data[":reason"] = Main::clean($_POST["reason"], 3, TRUE);
                // Cancel Sub
                $this->db->update("subscription",[], ["id" => $subscription->id], $data);

                $PArray = [
                    ":date"  => "NOW()",
                    ":tid"  => "r_{$subscription->uniqueid}",
                    ":amount"  =>  $refund,
                    ":status"  =>  "Refunded",
                    ":userid"  =>  $this->user->id,
                    ":expiry" =>  NULL,
                    ":data" =>  NULL
                ];
                $this->db->insert("payment", $PArray);
                // Downgrade user
                $this->db->update("user", ["expiration" => $data[":expiry"], "pro" => "0", "planid" => NULL], ["id" => $user->id]);
                $Sub->cancel();

            }else{

                $data[":reason"] = Main::clean($_POST["reason"], 3, TRUE);
                $this->db->update("subscription",[], ["id" => $subscription->id], $data);
                $Sub->cancel_at_period_end = true;
                $Sub->save();
            }
            return Main::redirect("user/settings",array("success",e("Your subscription will be canceled at the end of the billing cycle.")));
        }
        return Main::redirect(Main::href("user/settings","",FALSE),array("danger",e("Something went wrong, please try again.")));
    }
    /**
     * Terminate Account
     * @author KBRmedia <http://gempixel.com>
     * @version 5.6
     */
    protected function terminate(){
        if(!$this->config["allowdelete"]){
            return $this->_404();
        }

        if(isset($_POST["token"])){
            if(!Main::validate_csrf_token($_POST["token"])) {
                return Main::redirect(Main::href("user/settings","",FALSE),array("danger",e("Something went wrong, please try again.")));
            }
            $user = $this->db->get("user", ["id" => $this->user->id], ["limit" => 1]);

            if(!Main::validate_pass($_POST["password"], $user->password)){
                return Main::redirect(Main::href("user/settings","",FALSE),array("danger",e("Your password is incorrect.")));
            }


            $this->db->delete("bundle", ["userid" => $user->id] ); // Delete bundle
            $this->db->delete("splash", ["userid" => $user->id] ); // Delete splash
            $this->db->delete("stats", ["urluserid" => $user->id] ); // Delete stats
            // $this->db->delete("payment", ["userid" => $user->id] ); // Delete payment
            // $this->db->delete("subscription", ["userid" => $user->id] ); // Delete subscription
            $this->db->delete("url", ["userid" => $user->id] ); // Delete url
            $this->db->delete("user", ["id" => $user->id] ); // Delete user
            $this->logout(FALSE);
            return Main::redirect(Main::href("user/login","",FALSE),array("success",e("Your account has been successfully terminated.")));
        }
        return Main::redirect(Main::href("user/settings","",FALSE),array("danger",e("Something went wrong, please try again.")));
    }
    /**
     * Membership page
     * @author KBRmedia <http://gempixel.com>
     * @version 5.6
     */
    protected function membership(){
        if ($this->isTeam()){
            return Main::redirect("user",array("danger",e("You do not have this permission. Please contact your team administrator.")));
        }
        // Filter ID
        $this->filter($this->id);
        // Meta information
        Main::set("title",e("Your membership"));
        // Get Template
        $this->isUser=TRUE;
        $this->header();
        include($this->t(__FUNCTION__));
        $this->footer();
    }
    /**
     * Settings
     * @since 5.7
     **/
    protected function settings(){
        // 2FA
        if(isset($_GET["2FA"])){

            if($_GET["2FA"] == "on"){
                if(!Main::validate_nonce("ON2FA{$this->user->id}")){
                    return Main::redirect(Main::href("user/settings","",FALSE),array("danger",e("Something went wrong, please try again.")));
                }
                include(ROOT."/includes/library/2FA.load.php");

                $gAuth = new \Sonata\GoogleAuthenticator\GoogleAuthenticator();
                $secret = $gAuth->generateSecret();

                $this->db->update("user", ["secret2fa" => $secret], ["id" => $this->user->id]);

                return Main::redirect(Main::href("user/settings","",FALSE),array("success",e("2FA has been activated on your account. Please make sure to backup the secret key or the QR code.")));
            }

            if($_GET["2FA"] == "off"){
                if(!Main::validate_nonce("OFF2FA{$this->user->id}")){
                    return Main::redirect(Main::href("user/settings","",FALSE),array("danger",e("Something went wrong, please try again.")));
                }

                $this->db->update("user", ["secret2fa" => ""], ["id" => $this->user->id]);

                return Main::redirect(Main::href("user/settings","",FALSE),array("success",e("2FA has been disabled on your account.")));
            }

        }

        // Update settings
        if(isset($_POST["token"])){
            if($this->config["demo"]){
                return Main::redirect("user/settings",array("danger",e("Feature disabled in demo.")));
            }
            if(!Main::validate_csrf_token($_POST["token"])) {
                return Main::redirect(Main::href("user/settings","",FALSE),array("danger",e("Something went wrong, please try again.")));
            }
            // Validate email
            if(empty($_POST["email"]) || !Main::email($_POST["email"])) return Main::redirect(Main::href("user/settings","",FALSE),array("danger",e("Please enter a valid email.")));

            // Check if empty is changed
            if($_POST["email"] !== $this->user->email){
                if($this->db->get("user",array("email"=>"?"),array("limit"=>1),array($_POST["email"]))){
                    return Main::redirect(Main::href("user/settings","",FALSE),array("danger",e("An account is already associated with this email.")));
                }
            }

            // Prepare and clean data
            $data= array(
                ":email" => Main::clean($_POST["email"],3,TRUE),
                ":media" => in_array($_POST["media"], array("0","1")) ? Main::clean($_POST["media"],3,TRUE) : 0,
                ":public" => in_array($_POST["public"], array("0","1")) ? Main::clean($_POST["public"],3,TRUE) : 0
            );

            // Validate username
            if(!empty($_POST["username"]) && empty($this->user->username) && $_POST["username"]!==$this->user->username){
                if(!Main::username($_POST["username"])) return Main::redirect(Main::href("user/settings","",FALSE),array("danger",e("Please enter a valid username.")));
                if($this->db->get("user",array("username"=>"?"),array("limit"=>1),array($_POST["username"]))){
                    return Main::redirect(Main::href("user/settings","",FALSE),array("danger",e("This username has already been used. Please try again.")));
                }
                $data[":username"]=$_POST["username"];
            }
            // Check if password is changed
            if(!empty($_POST["password"])){
                if(strlen($_POST["password"])<5) return Main::redirect(Main::href("user/settings","",FALSE),array("danger",e("Password must contain at least 5 characters.")));
                if(empty($_POST["cpassword"]) || $_POST["password"]!==$_POST["cpassword"]) return Main::redirect(Main::href("user/settings","",FALSE),array("danger",e("Passwords don't match.")));
                //Update Password
                $data[":password"]=Main::encode($_POST["password"]);
            }

            if($this->pro() && in_array($_POST["defaulttype"], ["direct","frame","splash","overlay"])){
                $data[":defaulttype"] = Main::clean($_POST["defaulttype"],3,TRUE);
            }

            // Update Users
            $this->db->update("user","",array("id" => $this->userid), $data);
            // Return to settings
            return Main::redirect(Main::href("user/settings","",FALSE),array("success",e("Account has been successfully updated.")));
        }

        Main::hook("sidebar", ["User", "sidebar2FA"]);

        // Filter ID
        $this->filter($this->id);
        // Meta information
        Main::set("title",e("Account Settings"));
        Main::set("description","Edit your account's information.");
        // Get Template
        $this->isUser=TRUE;
        $this->header();
        include($this->t(__FUNCTION__));
        $this->footer();
    }
    /**
     * [sidebar2FA description]
     * @author KBRmedia <https://gempixel.com>
     * @version 5.7
     * @return  [type]      [description]
     */
    public static function sidebar2FA(){
        global $app;

        echo '<div class="panel panel-default panel-body" id="'.__FUNCTION__.'">';
        echo '<h3>'.e("Two-Factor Authentication (2FA)").'</h3>';
        echo '<p>'.e("2FA is an enhanced level security for your account. Each time you login, an extra step where you will need to enter a unique code will be required to gain access to your account. To enable 2FA, please click the button below and download the <strong>Google Authenticator</strong> app from Apple Store or Play Store.").'</p>';
        if(!empty($app->user->secret2fa)){

            echo '<h3>'.e("Important").'</h3><p>'.e("You need to scan the code below with the app. You need to backup the QR code below by saving it and save the key somewhere safe in case you lose your phone. You will not be able to login if you can't provide the code, you will need to contact us. If you disable 2FA and re-enable it, you will need to scan a new code.").'</p>';

            include(ROOT."/includes/library/2FA.load.php");

            $gAuth = new \Sonata\GoogleAuthenticator\GoogleAuthenticator();
            $title = explode(" ", $app->config["title"]);
            $file = \Sonata\GoogleAuthenticator\GoogleQrUrl::generate($title[0], $app->user->secret2fa, $title[0]);
            echo '<p><img src="data:image/jpeg;base64,'.$file.'" width="150"></p>';
            echo '<p><strong>'.e("Secret Key").'</strong>: '.$app->user->secret2fa.'</p>';
            echo '<p><a href="'.Main::nonce("OFF2FA{$app->user->id}").'&2FA=off" class="btn btn-danger">'.e("Disable 2FA").'</a></p>';
        }else{
            echo '<p><a href="'.Main::nonce("ON2FA{$app->user->id}").'&2FA=on" class="btn btn-primary">'.e("Activate 2FA").'</a></p>';
        }
        echo '</div>';
    }

    /**
     * Show Last Payments
     * @since 4.2
     **/
    private function last_payments(){
        if(!$this->config["pro"]) return FALSE;

        if(isset($this->config["pt"]) && $this->config["pt"] == "stripe" && $subscription = $this->db->get("subscription",array("userid"=>"?"),array("order"=>"date"),array($this->userid))){

            $html = '<div class="main-content panel panel-default panel-body">';
            $html .="<h3>".e("Subscription History")."</h3>";
            $html .= '<div class="table-responsive">';
            $html .='<table class="table table-striped">
						        <thead>
						          <tr>
						            <th>'.e("Transaction ID").'</th>
						            <th>'.e("Amount").'</th>
						            <th>'.e("Since").'</th>
						            <th>'.e("Next Payment").'</th>
						            <th>'.e("Status").'</th>
						          </tr>
						        </thead>
						        <tbody>';
            foreach ($subscription as $payment){
                $html .='<tr data-id="'.$payment->id.'">
					              <td>'.$payment->uniqueid.'</td>
					              <td>'.Main::currency($this->config["currency"], $payment->amount).'</td>
					              <td>'.date("d F, Y",strtotime($payment->date)).'</td>
					              <td>'.date("d F, Y",strtotime($payment->expiry)).'</td>
					              <td>'.($payment->status == "Compeleted" ? e("Active") : $payment->status).'</td>
					            </tr>';
            }
            $html .='</tbody>
						      </table>';
            $html .= '</div>';
            $html .='</div>';
            echo $html;
        }

        $payments = $this->db->get("payment",array("userid"=>"?"),array("order"=>"date"),array($this->userid));

        $html = '<div class="main-content panel panel-default panel-body">';
        $html .="<h3>".e("Latest Transactions")."</h3>";
        $html .= '<div class="table-responsive">';
        $html .='<table class="table table-striped">
					        <thead>
					          <tr>
					            <th>'.e("Transaction ID").'</th>
					            <th>'.e("Amount").'</th>
					            <th>'.e("Date").'</th>
					            <th>'.e("Expiration").'</th>
					          </tr>
					        </thead>
					        <tbody>';
        foreach ($payments as $payment){
            $html .='<tr data-id="'.$payment->id.'">
				              <td>'.($payment->status == "Refunded" ? "<span class='label label-success'>".e("Refunded")."</span> ":"").$payment->tid.'</td>
				              <td>'.($payment->status == "Refunded" ? "-" :"").Main::currency($this->config["currency"], $payment->amount).'</td>
				              <td>'.date("d F, Y",strtotime($payment->date)).'</td>
				              <td>'.($payment->status == "Refunded" ? "" : date("d F, Y",strtotime($payment->expiry))).'</td>
				            </tr>';
        }
        $html .='</tbody>
					      </table>';
        $html .= '</div>';
        $html .='</div>';
        echo $html;
    }
    /**
     * User Export URLs
     * @since 5.6
     */
    protected function export(){
        if($this->config["demo"]){
            Main::redirect("user/settings",array("danger",e("Feature disabled in demo.")));
            return;
        }
        if($this->isTeam() && !$this->teamPermission("export.create")){
            return Main::redirect("user",array("danger",e("You do not have this permission. Please contact your team administrator.")));
        }

        if($this->permission("export") === FALSE){
            return Main::redirect("upgrade",array("warning",e("Please choose a premium package to unlock this feature.")));
        }

        if(!empty($this->id)){
            if($url = $this->db->get("url",array("userid"=>"?","id"=>"?"),array("limit"=>1),array($this->user->id,$this->id))){
                return $this->export_data($url->custom.$url->alias);
            }else{
                return Main::redirect("user/edit/{$this->id}",array("danger",e("Data for this url is not available.")));
            }
        }
        if(!Main::validate_nonce("export_url")) return Main::redirect("user/settings",array("danger",e("Security token expired, please try again.")));
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment;filename=URL_Shortener_URLList.csv');
        $result = $this->db->get("url",array("userid"=>$this->user->id),array("order"=>"id","all"=>1));
        echo "Short URL, Long URL, Date, Clicks\n";
        foreach ($result as $url) {
            echo "{$url->domain}/{$url->alias}{$url->custom},{$url->url},{$url->date},{$url->click}\n";
        }
        return;
    }
    /**
     * User Export URLs
     * @since 5.6
     */
    protected function export_data($id){
        if($this->permission("api") === FALSE){
            return Main::redirect("upgrade",array("warning",e("Please choose a premium package to unlock this feature.")));
        }
        if(!Main::validate_nonce("export_url-{$this->id}")) return Main::redirect("user",array("danger",e("Security token expired, please try again.")));
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment;filename=URL_Shortener_'.$id.'_Stats.csv');
        $result = $this->db->get("stats",array("urluserid"=>$this->user->id,"short"=>$id),array("order"=>"id","all"=>1));
        echo "Short URL, Date, IP, Country, Referrer\n";
        foreach ($result as $url) {
            echo "{$url->domain}/{$url->short},{$url->date},{$url->ip},{$url->country},{$url->referer}\n";
        }
        return;
    }
    /**
     * Tracking Pixels
     * @since 5.6
     **/
    protected function pixels(){

        if($this->permission("pixels") === FALSE){
            return Main::redirect("upgrade",array("warning",e("Please choose a premium package to unlock this feature.")));
        }

        if(!empty($this->id) && in_array($this->id, ["save", "delete"])){
            $fn = "pixels_{$this->id}";
            return $this->$fn();
        }

        $fbpixel	= json_decode($this->user->fbpixel, TRUE);
        $adwordspixel	 = json_decode($this->user->adwordspixel, TRUE);
        $linkedinpixel	= json_decode($this->user->linkedinpixel, TRUE);
        $twitterpixel	= json_decode($this->user->twitterpixel, TRUE);
        $adrollpixel	= json_decode($this->user->adrollpixel, TRUE);
        $quorapixel	= json_decode($this->user->quorapixel, TRUE);

        $total = $this->count("user_pixels");


        // Update settings
        if(isset($_POST["token"])){
            if($this->config["demo"]){
                return Main::redirect("user/pixels",array("danger",e("Feature disabled in demo.")));
            }

            if($this->isTeam() && !$this->teamPermission("pixels.create")){
                return Main::redirect("user",array("danger",e("You do not have this permission. Please contact your team administrator.")));
            }

            if($this->permission("pixels") > 0 && $total >= $this->permission("pixels")) return Main::redirect(Main::href("user/pixels","",FALSE),array("danger",e("You have reached your max limit.")));

            if(!Main::validate_csrf_token($_POST["token"])) {
                return Main::redirect(Main::href("user/pixels","",FALSE),array("danger",e("Something went wrong, please try again.")));
            }

            $key = "";

            if($_POST["type"] == "facebook"){
                if(!is_numeric($_POST["tag"]) || (strlen($_POST["tag"]) > 20)) return Main::redirect(Main::href("user/pixels","",FALSE),array("danger",e("Facebook pixel ID is not correct. Please double check.")));
                $key = "fbpixel";
            }

            if($_POST["type"] == "adwords"){
                if((strlen($_POST["tag"]) > 40)) return Main::redirect(Main::href("user/pixels","",FALSE),array("danger",e("Adwords pixel ID is not correct. Please double check.")));
                $key = "adwordspixel";
            }

            if($_POST["type"] == "linkedin"){
                if((strlen($_POST["tag"]) > 10)) return Main::redirect(Main::href("user/pixels","",FALSE),array("danger",e("LinkedIn ID is not correct. Please double check.")));
                $key = "linkedinpixel";
            }

            if($_POST["type"] == "twitter"){
                if((strlen($_POST["tag"]) > 15)) return Main::redirect(Main::href("user/pixels","",FALSE),array("danger",e("Twitter ID is not correct. Please double check.")));
                $key = "twitterpixel";
            }

            if($_POST["type"] == "adroll"){
                if((strlen($_POST["tag"]) > 50)) return Main::redirect(Main::href("user/pixels","",FALSE),array("danger",e("AdRoll ID is not correct. Please double check.")));
                $key = "adrollpixel";
            }

            if($_POST["type"] == "quora"){
                if((strlen($_POST["tag"]) < 30)) return Main::redirect(Main::href("user/pixels","",FALSE),array("danger",e("Quora ID is not correct. Please double check.")));
                $key = "quorapixel";
            }

            // Prepare and clean data

            $cData = json_decode($this->user->{$key}, TRUE);
            if(empty($cData) || $cData == "[]") $cData = [];

            $cData[] = [
                "name" => Main::clean($_POST["name"], 3, TRUE),
                "tag" => Main::clean($_POST["tag"], 3, TRUE)
            ];

            $data = [
                ":$key" => json_encode($cData)
            ];

            // Update Users
            $this->db->update("user","",array("id" => $this->user->id), $data);
            // Return to settings
            return Main::redirect(Main::href("user/pixels","",FALSE),array("success",e("Tracking pixel has been successfully created.")));
        }


        // Meta information
        Main::set("title",e("Tracking Pixels"));
        // Get Template
        $this->isUser=TRUE;
        $this->header();
        include($this->t(__FUNCTION__));
        $this->footer();
    }
    /**
     * [pixels_save description]
     * @author KBRmedia <http://gempixel.com>
     * @version 5.6
     * @return  [type] [description]
     */
    protected function pixels_save(){

        if(!isset($_GET["id"]) || !is_numeric($_GET["id"])) return $this->_404();

        if ($this->isTeam() && !$this->teamPermission("pixels.edit")){
            return Main::redirect("user/pixels",array("danger",e("You do not have this permission. Please contact your team administrator.")));
        }

        $id = Main::clean($_GET["id"], 3, TRUE);

        $key = array_keys($_POST);
        $provider = $key[0];

        if(!isset($_POST[$provider]["name"]) || !isset($_POST[$provider]["tag"])) return Main::redirect(Main::href("user/pixels","",FALSE),array("danger",e("An error occured. Please try again.")));

        if(!isset($this->user->{$provider})) return Main::redirect(Main::href("user/pixels","",FALSE),array("danger",e("An error occured. Please try again.")));

        $cData = json_decode($this->user->{$provider}, TRUE);

        if(!isset($cData[$id])) return $this->_404();

        if($provider == "fbpixel"){
            if(!is_numeric($_POST[$provider]["tag"]) || (strlen($_POST[$provider]["tag"]) > 20)) return Main::redirect(Main::href("user/pixels","",FALSE),array("danger",e("Facebook pixel ID is not correct. Please double check.")));
            $key = "fbpixel";
        }

        if($provider == "adwordspixel"){
            if((strlen($_POST[$provider]["tag"]) > 40)) return Main::redirect(Main::href("user/pixels","",FALSE),array("danger",e("Adwords pixel ID is not correct. Please double check.")));
            $key = "adwordspixel";
        }

        if($provider == "linkedinpixel"){
            if((strlen($_POST[$provider]["tag"]) > 10)) return Main::redirect(Main::href("user/pixels","",FALSE),array("danger",e("LinkedIn ID is not correct. Please double check.")));
            $key = "linkedinpixel";
        }

        if($provider == "twitterpixel"){
            if((strlen($_POST[$provider]["tag"]) > 15)) return Main::redirect(Main::href("user/pixels","",FALSE),array("danger",e("Twitter ID is not correct. Please double check.")));
            $key = "twitterpixel";
        }

        if($provider == "adrollpixel"){
            if((strlen($_POST[$provider]["tag"]) > 40)) return Main::redirect(Main::href("user/pixels","",FALSE),array("danger",e("AdRoll ID is not correct. Please double check.")));
            $key = "adrollpixel";
        }

        if($provider == "quorapixel"){
            if((strlen($_POST[$provider]["tag"]) > 40)) return Main::redirect(Main::href("user/pixels","",FALSE),array("danger",e("AdRoll ID is not correct. Please double check.")));
            $key = "quorapixel";
        }

        $cData[$id] = [
            "name" => Main::clean($_POST[$provider]["name"], 3, TRUE),
            "tag" => Main::clean($_POST[$provider]["tag"], 3, TRUE)
        ];
        $data = [
            ":$key" => json_encode($cData)
        ];

        // Update Users
        $this->db->update("user","",array("id" => $this->user->id), $data);

        return Main::redirect(Main::href("user/pixels","",FALSE),array("success",e("Tracking pixel has been successfully saved.")));
    }
    /**
     * [pixels_delete description]
     * @author KBRmedia <http://gempixel.com>
     * @version 5.6
     * @return  [type] [description]
     */
    protected function pixels_delete(){

        if ($this->isTeam() && !$this->teamPermission("pixels.delete")){
            return Main::redirect("user/pixels",array("danger",e("You do not have this permission. Please contact your team administrator.")));
        }

        if(!isset($_GET["id"]) || !is_numeric($_GET["id"])) return $this->_404();

        $id = Main::clean($_GET["id"], 3, TRUE);

        $type = Main::clean($_GET["type"], 3, TRUE);

        if(!isset($this->user->{$type})) return Main::redirect(Main::href("user/pixels","",FALSE),array("danger",e("An error occured. Please try 
			again.")));

        $cData = json_decode($this->user->{$type}, TRUE);
        if(!isset($cData[$id])) return $this->_404();

        unset($cData[$id]);

        $data = [
            ":$type" => json_encode($cData)
        ];

        // Update Users
        $this->db->update("user","",array("id" => $this->user->id), $data);

        return Main::redirect(Main::href("user/pixels","",FALSE),array("success",e("Tracking pixel has been successfully deleted.")));
    }
    /**
     * [domain description]
     * @author KBRmedia <http://gempixel.com>
     * @version 5.6
     * @return  [type] [description]
     */
    protected function domain(){
        $count = $this->permission("domain");

        if($count  === FALSE){
            return Main::redirect("upgrade",array("warning",e("Please choose a premium package to unlock this feature.")));
        }

        if(is_numeric($this->id)) return $this->domain_delete();

        // Update settings
        if(isset($_POST["token"])){
            if($this->config["demo"]){
                return Main::redirect("user/domain",array("danger",e("Feature disabled in demo.")));
            }

            if ($this->isTeam() && !$this->teamPermission("domain.create")){
                return Main::redirect("user/domain",array("danger",e("You do not have this permission. Please contact your team administrator.")));
            }

            if(!Main::validate_csrf_token($_POST["token"])) {
                return Main::redirect(Main::href("user/domain","",FALSE),array("danger",e("Something went wrong, please try again.")));
            }

            if($count > 0 && $this->db->count("domains","userid='{$this->user->id}'") >= $count) return Main::redirect(Main::href("user/splash","",FALSE),array("danger",e("You have reached your max limit.")));

            if(!empty($this->user->domain) && empty($_POST["domain"])) return $this->domainClear();

            if(empty($_POST["domain"])) return Main::redirect(Main::href("user/domain","",FALSE),array("danger",e("This is not a valid domain name.")));

            if(!empty($_POST["domain"]) && !filter_var($_POST["domain"], FILTER_VALIDATE_URL)) return Main::redirect(Main::href("user/domain","",FALSE),array("danger",e("This is not a valid domain name.")));

            $domain = Main::clean($_POST["domain"], 3, TRUE);

            if($this->db->get("domains", ["domain" => "?"], ["limit" => "1"], [$domain])) return Main::redirect(Main::href("user/domain","",FALSE),array("danger",e("This domain name already exists.")));

            $data = [
                ":userid" => $this->user->id,
                ":domain" => $domain,
                ":redirect" => Main::clean($_POST["default"], 3, TRUE),
                ":status" => "1"
            ];

            $this->db->insert("domains",$data);

            return Main::redirect(Main::href("user/domain","",FALSE),array("success",e("This domain name has been added. It could take up to 36 hours to be updated.")));
        }

        $before = "";

        $header = e("Manage Custom Domain");

        $host = parse_url($this->config["url"]);

        $widgets ='<div class="panel panel-default panel-body" id="'.__FUNCTION__.'">';
        $widgets.='<h3>'.e("How to setup custom domain").'</h3>';
        $widgets.='<p>'.e("If you have a custom domain name that you want to use with our service, you can associate it to your account very easily. Once added, we will add the domain to your account and set it as the default domain name for your URLs. DNS changes could take up to 36 hours.").'</p>';

        $widgets .='<p>'.e("To point your subdomain name, create a CNAME record and set the value to ").' <strong>'.$host["host"].'</strong></p>';
        if($this->config["serverip"]){
            $widgets .='<p>'.e("To point your domain name, create an A record and set the value to ").'<strong>'.$this->config["serverip"].'</strong></p>';
        }
        $widgets.='</div>';

        $domains = $this->db->get("domains", ["userid" => $this->user->id]);

        Main::set("title", $header);
        $this->isUser = TRUE;
        $this->header();
        include($this->t("domain"));
        $this->footer();
    }
    /**
     * [domain_delete description]
     * @author KBRmedia <https://gempixel.com>
     * @version 5.6
     * @return  [type] [description]
     */
    protected function domain_delete(){
        if(empty($this->id) || !is_numeric($this->id)) return Main::redirect(Main::href("user/domain","",FALSE),array("danger",e("This domain name does not exist.")));

        if ($this->isTeam() && !$this->teamPermission("domain.delete")){
            return Main::redirect("user/domain",array("danger",e("You do not have this permission. Please contact your team administrator.")));
        }

        if(Main::validate_nonce("delete_domain-{$this->id}")){
            $this->db->delete("domains", ["id" => $this->id, "userid" => $this->user->id]);
            return Main::redirect(Main::href("user/domain","",FALSE),array("success",e("This domain name has been deleted.")));
        }
        return Main::redirect(Main::href("user/domain","",FALSE),array("danger",e("Security token expired. Please try again.")));
    }

    /**
     * [teams description]
     * @author KBRmedia <https://gempixel.com>
     * @version 5.6.2
     * @return  [type] [description]
     */
    protected function teams(){

        $count = $this->permission("team");

        if($count  === FALSE){
            return Main::redirect("upgrade",array("warning",e("Please choose a premium package to unlock this feature.")));
        }

        if(!empty($this->id) && in_array($this->id, ["add", "remove","edit"])){
            $fn = "teams_{$this->id}";
            return $this->$fn();
        }

        $team = $this->db->get("user", ["teamid" => $this->user->id], ["order" => "id"]);

        Main::set("title", e("Teams"));

        $this->isUser = TRUE;
        $this->header();
        include($this->t(__FUNCTION__));
        $this->footer();
    }

    /**
     * [teams_add description]
     * @author KBRmedia <https://gempixel.com>
     * @version 5.6
     * @return  [type] [description]
     */
    protected function teams_add(){
        // Update settings
        if(isset($_POST["token"])){

            if($this->config["demo"]){
                return Main::redirect("user/teams",array("danger",e("Feature disabled in demo.")));
            }
            if(!Main::validate_csrf_token($_POST["token"])) {
                return Main::redirect(Main::href("user/teams","",FALSE),array("danger",e("Something went wrong, please try again.")));
            }
            if ($this->isTeam()){
                return Main::redirect("user/teams",array("danger",e("You do not have this permission. Please contact your team administrator.")));
            }

            $count = $this->permission("team");

            if($count > 0 && $this->db->count("user","teamid='{$this->user->id}'") >= $count) return Main::redirect(Main::href("user/teams","",FALSE),array("danger",e("You have reached your max limit.")));

            $email = Main::clean($_POST["email"], 3, TRUE);

            if(empty($email) || !Main::email($email)) return Main::redirect(Main::href("user/teams","",FALSE),array("danger",e("This is not a valid domain email address")));

            if($this->db->get("user", ["email" => $email], ["limit" => "1"])){
                return Main::redirect(Main::href("user/teams","",FALSE),array("danger",e("This user has already an account. Please use another email.")));
            }

            if($this->db->get("user", ["email" => $email, "teamid" => $this->user->id], ["limit" => "1"])){
                return Main::redirect(Main::href("user/teams","",FALSE),array("danger",e("This email address has been invited.")));
            }

            $permissions = Main::clean($_POST["permissions"], 3, TRUE);

            $authkey =  Main::encode($this->config["security"].$email.Main::strrand());
            $activate = "{$this->config["url"]}/user/invite/".md5($authkey);

            $data = [
                ":email" => $email,
                ":password" => Main::strrand(26),
                ":api" => Main::strrand(12),
                ":auth_key" => $authkey,
                ":active" => "0",
                ":teamid" => $this->user->id,
                ":teampermission" => json_encode($_POST["permissions"])
            ];

            $this->db->insert("user",$data);

            // Send Email
            $mail["to"] = $email;

            $mail["subject"] = "[{$this->config["title"]}] You have been invited to join.";
            $activate = "{$this->config["url"]}/user/invite/".md5($authkey);

            $mail["message"] = str_replace("{site.title}", $this->config["title"], $this->config["email.invitation"]);
            $mail["message"] = str_replace("{site.link}", $this->config["url"], $mail["message"]);
            $mail["message"] = str_replace("{user.username}", "", $mail["message"]);
            $mail["message"] = str_replace("{user.invite}", $activate, $mail["message"]);
            $mail["message"] = str_replace("http://http", "http", $mail["message"]);
            $mail["message"] = str_replace("{user.email}", $data[":email"], $mail["message"]);
            $mail["message"] = str_replace("{user.date}", date("d-m-Y"), $mail["message"]);

            Main::send($mail);
            return Main::redirect(Main::href("user/teams","",FALSE),array("success",e("An invite has been sent to the email.")));
        }
        return Main::redirect(Main::href("user/teams","",FALSE),array("danger",e("Something went wrong, please try again.")));
    }
    /**
     * [teams_remove description]
     * @author KBRmedia <https://gempixel.com>
     * @version 5.6
     * @return  [type] [description]
     */
    protected function teams_remove(){
        if(!empty($_GET["user"]) && is_numeric($_GET["user"])){
            // Validated Nonce
            if(Main::validate_nonce("delete_team-{$_GET["user"]}")){
                if($user = $this->db->get("user", ["id" => "?", "teamid" => "?"], [], [$_GET["user"], $this->user->id])){
                    $this->db->delete("user",array("id"=>"?", "teamid"=>"?"),array($_GET["user"], $this->user->id));
                    return Main::redirect(Main::href("user/teams","",FALSE),array("success",e("Member has been deleted.")));
                }
            }
        }
        return Main::redirect(Main::href("user/teams","",FALSE),array("danger",e("Something went wrong, please try again.")));
    }
    /**
     * [teams_edit description]
     * @author KBRmedia <https://gempixel.com>
     * @version 5.6.1
     * @return  [type] [description]
     */
    protected function teams_edit(){
        if(!$team = $this->db->get("user", ["teamid" => "?", "id" => "?"], ["limit" => "1"], [$this->user->id, Main::clean($_GET["user"])])){
            return $this->_404();
        }

        if(isset($_POST["token"])){
            if($this->config["demo"]){
                return Main::redirect("user/teams",array("danger",e("Feature disabled in demo.")));
            }
            if(!Main::validate_csrf_token($_POST["token"])) {
                return Main::redirect(Main::href("user/teams","",FALSE),array("danger",e("Something went wrong, please try again.")));
            }
            if ($this->isTeam()){
                return Main::redirect("user/teams",array("danger",e("You do not have this permission. Please contact your team administrator.")));
            }

            $permissions = Main::clean($_POST["permissions"], 3, TRUE);

            $data = [
                ":teampermission" => json_encode($_POST["permissions"])
            ];

            $this->db->update("user", ["teampermission" => json_encode($_POST["permissions"])], ["id" => $team->id]);
            return Main::redirect("user/teams/edit?user={$team->id}",array("success",e("Permissions have been updated.")));
        }

        $before = "";
        $widgets = "";
        $header = e("Edit Team");
        $permissions = json_decode($team->teampermission);
        $content = '<form action="'.Main::href("user/teams/edit?user={$team->id}").'" method="post">
					<div class="row">
						<div class="col-md-6">
							<div class="form-group">
				    		<label class="label-control">'.e("Email").'</label>
				    		<input type="text" class="form-control" value="'.$team->email.'" disabled>								
							</div>
						</div>
						<div class="col-md-6">
							<div class="form-group">
				    		<label for="permissions" class="label-control">'.e("Permissions").'</label>
				    		<select name="permissions[]" class="form-control" data-placeholder="'.e("Permissions").'" multiple>	
				    			<optgroup label="'.e("Links").'">
										<option value="links.create" '.(in_array("links.create", $permissions) ? "selected" : "").'>'.e("Create Links").'</option>
					    			<option value="links.edit" '.(in_array("links.edit", $permissions) ? "selected" : "").'>'.e("Edit Links").'</option>
					    			<option value="links.delete" '.(in_array("links.delete", $permissions) ? "selected" : "").'>'.e("Delete Links").'</option>
				    			</optgroup>';
        if ($this->permission("splash") !== FALSE){
            $content .='<optgroup label="'.e("Splash page").'">
						    			<option value="splash.create" '.(in_array("splash.create", $permissions) ? "selected" : "").'>'.e("Create Splash").'</option>
						    			<option value="splash.edit" '.(in_array("splash.edit", $permissions) ? "selected" : "").'>'.e("Edit Splash").'</option>
						    			<option value="splash.delete" '.(in_array("splash.delete", $permissions) ? "selected" : "").'>'.e("Delete Splash").'</option>				    				
					    			</optgroup>';
        }
        if ($this->permission("overlay") !== FALSE){
            $content .='<optgroup label="'.e("Overlay Page").'">
						    			<option value="overlay.create" '.(in_array("overlay.create", $permissions) ? "selected" : "").'>'.e("Create Overlay").'</option>
						    			<option value="overlay.edit" '.(in_array("overlay.edit", $permissions) ? "selected" : "").'>'.e("Edit Overlay").'</option>
						    			<option value="overlay.delete" '.(in_array("overlay.delete", $permissions) ? "selected" : "").'>'.e("Delete Overlay").'</option>				    				
					    			</optgroup>';
        }
        if ($this->permission("pixels") !== FALSE){
            $content .='<optgroup label="'.e("Pixels").'">
						    			<option value="pixels.create" '.(in_array("pixels.create", $permissions) ? "selected" : "").'>'.e("Create Pixels").'</option>
						    			<option value="pixels.edit" '.(in_array("pixels.edit", $permissions) ? "selected" : "").'>'.e("Edit Pixels").'</option>
						    			<option value="pixels.delete" '.(in_array("pixels.delete", $permissions) ? "selected" : "").'>'.e("Delete Pixels").'</option>				    				
					    			</optgroup>';
        }
        if ($this->permission("domain") !== FALSE){
            $content .='<optgroup label="'.e("Domain").'">
						    			<option value="domain.create" '.(in_array("pixels.create", $permissions) ? "selected" : "").'>'.e("Add Custom Domain").'</option>
						    			<option value="domain.delete" '.(in_array("pixels.delete", $permissions) ? "selected" : "").'>'.e("Delete Custom Domain").'</option>				    				
					    			</optgroup>';
        }
        if ($this->permission("bundle") !== FALSE){
            $content .='<optgroup label="'.e("Bundles").'">
						    			<option value="bundle.create" '.(in_array("bundle.create", $permissions) ? "selected" : "").'>'.e("Create Bundles").'</option>
						    			<option value="bundle.edit" '.(in_array("bundle.edit", $permissions) ? "selected" : "").'>'.e("Edit Bundles").'</option>
						    			<option value="bundle.delete" '.(in_array("bundle.delete", $permissions) ? "selected" : "").'>'.e("Delete Bundles").'</option>				    				
					    			</optgroup>';
        }
        if ($this->permission("api") !== FALSE){
            $content .='<option value="api.create" '.(in_array("api.create", $permissions) ? "selected" : "").'>'.e("Developer API").'</option>';
        }
        if ($this->permission("export") !== FALSE){
            $content .='<option value="export.create" '.(in_array("export.create", $permissions) ? "selected" : "").'>'.e("Export Data").'</option>';
        }
        $content .='</select>			
							</div>
						</div>
					</div>
					'.Main::csrf_token(TRUE).'
					<button class="btn btn-primary" type="submit">'.e("Save").'</button>
				</form>';

        $this->isUser=TRUE;
        $this->header();
        include($this->t("shared/user_template"));
        $this->footer();
    }
}