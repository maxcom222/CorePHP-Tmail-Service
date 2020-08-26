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
 * @package Theme Functions
 */

if(_VERSION < 5.4) return FALSE;

Main::hook("header", "themeheader");
/**
 * [themeheader description]
 * @author KBRmedia <https://gempixel.com>
 * @version 1.0
 * @param   [type] $user [description]
 * @return  [type]       [description]
 */
function themeheader($user){

	if(!$user) return;

	if(isset($_COOKIE["darkmode"]) && $_COOKIE["darkmode"] == "on"){
		Main::set("body_class", "dark-mode");
	}else{
		Main::set("body_class", "");
	}

	if(isset($_GET["darkmode"]) && $_GET["darkmode"] == "on"){
		Main::cookie("darkmode", "on", 31 * 24 * 60);
		Main::set("body_class", "dark-mode");
		return Main::redirect("user");
	}

	if(isset($_GET["darkmode"]) && $_GET["darkmode"] == "off"){
		Main::cookie("darkmode", "off", -60);
		return Main::redirect("user");
	}

}
/**
 * [isDark description]
 * @author KBRmedia <https://gempixel.com>
 * @version 1.0
 * @return  boolean [description]
 */
function isDark(){
	if(isset($_COOKIE["darkmode"]) && $_COOKIE["darkmode"] == "on") return true;
	return false;
}