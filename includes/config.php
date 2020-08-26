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
 * @subpackage Configuration File
 */
// Database Configuration
  $dbinfo = array(
    "host" => 'localhost',        // Your mySQL Host (usually Localhost)
    "db" => 'tempmail',            // The database where you have dumped the included sql file
    "user" => 'root',        // Your mySQL username
    "password" => '',    //  Your mySQL Password 
    "prefix" => ''      // Prefix for your tables if you are using same db for multiple scripts, e.g. short_
  );

  $config = array(
    //WebMail
    "incoming" => "mail.moodofthemoon.com",
    "incoming_port" => "993",
    "outgoing" => "mail.moodofthemoon.com",
    "outgoing_port" => "465",
    "tmail_domain" => "moodofthemoon.com",
    "based_email" => "test@moodofthemoon.com", //your current webmail
    "password" => "SprxyBEEs8xc", //your current webmail password
    //Tmail
    "API_KEY" => "Axle8118", //your script api key
    "API_URL" => "http://localhost:8081", //your script api url

    // Your Server's Timezone - List of available timezones (Pick the closest): https://php.net/manual/en/timezones.php
    "timezone" => date_default_timezone_get(),
    // Cache Data - If you notice anomalies, disable this. You should enable this when you get high hits
    "cache" => FALSE,
    // Use CDN to host libraries for faster loading
    "cdn" => TRUE,
    // Enable Compression? Makes your website faster
    "gzip" => TRUE,
    /*
     ====================================================================================
     *  Security Key & Token - Please don't change this if your site is live.
     * ----------------------------------------------------------------------------------
     *  - Setup a security phrase - This is used to encode some important user 
     *    information such as password. The longer the key the more secure they are.
     *
     *  - If you change this, many things such as user login and even admin login will 
     *    fail.
     *
     *  - If the two config below don't have any values or have PUS8a43068f6bfa2c01a05fe0ef8bfb3c8230d4acd988cebcb9d2142e2af7a9dbaf or 74dbb0fa35d9abd4ca8b5593ae4afb66, replace these by a random key.
     ====================================================================================
    */
    "security" => 'PUS8a43068f6bfa2c01a05fe0ef8bfb3c8230d4acd988cebcb9d2142e2af7a9dbaf',  // !!!! DON'T CHANGE THIS IF YOUR SITE IS LIVE !!!!
    "public_token" => '74dbb0fa35d9abd4ca8b5593ae4afb66', // This is randomly generated and it is a public key

    "debug" => 0,   // Enable debug mode (outputs errors) - 0 = OFF, 1 = Error message, 2 = Error + Queries (Don't enable this if your site is live!)
    "demo" => 0 // Demo mode
  );


// Include core.php
include ('core.php');