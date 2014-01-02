<?php
// ===========================================================================================
//
// File: PPairingOfMatchesActionProcess.php
//
// Description: Processes requests for adding, deleting or reseting a round (match round).
// Observe that both admins and the creator of the tournament ar allowed to perform
// these actions.
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
$theRound = $pc->GETisSetOrSetDefault('cr', 0);
$tId = $pc->GETisSetOrSetDefault('t', 0);
CPageController::IsNumericOrDie($theRound);
CPageController::IsNumericOrDie($tId);

$db = new CDatabaseController();
$mysqli = $db->Connect();

$tManager = new CTournamentManager();
$tournament = $tManager ->getTournament($db, $tId);
$intFilter->IsUserMemberOfGroupAdminOrIsCurrentUserOrTerminate($tournament -> getCreator() -> getId());
$tManager->modifyRound($db, $tournament, $theRound);

// -------------------------------------------------------------------------------------------
//
// Close DB connection
//
$mysqli->close();

$returnPageLength = strlen($returnPage);
$returnPage = substr($returnPage, 0, $returnPageLength - 2);

// -------------------------------------------------------------------------------------------
//
// Redirect to another page
//
$pc->RedirectTo($returnPage);
exit;

?>