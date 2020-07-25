<?php
/** RAIDA server
 *
 * guardian_send_email.php
 *
 * Responds to raida and sends emails. 
 *
 * @author Sean H. Worthington 2020
 *
 * @param array $_GET -> nn, sn, ans[], sns[], password;
 *
 * @example 

https://guardian1.cloudcoin.global/service/lost/guardian_send_email.php?

password=47712cfb590e4b1fa8e2f479f287418&raida=17&sns[]=2&ans[]=e4108c63f8625150f28232b31925219e&email=sean@worthington.net

PARAMETERS:
password: the password of the RAIDA Guardian
ans[]: Payment coin Authenticity Number that is a guid without hypends. Lowercase prefered.
sns[] Array of lost coins.
email: The email whose hash has been enbedded in the last quarter of the AN.  

	    
RESPONSE:
	If all the coins were authentic:
            {
				"server":"Guardian1",
				"status":"success",
				"message":"Email was sent",
				"version":"2020-02-13",
				"time":"2020-05-10 05:27:09",
				"ex_time":0.0022242069244384766
			}
			
	If all the password was wrong:
            {
				"server":"Guardian1",
				"status":"fail",
				"message":"Password wrong",
				"version":"2020-02-13",
				"time":"2020-05-10 04:20:40",
				"ex_time":0.0022242069244384766
			}
 */



//START TIMER
$time_pre = microtime(true);


//Errors on or off
$debug = true;
if( $debug ) { 	error_reporting(E_ALL); ini_set('display_errors', 1); }

//show help if no parameters
if(!count($_GET)) {  die(help()); }


//Includes
include 'guardian_node.cfg';
include 'version.inc'; 

//Initialize
$this_node_number = THIS_NODE_NUMBER;
date_default_timezone_set('Etc/UTC');
$date = date("Y-m-d H:i:s");
$max_coins = 400;

$count = 0;
$fromGuardian = GUARDIAN_NAME;
$password = PASSWORD;

validate_GET_Parameters();

$RAIDA = $_GET["raida"];
$email_address_to = $_GET["email"] ;
$subject = "From RAIDA $RAIDA via guardian server $fromGuardian";


$headers = "From: support@cloudcoin.global\r\n";
$headers .= "Reply-To: support@cloudcoin.global\r\n";
$headers .= "Return-Path: support@cloudcoin.global\r\n";


/* HTML Message */
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
$message = '<html><body>';
$message .= '<img src="https://cloudcoin.global/img/RAIDA-logo.png" alt="RAIDA" />';
$message .= '<table rules="all" style="border-color: #666;" cellpadding="10">';
$message .= "<tr><td><strong>RAIDA:</strong> </td><td>$RAIDA</td></tr>";
$message .= "<tr><td><strong>Instructions</strong> </td><td>You will need to put the serial numbers and authenticity numbers into a stack file with at least 20 other ANs from 20 other raida using the standard stack format. Use the <a href='https://cloudcoin.global/recover.html'>Fix tool.</a> to help.</td></tr>";
$message .= "<tr><th>Serial Numbers</th><th>Authenticiy Numbers</th></tr>";
for($i = 0; $i < $count; $i++){
	$message .= "<tr><td>".$_GET['sns'][$i]."</td><td>".$_GET['ans'][$i]."</td></tr>";
}//end for each sn
$message .= "</table>";
$message .= "</body></html>";
/**/

/*
$message = "Dear Patron! \r\n \r\n These is your AN numbers for RAIDA $RAIDA \r\n \r\n You will need to put it into a stack file with at least 20 other ANs from 20 other raida \r\n \r\n Thank you for using CloudCoin \r\n";

for($i = 0; $i < $count; $i++){
	$message .= $_GET['sns'][$i].": ".$_GET['ans'][$i]."\r\n";
}//end for each sn
*/

mail($email_address_to, $subject, $message, $headers);

response("success", "email was sent");

 /* HELPER FUNCTIONS */
 
function validate_GET_Parameters(){
	 	//Check for empty parameters	
		global $max_coins;
		global $count;
		global $password;

		$err = array();
		
		if (empty( $_GET['sns'])) $err[] = "<br>The sn (serial number) of your coins required but was missing.";         
		if (empty( $_GET['ans'])) $err[] = "<br>The an (authenticity number) coins are required but missing.";
		if (empty( $_GET['password'])) $err[] = "<br>A password was required but was missing.";     
		if (empty( $_GET['raida'])) $err[] = "<br>The raida was required but was missing.";  		
		if (empty($_GET["email"])) {
			$err[] = "<br>The email required but was missing.";  
		} else {
			$email = test_input($_GET["email"]);
			// check if e-mail address is well-formed
			if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$err[] = "<br>Invalid email format";
			}
		}
		if (!is_numeric($_GET["raida"])) $err[] = "<br>The raida should be a number between 0 and 24.";   ;
		if( $count > $max_coins  ) $err[] = "<br>Ony $max_coins coins may be attached.";
		if( $password != $_GET['password']) $err[] = "<br>Wrong Password";
		
		if ($err) 
		{
			$status = "missing parameters";
			$message = "";
			
			foreach($err as $line)
			{ 
				$message .= $line	;				
			}//end for each error
			die(response($status, $message));
		}
		
		$count = count( $_GET['sns'] );
		$_GET["email"] =  htmlspecialchars($_GET["email"]);
		$_GET["raida"] =  intval($_GET["raida"]);
		if( $count > $max_coins  ) $err[] = "<br>Ony $max_coins coins may be attached.";
		if( $password != $_GET['password']) $err[] = "Wrong Password";
		
		if ($err) 
		{
			$status = "missing parameters";
			$message = "";
			
			foreach($err as $line)
			{ 
				$message .= $line	;				
			}//end for each error
			die(response($status, $message));
		}
		
		
		
		for($i = 0; $i < $count ; $i++)
		{
			if (!isValidSN( $_GET['sns'][$i] )) $err[] = "<br>At least one sns[] was not valid.";
			if( array_diff_key( $_GET['sns'] , array_unique( $_GET['sns'] ) ))  $err[] = "<br>Duplicate values in the sns[]" ;
			if (!isValidHexStr($_GET['ans'][$i], 32)) $err[] = "<br>At least one an (authenticity number) was not valid.";
			
				if ($err) 
				{
					$status = "Defective parameters";
					$message = "";
					
					foreach($err as $line)
					{ 
						$message .= $line	;				
					}//end for each error
					die(response($status, $message));
				}
		}
	
 }// end validate 
 
function isValidSN(string $sn): bool { global $max_sn; if( is_numeric($sn) && intval($sn)>=1 && intval($sn) <= 16777216 && !in_array($sn,['127','1270','12700','127000','1270000','12700000','1372730'],true)){ return true;}else{return false;}}

function isValidHexStr(string $hexStr, int $length): bool{
	if( strlen($hexStr) === $length && ctype_xdigit($hexStr))
	{ return true;}else{ return false;}
}

/* FUNCTIONS */
function response(string $status, $message){
	global $date;
	global $time_pre;
	global $version;
	global $this_node_number;
	
	$time_GET = microtime(true);
	$exec_time = $time_GET - $time_pre;
	$json_reponse = json_encode([
				'server' => "guardian".$this_node_number,
                'status' => $status,
                'message' => $message,
                'version' => VERSION,
                'time' => $date,
				'ex_time' => $exec_time
            ]);
			die($json_reponse );
}

function test_input($data) {
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data);
  return $data;
}

function help(){
	
	echo "This service is reserved for RAIDA machines.";
}//end help

?>
