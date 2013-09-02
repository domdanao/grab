<?php
##################################################
##################################################
### CONFIGURATION FILE FOR GLOBE GRAB SERVICE ####
##################################################
##################################################

// AS OF August 19, 2013, 0925

##################################################
// Make sure to set the timezone
date_default_timezone_set('Asia/Manila');


##################################################
// Required files, make sure these are in the same directory
require 'database.php';
require 'functions.php';


##################################################
// Operator
$OPERATOR = 'GLOBE';


##################################################
// Main keywords
// These are the allowed keywords
// So if subscriber sends GRABE <ITEM>, still valid
// Populate as necessary
$KEYWORDS_MAIN = array(
	'grab',
	'grabs',
	'grabe',
	'grav'
	);

$KEYWORDS_PARAM = array(
	'grab'  => array( 'grab','grabs','grabe','grav' ),
	'bag'   => array( 'bag','info', 'inf0' ),
	'time'  => array( 'time','stat' ),
	'on'    => array( 'on','start','go','subscribe' ),
	'off'   => array( 'off','stop','quit','cancel','unsubscribe' ),
	'help'  => array( 'help' ),
	'reg'	=> array( 'reg' ),
	'unli'	=> array( 'unli' )
	);


##################################################
// LOGGING
$LOGGING = 0; // TODO: Change to active logging (0 means no logging; 1 means with logging)


##################################################
// Shortcodes
$INLA = '2889';
$OUTLA = '28891';


##################################################
// VIRTUAL HOST DIRECTORY
// For Unix, OSX, Linux systems
// $VHROOT = '/Applications/MAMP/htdocs/zed';
// $BASE_URL = "/zed";
// For Windows
$VHROOT = 'C:/wamp/apps/grab';
$BASE_URL = "/globe";


##################################################
// Log files
$LOGSROOT = 'C:/wamp/logs/grab';
$MAIN_LOG = $LOGSROOT . '/grab_' . date( "Ymd" ) . '.log';


##################################################
// Inbound Messages Log
$IN_LOG = $LOGSROOT . '/grab_mo_' . date ( "Ymd" ) . '.log';


##################################################
// Charging Log
$CHG_LOG = $LOGSROOT . '/grab_charge_'.date( "Ymd" ).'.log';


##################################################
// Outbound Messages Log
$OUT_LOG = $LOGSROOT . '/grab_mt_'.date( "Ymd" ) . '.log';


##################################################
// Program host
$PROG_HOST = 'localhost'; #$_SERVER['SERVER_ADDR'];
$PROG_PORT = 8888;
$PROG_BASE = '/grab';


##################################################
// KEYWORD URLs

// URL for keyword BAG
$BAG_PATH = $BASE_URL . "/grab_bag.php";

// URL for keyword REG
$REG_PATH = $BASE_URL . "/grab_reg.php";

// URL for keyword HELP
$HELP_PATH = $BASE_URL . "/grab_help.php";

// URL for keyword TIME
$TIME_PATH = $BASE_URL . "/grab_time.php";

// URL for keyword ON/OFF
$SUBSCRIBE_PATH = $BASE_URL . "/grab_subscribe.php";

// URL for keyword UNLI
$UNLI_PATH = $BASE_URL . "/grab_unli.php";

// Default URL
$DEFAULT_PATH = $BASE_URL . "/grab_main.php";


##################################################
// Boilerplate texts
$BP1 = "[P2.50/txt]";
$BP2 = "[P2.50/grab]";


##################################################
// REG instruction
$NOT_REG = "\n\nPara mabili mo ang item for P88, mag-member ka muna for free! Text GRAB REG name/age/address to $INLA.Ex.: GRAB REG MARK SY/25/8 Apo St, QC.";


##################################################
// GRAB instruction
$HOW_TO_GRAB = "\n\nGrab an item u want by texting GRAB <item> to $INLA. $BP2";


##################################################
// WELCOME MESSAGE
$WELCOME_MSG = "Welcome to GRAB A GADGET PROMO! Participating in GRAB means you have read and agree to the Terms and Conditions found at the web site <url> also means you agree to receive free alerts of this service.\n\nFor more info txt HELP GRAB to $INLA.\n\nDTI XXXX\nCall 7065278. $BP1";


##################################################
### SENDSMS URL and PARAMETERS
### This is for Globe SMS MT service

$SENDSMS = array(
	'url'		=> 'http://180.87.143.49/globe/sendsms.php',
	'mo_id'		=> '',			// MO_ID, MANDATORY
	'txid'		=>	'',			// TXID, MANDATORY
	'mobtel'	=>	'',			// MOBTEL, MANDATORY
	'message'	=>	''			// MESSAGE, MANDATORY
	);



##################################################
### CHARGING URL and PARAMETERS
### This is for Globe RT Billing service

$SENDCHARGE = array(
	'url'		=>	'http://180.87.143.49/globe/sendcharge.php',
	'mo_id'		=>	'',			// MO_ID, MANDATORY
	'txid'		=>	'',			// TXID, MANDATORY
	'mobtel'	=>	'',			// MOBTEL, MANDATORY
	'charge'	=>	0			// CHARGE AMOUNT, MANDATORY
	);


##################################################
// print "Good";
?>