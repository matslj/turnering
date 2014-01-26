<?php
// ===========================================================================================
//
// File: PPairingOfMatchesProcess.php
//
// Description: Handles ajax calls on updating scores.
//
// Author: Mats Ljungquist
//

$log = logging_CLogger::getInstance(__FILE__);
// -------------------------------------------------------------------------------------------
//
// Get pagecontroller helpers. Useful methods to use in most pagecontrollers
//
$pc = CPageController::getInstance();

// -------------------------------------------------------------------------------------------
//
// Interception Filter, controlling access, authorithy and other checks.
//
$intFilter = new CInterceptionFilter();

$intFilter->FrontControllerIsVisitedOrDie();
$intFilter->UserIsSignedInOrRecirectToSignIn();

// Get user-object
$uo = CUserData::getInstance();
$userId = $uo -> getId();

// -------------------------------------------------------------------------------------------
//
// Take care of _GET/_POST variables. Store them in a variable (if they are set).
//
$incomingData	= $pc->POSTisSetOrSetDefault('status');

// $log -> debug(print_r($scores, true));

$status = "ok";
$message = "";
$participantListJSON = "[]";

$incomingDataDecoded = json_decode($incomingData);

if ($incomingDataDecoded != null) {

    $errorFound = false;
    
    // -------------------------------------------------------------------------
    // - Sanitize the data
    // - 
    $dTournamentId = $incomingDataDecoded -> tournamentId;
    if (!is_numeric($dTournamentId)) {
        $errorFound = true;
    }
    
    require_once(TP_SQLPATH . 'config.php');
    $userAction = "";
    if (strcmp($incomingDataDecoded -> action, "join") == 0) {
        $userAction = DBSP_JoinTournament;
    } else if (strcmp($incomingDataDecoded -> action, "leave") == 0) {
        $userAction = DBSP_LeaveTournament;
    }
    
    if (empty($userAction)) {
        $errorFound = true;
    }
    
    $log->debug("userAction: " . $userAction);
    
    // -------------------------------------------------------------------------
    // - If no errors were found -> proceed with db call
    // -
    if (!$errorFound) {
        
        // Prepare for database access
        $db = new CDatabaseController();
        $mysqli = $db->Connect();

        $query = "CALL {$userAction}({$dTournamentId}, {$userId});";
        $log->debug(" --- the query: " . $query);
        $res = $db->MultiQuery($query);

        $nrOfStatements = $db->RetrieveAndIgnoreResultsFromMultiQuery();

        if($nrOfStatements < 1) {
            $status = "error";
            $message = "Fel: Misslyckades med kommandot: '{$incomingDataDecoded -> action}' för turneringsId: '{$dTournamentId}'";
        } else {
            $participantListJSON = CTournamentManager::getParticipantList($db, $dTournamentId);
        }

        $mysqli->close();
    } else {
        $status = "error";
        $message = "Fel: Fel format på inkommande data.";
    }
} else {
    $status = "error";
    $message = "Fel: Indata saknas";
}

$json = <<<EOD
{
	"status": "{$status}",
    "message": "{$message}",
    "participants": {$participantListJSON},
    "action": "{$incomingDataDecoded -> action}"
}
EOD;
echo $json;
exit;

?>