<?php
##################################################
##################################################
### CONFIGURATION FILE FOR GLOBE GRAB SERVICE ####
##################################################
##################################################


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

// For checking unli subscription validity
$KEYWORDS_UNLI = array(
	'check',
	'time',
	'balance',
	'bal',
	'validity'
	);


##################################################
// LOGGING
$LOGGING = 0; // TODO: Change to active logging (0 means no logging; 1 means with logging)


##################################################
// Shortcodes
$INLA = '2889';
$OUTLA = '28891';


##################################################
// Program host
$PROG_HOST = 'grab.stickr.ph'; #$_SERVER['SERVER_ADDR'];
$PROG_PORT = 80;
$PROG_BASE = '/globe';


##################################################
// KEYWORD URLs

// URL for keyword BAG
$BAG_PATH = $PROG_BASE . "/grab_bag.php";

// URL for keyword REG
$REG_PATH = $PROG_BASE . "/grab_reg.php";

// URL for keyword HELP
$HELP_PATH = $PROG_BASE . "/grab_help.php";

// URL for keyword TIME
$TIME_PATH = $PROG_BASE . "/grab_time.php";

// URL for keyword ON/OFF
$SUBSCRIBE_PATH = $PROG_BASE . "/grab_subscribe.php";

// URL for keyword UNLI
$UNLI_PATH = $PROG_BASE . "/grab_unli.php";

// Default URL
$DEFAULT_PATH = $PROG_BASE . "/grab_main.php";

// TESTING URL
$TEST_PATH = $PROG_BASE . "/grab_test.php";


##################################################
// Boilerplate texts
$BP1 = "P1.00/txt";
$BP2 = "P1.00/grab";
$BP3 = "P10/24h";


##################################################
// REG instruction
$NOT_REG = "\n\nPara mabili mo ang item for P88, mag-member ka muna for free! Text GRAB REG name/age/address to $INLA.Ex.: GRAB REG MARK SY/25/8 Apo St, QC.";


##################################################
// GRAB instruction
$HOW_TO_GRAB = "\n\nGrab an item u want by texting GRAB <item> to $INLA. $BP2";


##################################################
// WELCOME MESSAGE
//$WELCOME_MSG = "Welcome to GRAB A GADGET PROMO! Participating in GRAB means you have read and agree to the Terms and Conditions found at the web site <url> also means you agree to receive free alerts of this service.\n\nFor more info txt HELP GRAB to $INLA.\n\nDTI6597\nCall 7065278. $BP1";
$WELCOME_MSG = "Welcome to GRAB A GADGET PROMO! Available commands:\n
	GRAB REG name/age/address - to be a member and buy items for P88 only\n
	GRAB <ITEM> - grab an item\n
	GRAB UNLI - unlimited grab 24hrs\n
	GRAB BAG - list the current items up for grabs all for 88 pesos!\n
	GRAB TIME - know how much time u are holding the item DTI6597Promo Pd 09/22/13-10/19/13 Call 7065278\n".
	$BP1 . $REGMSG;
	

##################################################
### SENDSMS URL and PARAMETERS
### This is for Globe SMS MT service

$SENDSMS = array(
	'url'		=> 'http://180.87.143.49:8888/grab/globe/sendsms.php',
	'parameters'	=> array(
		'mo_id'		=> '',			// MO_ID, MANDATORY
		'txid'		=>	'',			// TXID, MANDATORY
		'mobtel'	=>	'',			// MOBTEL, MANDATORY
		'message'	=>	''			// MESSAGE, MANDATORY
		)
	);



##################################################
### CHARGING URL and PARAMETERS
### This is for Globe RT Billing service

$SENDCHARGE = array(
	'url'		=>	'http://180.87.143.49:8888/grab/globe/sendcharge.php',
	'parameters'		=>	array(
		'mo_id'		=>	'',			// MO_ID, MANDATORY
		'txid'		=>	'',			// TXID, MANDATORY
		'mobtel'	=>	'',			// MOBTEL, MANDATORY
		'charge'	=>	0			// CHARGE AMOUNT, MANDATORY
		)
	);


##################################################
$PROGRAM_OWNER = '639152481296';

##################################################
$BAN_TIME_START = '12:00:01 am';
$BAN_TIME_END = '6:59:59 am';
$BAN_MSG = "GRAB: Sorry, walang grab ngayong oras. Nagsisimula ang grab time nang " . date("g:i:s a", strtotime("$BAN_TIME_END + 1sec")) . ". Balik ka later!";

// print "Good";
?>