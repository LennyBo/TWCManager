<?php

$debugLevel = 0;
$twcScriptDir = "/home/pi/TWCxHomeAssistant/TWC/"; //Change that to the locaiton of your TPWManager.py script

$url = "http://192.168.0.221";  //Change that to the url to the index.html

$ipcKey = ftok($twcScriptDir, "T");
$ipcQueue = msg_get_queue($ipcKey, 0666);


if(@$_GET['GetStatus'] == '1'){
    printStatus();
}else if(@$_GET['Boost'] == '1'){
    setHeader();
    ipcCommand("chargeNow");
    header('Location: '.$url);
}else if(@$_GET['CancelBoost'] == '1'){
    setHeader();
    ipcCommand("chargeNowCancel");
    header('Location: '.$url);
}

function setHeader(){
    ipcCommand("setNonScheduledAmps=-1");
    ipcCommand("setScheduledAmps=16 startTime=21:00 endTime=07:00 days=127");
    ipcCommand("setResumeTrackGreenEnergyTime=-1:00");
}



function printStatus(){
    $response = ipcQuery('getStatus');
    if($response != '') {
        $status = explode('`', $response);
        
        $arrayStatus = [];
        $arrayStatus["maxAmpsToDivideAmongSlaves"] = $status[0];
        $arrayStatus["wiringMaxAmpsAllTWCs"] = $status[1];
        $arrayStatus["minAmpsPerTWC"] = $status[2];
        $arrayStatus["chargeNowAmps"] = $status[3];
        $arrayStatus["nonScheduledAmpsMax"] = $status[4];
        $arrayStatus["scheduledAmpsMax"] = $status[5];
        $arrayStatus["scheduledAmpStartTime"] = $status[6];
        $arrayStatus["scheduledAmpsEndTime"] = $status[7];
        $arrayStatus["resumeTrackGreenEnergyTime"] = $status[9];

        if($status[11] < 1) {
            print "</p><p style=\"margin-bottom:0\">";
            print "<strong>No slave TWCs found on RS485 network.</strong>";
        }
        else {
            // Display info about each TWC being managed.
            $arrayStatus["TWC"] = [];
            $numTWCs = $status[11];
            for($i = 0; $i < $numTWCs; $i++) {
                
                $subStatus = explode('~', $status[12 + $i]);
                $arrayStatus["TWC"][$i]["twcModelMaxAmps"] = $subStatus[1];

                if($subStatus[2] < 1.0) {
                    $arrayStatus["TWC"][$i]["twcChargeSpeed"] = "0";
                }
                else {
                    $arrayStatus["TWC"][$i]["twcChargeSpeed"] = $subStatus[2];
                    
                }
                $arrayStatus["TWC"][$i]["twcChargeAvailable"] = $subStatus[3];
            }
        }
        echo json_encode($arrayStatus);
    }
}


function ipcCommand($ipcCommand)
    // Send an IPC command to TWCManager.py.  A command does not expect a
    // response.
    {
        global $ipcQueue, $debugLevel;
        echo "<b>$ipcCommand</b><br>";
        $ipcErrorCode = 0;
        $ipcMsgID = 0;
        $ipcMsgTime = time();

        ipcSend($ipcMsgTime, $ipcMsgID, $ipcCommand);
    }


function ipcQuery($ipcMsgSend, $usePackets = false)
    // Send an IPC query to TWCManager.py and wait for a response which we
    // return.
    {
        global $ipcQueue, $debugLevel;
        $ipcErrorCode = 0;

        // There could be multiple web pages or other interfaces sending queries
        // to TWCManager.py.  To help ensure we get back the response to our
        // particular query, assign a random ID to our query and only accept
        // responses containing the same ID.
        $ipcMsgID = rand(1,65535);

        // Also add a timestamp to our query.  Messages unprocessed for too long
        // will be discarded.
        $ipcMsgTime = time();

        // Send our query
        if(ipcSend($ipcMsgTime, $ipcMsgID, $ipcMsgSend) == false) {
            return '';
        }

        // Wait up to 5 seconds for a response.
        $ipcMsgType = 0;
        $ipcMsgRecv = '';
        $ipcMaxMsgSize = 300;
        $i = 0;
        $maxRetries = 50;
        $numPackets = 0;
        $msgResult = '';
        for(; $i < $maxRetries; $i++) {
            // MSG_NOERROR flag prevents showing an error if there are too many
            // characters and some were lost.
            if(msg_receive($ipcQueue, 1, $ipcMsgType, $ipcMaxMsgSize, $ipcMsgRecv, false,
                           MSG_IPC_NOWAIT | MSG_NOERROR, $ipcErrorCode) == false
            ) {
                // Error 42 means no response is available yet, which is likely to happen
                // briefly.
                if($ipcErrorCode != 42) {
                    print("Message receive failed with error code $ipcErrorCode<br>");
                }
            }
            else {
                $aryMsg = unpack("Ltime/SID/a*msg", $ipcMsgRecv);
                if($debugLevel >= 10) {
                   print "ipcQuery received '" . $aryMsg['msg'] . "', id " . $aryMsg['ID']
                           . ", time " . $aryMsg['time'] . "<p>";
                }

                if($aryMsg['ID'] == $ipcMsgID) {
                    // This response matches our message ID
                    if($usePackets) {
                        if($numPackets == 0) {
                            $numPackets = ord($aryMsg['msg']);
                            if($debugLevel >= 10) {
                                print "ipcQuery numPackets $numPackets<p>";
                            }
                        }
                        else {
                            $msgResult .= $aryMsg['msg'];
                            $numPackets--;
                            if($numPackets == 0) {
                                return $msgResult;
                            }
                        }
                        continue;
                    }
                    else {
                        return $aryMsg['msg'];
                    }
                }
                if(time() - $aryMsg['time'] < 30) {
                    // Message ID doesn't match the ID of our query so this
                    // isn't a response to our query. However, this message is
                    // less than 30 seconds old so another process may still be
                    // waiting for it. Therefore, we put it back at the end of
                    // the message queue.
                    if($debugLevel >= 10) {
                        print "ipcQuery: Put unexpired message back at end of queue.<br>";
                    }
                    ipcSend($aryMsg['time'], $aryMsg['ID'], $aryMsg['msg'], 1);
                }
            }

            // Sleep 1/10 of a second, then check again for a response.
            usleep(100000);
        }

        if($i >= $maxRetries) {
            print "<span style=\"color:#F00; font-weight:bold;\">"
                . "Timed out waiting for response from TWCManager script.</span><p>"
                . "If the script is running, make sure the \$twcScriptDir parameter "
                . "in the source of this web page points to the directory containing "
                . "the TWCManager script.</p><p>";
        }
        return '';
    }

    function ipcSend($ipcMsgTime, $ipcMsgID, $ipcMsg, $ipcMsgType = 2)
    // Help ipcCommand or ipcQuery send their IPC message. Don't call this
    // directly.
    // Most messages we send to TWCManager.py will use $ipcMsgType = 2 while
    // responses to queries will use $ipcMsgType = 1. I picked those values
    // thinking I might use type 1 for responses to queries and values 2 and
    // higher to distinguish different commands or queries but decided to use
    // clear English messages.
    {
        global $ipcQueue, $debugLevel;

        if($debugLevel >= 10) {
            if($debugLevel >= 10) {
                print "ipcQuery sending '" . $ipcMsg . "', id " . $ipcMsgID
                        . ", time " . $ipcMsgTime . "<p>";
            }

            if($debugLevel >= 11) {
                // Print binary bytes in the message if debugging requires.
                print "ipcSend binary message of length " . strlen($ipcMsgSend) . ': ';
                for($i = 0; $i < strlen($ipcMsgSend); $i++) {
                    printf("%02x ", ord(substr($ipcMsgSend, $i, 1)));
                }
                print("<p>");
            }
        }

        if(msg_send($ipcQueue, $ipcMsgType, pack("LSa*", $ipcMsgTime, $ipcMsgID, $ipcMsg),
                    false, false, $ipcErrorCode) == false
        ) {
            print("Couldn't send '$ipcMsgSend'.  Error code $ipcErrorcode.<br><br>");
            return false;
        }
        return true;
    }