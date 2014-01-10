<?php
// ===========================================================================================
//
// File: PPairingOfMatchesActionProcess.php
//
// Description: Processes requests for adding, deleting or reseting a round (match round).
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

// -------------------------------------------------------------------------------------------
//
// Take care of _GET/_POST variables. Store them in a variable (if they are set).
//
$returnPage = $pc->GETisSetOrSetDefault('p', 'home');
$tournamentId = $pc->GETisSetOrSetDefault('tId', 0);
CPageController::IsNumericOrDie($tournamentId);

$db = new CDatabaseController();
$mysqli = $db->Connect();

$tManager = new CTournamentManager();
$tManager->deleteTournament($db, $tournamentId);

// -------------------------------------------------------------------------------------------
//
// Close DB connection
//
$mysqli->close();

//$log->debug("### -returnpage1: " . $returnPage);
//
//$returnPageLength = strlen($returnPage);
//$returnPage = substr($returnPage, 0, $returnPageLength - 1);
//
//$log->debug("### -returnpage2: " . $returnPage);

$returnPage = "?p=admin_tournament";

// -------------------------------------------------------------------------------------------
//
// Redirect to another page
//
$pc->RedirectTo($returnPage);
exit;

?>