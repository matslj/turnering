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
// Check so that logged in user is admin
$intFilter->IsUserMemberOfGroupAdminOrTerminate();

// -------------------------------------------------------------------------------------------
//
// Take care of _GET/_POST variables. Store them in a variable (if they are set).
//
$tId        = $pc->POSTisSetOrSetDefault('tId',      '0');
$dateFrom 	= $pc->POSTisSetOrSetDefault('dateFrom',  '');
$hourFrom 	= $pc->POSTisSetOrSetDefault('hourFrom',   0);
$minuteFrom = $pc->POSTisSetOrSetDefault('minuteFrom', 0);
$dateTom 	= $pc->POSTisSetOrSetDefault('dateTom',   '');
$hourTom 	= $pc->POSTisSetOrSetDefault('hourTom',    0);
$minuteTom  = $pc->POSTisSetOrSetDefault('minuteTom',  0);
$nrOfRounds = $pc->POSTisSetOrSetDefault('nrOfRounds', 0);
$byeScore	= $pc->POSTisSetOrSetDefault('byeScore',   0);
$tieBreak1 	= $pc->POSTisSetOrSetDefault('tbone',   '');
$tieBreak2	= $pc->POSTisSetOrSetDefault('tbtwo',   '');

// Check incoming data
$pc->IsNumericOrDie($tId, 0);

$pc->IsNumericOrDie($nrOfRounds, 0);
$pc->IsNumericOrDie($byeScore, 0);

$pc->IsNumericOrDie($hourFrom, 0);
$pc->IsNumericOrDie($minuteFrom, 0);

$pc->IsNumericOrDie($hourTom, 0);
$pc->IsNumericOrDie($minuteTom, 0);

$errorMsg = "Fel: <ul>";
$errorFound = false;
$errorMsgArray = array();

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

// Create database object (to get the required sql-config)
$db = new CDatabaseController();
$mysqli = $db->Connect();

// Get current tournament data
$tournament = CTournament::getInstanceById($db, $tId);
$log->debug("nrofrounds: " . $nrOfRounds . " currround: " . $tournament->getCurrentRound());
if ($nrOfRounds < $tournament->getCurrentRound()) {
    $log->debug("HÄÄÄÄÄÄÄÄÄÄÄÄÄÄÄÄÄÄÄÄÄÄÄÄÄÄÄÄÄÄÄÄÄÄÄÄÄÄÄÄÄR: ");
    $errorMsg = "Totalt antal rundor måste vara fler än antalet redan spelade rundor.";
    $errorMsgArray[] = $errorMsg;
    $errorFound = true;
}

if ($errorFound) {
    $mysqli->close();
    $json = json_encode($errorMsgArray);
echo $json;
exit;
}

$dateFormat = "Y-m-d H:i:s";

$spEditSelectedValuesTournament = DBSP_EditSelectedValuesTournament;
$query = "CALL {$spEditSelectedValuesTournament}({$tId}, {$nrOfRounds}, {$byeScore}, '{$df->format($dateFormat)}', '{$dt->format($dateFormat)}', '{$tbResult}');";

// Perform the query
$res = $db->MultiQuery($query);
$nrOfStatements = $db->RetrieveAndIgnoreResultsFromMultiQuery();
$log -> debug("Number of statements: " . $nrOfStatements);
// Must be exactly one successful statement.
if($nrOfStatements != 1) {
    $_SESSION['errorMessage']	= "Fel: Det gick inte att uppdatera databasen";
}

$mysqli->close();

// -------------------------------------------------------------------------------------------
//
// Redirect to another page
//
$pc->RedirectTo($pc->POSTisSetOrSetDefault('redirect'));
exit;

?>