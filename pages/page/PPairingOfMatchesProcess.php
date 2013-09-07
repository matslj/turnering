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
$scores	= $pc->POSTisSetOrSetDefault('scores');

// $log -> debug(print_r($scores, true));

$status = "ok";
$message = "";

$scoresDecoded = json_decode($scores);

if ($scoresDecoded != null && is_array($scoresDecoded)) {
    
    $log -> debug("häääär ä vi nu");
    
    $errorFound = false;
    
    foreach ($scoresDecoded as $value) {
        
        // Sanitize the data
        
        $matchId = $value -> matchId;
        if (!is_numeric($matchId)) {
            $errorFound = true;
            break;
        }
        $playerOneScore = $value -> playerOneScore;
        if (!is_numeric($playerOneScore)) {
            $errorFound = true;
            break;
        }
        $playerTwoScore = $value -> playerTwoScore;
        if (!is_numeric($playerTwoScore)) {
            $errorFound = true;
            break;
        }  
    }
    
    $log -> debug("häääär ä vi nu då");
    
    if (!$errorFound) {
    
        $max = count($scoresDecoded);

        // Prepare for database access
        $db = new CDatabaseController();
        $mysqli = $db->Connect();

        // Get db-function name
        $spUpdateMatchScore = DBSP_UpdateMatchScore;

        $log -> debug("där och max: " . $max);
    
        foreach ($scoresDecoded as $value) {
            $matchId = $value -> matchId;
            $playerOneScore = $value -> playerOneScore;
            $playerTwoScore = $value -> playerTwoScore;
            
            $query = "CALL {$spUpdateMatchScore}({$matchId}, {$playerOneScore}, {$playerTwoScore});";
            $res = $db->MultiQuery($query);
            
            $nrOfStatements = $db->RetrieveAndIgnoreResultsFromMultiQuery();
        
            if($nrOfStatements != 1) {
                // Delete not OK
                $log -> debug("ERROR: Update av matchid '" . $matchId . "' gick inte att genomföra");
                $status = "error";
                $message = "Fel: Misslyckades med uppdatering";
            }
        }
        
        $mysqli->close();
    } else {
        $status = "error";
        $message = "Fel: Fel format på inkommande data";
    }
} else {
    $status = "error";
    $message = "Fel: Indata saknas";
}

$json = <<<EOD
{
	"status": "{$status}",
    "message": "{$message}"
}
EOD;
echo $json;
exit;

?>