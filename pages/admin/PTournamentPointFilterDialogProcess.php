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
$intFilter->UserIsMemberOfGroupAdminOrDie();

// -------------------------------------------------------------------------------------------
//
// Take care of _GET/_POST variables. Store them in a variable (if they are set).
//

$scores	      = $pc->POSTisSetOrSetDefault('scores');
$tournamentId = $pc->POSTisSetOrSetDefault('tournamentId');

// $log -> debug(print_r($scores, true));

$status = "ok";
$message = "";

$scoresDecoded = json_decode($scores);

if ($scoresDecoded != null && is_array($scoresDecoded)) {
    
    $log -> debug("häääär ä vi nu");
    
    $errorFound = false;
    
    if (!is_numeric($tournamentId)) {
        $errorFound = true;
    }
    
    foreach ($scoresDecoded as $value) {
        
        // Sanitize the data
        
        $orgFrom = $value -> orgFrom;
        if (!is_numeric($orgFrom)) {
            $errorFound = true;
            break;
        }
        $orgTom = $value -> orgTom;
        if (!is_numeric($orgTom)) {
            $errorFound = true;
            break;
        }
        if ($orgFrom > $orgTom) {
            $errorFound = true;
            break;
        }
        
        $newFrom = $value -> newFrom;
        if (!is_numeric($newFrom)) {
            $errorFound = true;
            break;
        }
        $newTom = $value -> newTom;
        if (!is_numeric($newTom)) {
            $errorFound = true;
            break;
        }
    }
    
    if (!$errorFound) {

        // Prepare for database access
        $db = new CDatabaseController();
        $mysqli = $db->Connect();
        
        // escape special characters
        $scores = $mysqli->real_escape_string($scores);

        // Get db-function name
        $spSetJsonScoreProxy = DBSP_SetJsonScoreProxyTournament;

        $query = "CALL {$spSetJsonScoreProxy}({$tournamentId}, '{$scores}');";
        $res = $db->MultiQuery($query);

        $nrOfStatements = $db->RetrieveAndIgnoreResultsFromMultiQuery();

        if($nrOfStatements != 1) {
            // Delete not OK
            $log -> debug("ERROR: Update av jsondata på turneringsId '" . $tournamentId . "' gick inte att genomföra");
            $status = "error";
            $message = "Fel: Misslyckades med uppdatering";
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