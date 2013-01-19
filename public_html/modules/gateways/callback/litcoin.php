<?php

# Required File Includes
include("../../../dbconnect.php");
include("../../../includes/functions.php");
include("../../../includes/gatewayfunctions.php");
include("../../../includes/invoicefunctions.php");

$gatewaymodule = "litecoin"; # Enter your gateway module name here replacing template

$GATEWAY = getGatewayVariables($gatewaymodule);
if (!$GATEWAY["type"]) die("Module Not Activated"); # Checks gateway module is active before accepting callback
    # Gateway Specific Variables
	$u = $GATEWAY['username'];
	$p = $GATEWAY['password'];
	$h = $GATEWAY['host'].':'.$GATEWAY['port'];
	$rpc = 'http://'.$u.':'.$p.'@'.$h;
	# Build Litecoin Information Here
	require_once '../litcoin/jsonRPCClient.php';
	$litecoin = new jsonRPCClient($rpc); 
	if(!$litecoin->getinfo()){
		die('could not connect to litcoind');
	}
	
$sql = 'SELECT * FROM tblinvoices WHERE paymentmethod="'.$gatewaymodule.'" AND status = "Unpaid"';
$results = mysql_query($sql);
while($result = mysql_fetch_array($results)){
	$amount = $result['total'];
	$btcaccount = $result['userid'].'-'.$result['id'];
	$received = $litecoin->getbalance($btcaccount);
	//print($received);
	if($amount <= $received){
		//echo 'PAID';
		$fee = 0;
		$transid = $litecoin->getaccountaddress($btcaccount.'-'.$result['id']);
		//checkCbTransID($transid); 
		addInvoicePayment($result['id'],$transid,$received,$fee,$gatewaymodule);
		logTransaction($GATEWAY["name"],array('address'=>$transid,'amount'=>$received),"Successful");
	}
	else{
		//echo 'Still Owes: '.$amount;
	}
}

?>
