<?php
// ===========================================================================================
//
// PProfileProcess.php
//
// Updates user password, email or avatar.
// 
// @author Mats Ljungquist
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

// -------------------------------------------------------------------------------------------
//
// Take care of _GET/_POST variables. Store them in a variable (if they are set).
//
$tId        = $pc->POSTisSetOrSetDefault('tId',      '0');
$place 	= $pc->POSTisSetOrSetDefault('place',  '');
$dateFrom 	= $pc->POSTisSetOrSetDefault('dateFrom',  '');
$hourFrom 	= $pc->POSTisSetOrSetDefault('hourFrom',   0);
$minuteFrom = $pc->POSTisSetOrSetDefault('minuteFrom', 0);
$dateTom 	= $pc->POSTisSetOrSetDefault('dateTom',   '');
$hourTom 	= $pc->POSTisSetOrSetDefault('hourTom',    0);
$minuteTom  = $pc->POSTisSetOrSetDefault('minuteTom',  0);
$nrOfRounds = $pc->POSTisSetOrSetDefault('nrOfRounds', 0);
$byeScore	= $pc->POSTisSetOrSetDefault('byeScore',   0);
$tieBreak1 	= $pc->POSTisSetOrSetDefault('tbone',     '');
$tieBreak2	= $pc->POSTisSetOrSetDefault('tbtwo',     '');
$tieBreak3	= $pc->POSTisSetOrSetDefault('tbthree',     '');
$useProxy	= $pc->POSTisSetOrSetDefault('pointFilterCbx', 'false');
$active     = $pc->POSTisSetOrSetDefault('activeFilterCbx', 'false');

$tempOnlyActiveUpdated = empty($dateFrom)
                      && empty($dateTom)
                      && empty($place)
                      && empty($hourFrom)
                      && empty($minuteFrom)
                      && empty($hourTom)
                      && empty($minuteTom)
                      && empty($byeScore)
                      && empty($nrOfRounds)
                      && empty($tieBreak1)
                      && empty($tieBreak2)
                      && empty($tieBreak3)
//                      && empty($useProxy)
                      && !empty($tId);

$log->debug("##### useProxy: " . $useProxy);

if (strcmp($active, "true") != 0) {
    $active = "false";
}

$errorMsg = "Fel: <ul>";
$errorFound = false;
$errorMsgArray = array();

// Create database object (to get the required sql-config)
$db = new CDatabaseController();
$mysqli = $db->Connect();

$place = $mysqli -> real_escape_string($place);

// If only the active property has been updated -> bypass all other controls
if ($tempOnlyActiveUpdated) {
    
    $spChangeActiveTournament = DBSP_ChangeActiveTournament;
    $query = "CALL {$spChangeActiveTournament}({$tId}, {$active});";
    
    $res = $db->MultiQuery($query);
    
    $nrOfStatements = $db->RetrieveAndIgnoreResultsFromMultiQuery();
    
    $log -> debug("Number of statements: " . $nrOfStatements);
    // Must be exactly one successful statement.
    if($nrOfStatements != 1) {
        $errorMsg = "Fel: Det gick inte att uppdatera databasen";
        $errorMsgArray[] = $errorMsg;
        $errorFound = true;
    }
} else {

    if (strcmp($useProxy, "true") != 0) {
        $useProxy = "false";

        // If useProxy is false, then 'orgscore' is not a selectable tie break option.
        if (strcmp($tieBreak1, "orgscore") == 0) {
            $tieBreak1 = "";
        }
        if (strcmp($tieBreak2, "orgscore") == 0) {
            $tieBreak2 = "";
        }
        if (strcmp($tieBreak3, "orgscore") == 0) {
            $tieBreak3 = "";
        }
    }

    // Check incoming data
    $pc->IsNumericOrDie($tId, 0);

    $pc->IsNumericOrDie($nrOfRounds, 0);
    $pc->IsNumericOrDie($byeScore, 0);

    $pc->IsNumericOrDie($hourFrom, 0);
    $pc->IsNumericOrDie($minuteFrom, 0);

    $pc->IsNumericOrDie($hourTom, 0);
    $pc->IsNumericOrDie($minuteTom, 0);

    // Error checking
    if ($hourFrom < 0 || $hourFrom > 23) {
        $errorMsg = "felaktigt värde på timme: {$hourFrom}";
        $errorMsgArray[] = $errorMsg;
        $errorFound = true;
    }
    if ($hourTom < 0 || $hourTom > 23) {
        $errorMsg = "felaktigt värde på timme: {$hourTom}";
        $errorMsgArray[] = $errorMsg;
        $errorFound = true;
    }
    if ($minuteFrom < 0 || $minuteFrom > 59) {
        $errorMsg = "felaktigt värde på minuter: {$minuteFrom}";
        $errorMsgArray[] = $errorMsg;
        $errorFound = true;
    }
    if ($minuteTom < 0 || $minuteTom > 59) {
        $errorMsg = "felaktigt värde på minuter: {$minuteTom}";
        $errorMsgArray[] = $errorMsg;
        $errorFound = true;
    }

    $df = null;
    $dt = null;

    // Try create dates
    try {
        $df = new DateTime($dateFrom . " " . $hourFrom . ":" . $minuteFrom . ":01");
    } catch (Exception $exc) {
        $errorMsg = "Startdatum har fel format. Vänligen kontrollera formatet.";
        $errorMsgArray[] = $errorMsg;
        $errorFound = true;
    }
    try {
        $dt = new DateTime($dateTom . " " . $hourTom . ":" . $minuteTom . ":01"); 
    } catch (Exception $exc) {
        $errorMsg = "Slutdatum har fel format. Vänligen kontrollera formatet.";
        $errorMsgArray[] = $errorMsg;
        $errorFound = true;
    }

    $tbResult = "";

    // -- Tie break validation --
    // 
    // If a tie break exists it must be a valid tie breaker, this is checked against
    // CHTMLHelpers::isSelectableTieBreaker(). If it is ok it will be added to
    // the string of tie breakers which will be sent to the data base.
    // 
    // Also a tie breaker must not be the same as another tiebreker. If so it does
    // not generate an error, it will simply be discarded.
    if (!empty($tieBreak1)) {
        if (CHTMLHelpers::isSelectableTieBreaker($tieBreak1)) {
            $tbResult = $tieBreak1;
        } else {
            $errorMsg = "Tie break 1 har inte ett giltigt värde.";
            $errorMsgArray[] = $errorMsg;
            $errorFound = true;
        }
    }

    if (!empty($tieBreak2) && strcmp($tieBreak1, $tieBreak2) != 0) {
        if (CHTMLHelpers::isSelectableTieBreaker($tieBreak2)) {
            if (!empty($tbResult)) {
                $tbResult = $tbResult . "," . $tieBreak2;
            } else {
                $tbResult = $tieBreak2;
            }
        } else {
            $errorMsg = "Tie break 2 har inte ett giltigt värde.";
            $errorMsgArray[] = $errorMsg;
            $errorFound = true;
        }
    }

    if (!empty($tieBreak3) && strcmp($tieBreak1, $tieBreak3) != 0 && strcmp($tieBreak2, $tieBreak3) != 0) {
        if (CHTMLHelpers::isSelectableTieBreaker($tieBreak3)) {
            if (!empty($tbResult)) {
                $tbResult = $tbResult . "," . $tieBreak3;
            } else {
                $tbResult = $tieBreak3;
            }
        } else {
            $errorMsg = "Tie break 3 har inte ett giltigt värde.";
            $errorMsgArray[] = $errorMsg;
            $errorFound = true;
        }
    }

    // Get current tournament data
    $tournament = CTournament::getInstanceById($db, $tId);

    // Terminate if logged in user is not admin or the creator of the tournament
    $intFilter->IsAdminOrIsCurrentUserOrTerminate($tournament->getCreator()->getId());

    $log->debug("nrofrounds: " . $nrOfRounds . " currround: " . $tournament->getCurrentRound());
    if ($nrOfRounds < $tournament->getCurrentRound()) {
        //$log->debug("HÄÄÄÄÄÄÄÄÄÄÄÄÄÄÄÄÄÄÄÄÄÄÄÄÄÄÄÄÄÄÄÄÄÄÄÄÄÄÄÄÄR: ");
        $errorMsg = "Totalt antal rundor måste vara fler än antalet redan spelade rundor.";
        $errorMsgArray[] = $errorMsg;
        $errorFound = true;
    }
    
    $dateFormat = "Y-m-d H:i:s";

    $spEditSelectedValuesTournament = DBSP_EditSelectedValuesTournament;
    $query = "CALL {$spEditSelectedValuesTournament}({$tId}, '{$place}', {$nrOfRounds}, {$byeScore}, '{$df->format($dateFormat)}', '{$dt->format($dateFormat)}', '{$tbResult}', {$useProxy}, {$active});";

    // Perform the query
    $res = $db->MultiQuery($query);
    
    $nrOfStatements = $db->RetrieveAndIgnoreResultsFromMultiQuery();
    $log -> debug("Number of statements: " . $nrOfStatements);
    
    // Must be exactly one successful statement.
    if($nrOfStatements != 1) {

        $errorMsg = "Fel: Det gick inte att uppdatera databasen";
        $errorMsgArray[] = $errorMsg;
        $errorFound = true;
    }
}

$uo = CUserData::getInstance();
$admin = $uo->isAdmin();

$status = "ok";
$message = "";
$tournamentListJSON = "[]";

if ($errorFound) {
    $status = "error";
    $message = json_encode($errorMsgArray);
} else {
    $tournamentListJSON = CTournamentManager::getTournamentsAsJSON($db, !$admin);
}

$mysqli->close();

$json = <<<EOD
{
	"status": "{$status}",
    "message": "{$message}",
    "tournaments": {$tournamentListJSON},
    "active": {$active}
}
EOD;
echo $json;
exit;

?>