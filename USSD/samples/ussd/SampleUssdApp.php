<?php
/**
 *   (C) Copyright 1997-2013 hSenid International (pvt) Limited.
 *   All Rights Reserved.
 *
 *   These materials are unpublished, proprietary, confidential source code of
 *   hSenid International (pvt) Limited and constitute a TRADE SECRET of hSenid
 *   International (pvt) Limited.
 *
 *   hSenid International (pvt) Limited retains all title to and intellectual
 *   property rights in these materials.
 */

include_once '../../lib/ussd/MoUssdReceiver.php';
include_once '../../lib/ussd/MtUssdSender.php';
include_once '../log.php';
//include_once '../conn.php';

ini_set('error_log', 'ussd-app-error.log');

$receiver = new MoUssdReceiver(); // Create the Receiver object

$receiverSessionId = $receiver->getSessionId();
session_id($receiverSessionId); //Use received session id to create a unique session
session_start();

$content = $receiver->getMessage(); // get the message content
$address = $receiver->getAddress(); // get the sender's address
$requestId = $receiver->getRequestID(); // get the request ID
$applicationId = $receiver->getApplicationId(); // get application ID
$encoding = $receiver->getEncoding(); // get the encoding value
$version = $receiver->getVersion(); // get the version
$sessionId = $receiver->getSessionId(); // get the session ID;
$ussdOperation = $receiver->getUssdOperation(); // get the ussd operation



logFile("[ content=$content, address=$address, requestId=$requestId, applicationId=$applicationId, encoding=$encoding, version=$version, sessionId=$sessionId, ussdOperation=$ussdOperation ]");

//your logic goes here......
$responseMsg = array(
    "main" => " Welcome to Helping Hands.. 
                    1.About Helping Hands
                    2.Inform Disaster
                    000.Exit",
    "About" => "Helping Hands is Web based Disaster managemnet system.
                    999.Back",
    
    "Inform" => "1.Enter Your Details 
                 2.Enter Disaster Details
                     999.back",
    "Personal" => "Enter Your Name*NIC",
    "Disaster" => "Enter Disaster Type*Disaster Location*number of victims"
    
   );

logFile("Previous Menu is := " . $_SESSION['menu-Opt']); //Get previous menu number
if (($receiver->getUssdOperation()) == "mo-init") { //Send the main menu
    loadUssdSender($sessionId, $responseMsg["main"]);
    if (!(isset($_SESSION['menu-Opt']))) {
        $_SESSION['menu-Opt'] = "main"; //Initialize main menu
    }

}
if (($receiver->getUssdOperation()) == "mo-cont") {
    $menuName = null;

    switch ($_SESSION['menu-Opt']) {
        case "main":
            switch ($receiver->getMessage()) {
                 case "1":
                    $menuName = "About";
                    break;
                case "2":
                    $menuName = "Inform";
                    break;
                default:
                    $menuName = "main";
                    break;
            }
            $_SESSION['menu-Opt'] = $menuName; //Assign session menu name
            break;
    
      
        
        case "Inform":
            $_SESSION['menu-Opt'] = "Inform-hist"; 
            switch ($receiver->getMessage()) {
                case "1":
                    $menuName = "Personal";
                    break;
                case "2":
                    $menuName = "Disaster";
                    break;
                case "999":
                    $menuName = "main";
                    $_SESSION['menu-Opt'] = "main";
                    break;
                default:
                    $menuName = "main";
                    break;
            }
            break;

    case "Personal":
            $data = $receiver->getMessage();
            connectionPersonal($data);
            break;
    case "Disaster":
            $data = $receiver->getMessage();
            connectionDisaster($data);
            break;
    

    

    case "Inform-hist" ||  "Registered-hist":
            switch ($_SESSION['menu-Opt']) { //Execute menu back sessions
                case "Inform-hist":
                    $menuName = "Inform";
                    break;
               
                case "Registered-hist":
                    $menuName = "Registered";
                    break;
                
            }
            $_SESSION['menu-Opt'] = $menuName; //Assign previous session menu name
            break;

       
    }


    if ($receiver->getMessage() == "000") {
        $responseExitMsg = "Exit Program!";
        $response = loadUssdSender($sessionId, $responseExitMsg);
        session_destroy();
    } else {
        logFile("Selected response message := " . $responseMsg[$menuName]);
        $response = loadUssdSender($sessionId, $responseMsg[$menuName]);
    }



}
/*
    Get the session id and Response message as parameter
    Create sender object and send ussd with appropriate parameters
**/

function loadUssdSender($sessionId, $responseMessage)
{
    $password = "password";
    $destinationAddress = "tel:94771122336";
    if ($responseMessage == "000") {
        $ussdOperation = "mt-fin";
    } else {
        $ussdOperation = "mt-cont";
    }
    $chargingAmount = "5";
    $applicationId = "APP_000001";
    $encoding = "440";
    $version = "1.0";

    try {
        // Create the sender object server url

//        $sender = new MtUssdSender("http://localhost:7000/ussd/send/");   // Application ussd-mt sending http url
        $sender = new MtUssdSender("https://localhost:7443/ussd/send/"); // Application ussd-mt sending https url
        $response = $sender->ussd($applicationId, $password, $version, $responseMessage,
            $sessionId, $ussdOperation, $destinationAddress, $encoding, $chargingAmount);
        return $response;
    } catch (UssdException $ex) {
        //throws when failed sending or receiving the ussd
        error_log("USSD ERROR: {$ex->getStatusCode()} | {$ex->getStatusMessage()}");
        return null;
    }
}

function connectionPersonal($data){ 
    $db_name ="disasterdb";
    $user_name="root";
    $password="";
    $servername="localhost";
     $conn=mysqli_connect($servername,$user_name,$password,$db_name) or die("connection fail");

           $details = explode('*',$data);
            $name = $details[0];
            $nic = $details[1];
            $userQuery = "INSERT INTO `users`(`name`, `nic`) VALUES ('$name','$nic')";
            mysqli_query($conn,$userQuery);
             

            
      
}
function connectionDisaster($data){
    $db_name ="disasterdb";
    $user_name="root";
    $password="";
    $servername="localhost";
     $conn=mysqli_connect($servername,$user_name,$password,$db_name) or die("connection fail");
     $details = explode('*',$data);
            $DisasterType = $details[0];
            $DisasterLocation = $details[1];
            $NumberOfVictims= $details[2];
            
    $date = date("Y-m-d");

            $disasterQuery="INSERT INTO `disasters`(`disaster_type`, `disaster_date`, `location`,`num_of_victims`, `status`) VALUES ('$DisasterType','$date','$DisasterLocation','$NumberOfVictims','1')";
            mysqli_query($conn,$disasterQuery);

      
}



?>
