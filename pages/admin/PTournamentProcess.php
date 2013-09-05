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

// Error checking
if ($hourFrom < 0 || $hourFrom > 23) {
    $errorMsg = "<li>felaktigt värde på timme: {$hourFrom}</li>";
    $errorFound = true;
}
if ($hourTom < 0 || $hourTom > 23) {
    $errorMsg = "<li>felaktigt värde på timme: {$hourTom}</li>";
    $errorFound = true;
}
if ($minuteFrom < 0 || $minuteFrom > 59) {
    $errorMsg = "<li>felaktigt värde på minuter: {$minuteFrom}</li>";
    $errorFound = true;
}
if ($minuteTom < 0 || $minuteTom > 59) {
    $errorMsg = "<li>felaktigt värde på minuter: {$minuteTom}</li>";
    $errorFound = true;
}

$df = null;
$dt = null;

// Try create dates
try {
    $df = new DateTime($dateFrom . " " . $hourFrom . ":" . $minuteFrom . ":01");
} catch (Exception $exc) {
    $errorMsg = "<li>Startdatum har fel format. Vänligen kontrollera formatet.</li>";
    $errorFound = true;
}
try {
    $dt = new DateTime($dateTom . " " . $hourTom . ":" . $minuteTom . ":01"); 
} catch (Exception $exc) {
    $errorMsg = "<li>Slutdatum har fel format. Vänligen kontrollera formatet.</li>";
    $errorFound = true;
}

if ($errorFound) {
    $errorMsg .= "</ul>";
    $_SESSION['errorMessage'] = $errorMsg;
    $pc->RedirectTo($pc->POSTisSetOrSetDefault('redirect'));
    exit;
}

// Create database object (to get the required sql-config)
$db = new CDatabaseController();
$mysqli = $db->Connect();

// Get current tournament data
//$tournament = CTournament::getInstanceById($db, $tId);

$dateFormat = "Y-m-d H:i:s";

$spEditSelectedValuesTournament = DBSP_EditSelectedValuesTournament;
$query = "CALL {$spEditSelectedValuesTournament}({$tId}, {$nrOfRounds}, {$byeScore}, '{$df->format($dateFormat)}', '{$dt->format($dateFormat)}');";

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