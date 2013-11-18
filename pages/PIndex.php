<?php
// ===========================================================================================
//
// PIndex.php
//
// Startsida för turneringen.
//
// Author: Mats Ljungquist
//


// -------------------------------------------------------------------------------------------
//
// Get pagecontroller helpers. Useful methods to use in most pagecontrollers
//
$pc = CPageController::getInstance();
//$pc->LoadLanguage(__FILE__);


// -------------------------------------------------------------------------------------------
//
// Interception Filter, controlling access, authorithy and other checks.
//
$intFilter = new CInterceptionFilter();

$intFilter->FrontControllerIsVisitedOrDie();
$img = WS_IMAGES;

$redirect = $pc->computeRedirect();
// $urlToEditPost = "?p=page-edit{$redirect}&amp;page-id=";

// -------------------------------------------------------------------------------------------
//
// Take care of _GET/_POST variables. Store them in a variable (if they are set).
//
$pageId = 0;
$userId	= isset($_SESSION['idUser']) ? $_SESSION['idUser'] : "";

// -------------------------------------------------------------------------------------------
//
// Create a new database object, connect to the database, get the query and execute it.
// Relates to files in directory TP_SQLPATH.
//
$pageName = basename(__FILE__);

$needjQuery = TRUE;
$htmlHead = <<< EOD
<style>
    .whenWhere {
        float: right;
        padding: 0 5px 0 5px;
        margin: 0 0 10px 10px;
        background-color: #454545;
        border-style: solid;
        border-width: 1px;
        border-color: #6E6E6E #303030 #303030 #6E6E6E;
        width: 180px;
    }

    .whenWhere p {
        font-style: italic;
        font-size: 12px;
        font-weight: bold;
    }
</style>
EOD;

$javaScript = "";

$titleLink 	= "";
$title      = "";
$content 	= "";
$isEditable = "";

// Connect
$db 	= new CDatabaseController();
$mysqli = $db->Connect();

$tournament = CTournament::getInstanceById($db, 1);

// Get the SP names
$spGetSidaDetails	= DBSP_PGetSidaDetails;

$query = <<< EOD
CALL {$spGetSidaDetails}('$pageName', 0);
EOD;

// Perform the query
$results = Array();
$res = $db->MultiQuery($query);
$db->RetrieveAndStoreResultsFromMultiQuery($results);

// Get article details
$row = $results[0]->fetch_object();
if ($row) {
    $pageId     = $row->id;
    $title      = $row->title;
    $content    = $row->content;
    // $titleLink = ($intFilter->IsUserMemberOfGroupAdmin()) ? "<a title='Ändra inlägg' href='{$urlToEditPost}{$row->id}'>$row->title</a>" : $row->title;
}
$results[0]->close();

// *********************************************
// **      Get participant list
// *********************************************
$tUser = DBT_User;
$imgUrl = WS_IMAGES;
$participantListHtml = "";
$numberOfParticipants = 0;
$query = <<< EOD
SELECT
	idUser,
	accountUser,
    nameUser,
    armyUser
FROM {$tUser} AS U
WHERE deletedUser = FALSE
      AND activeUser = TRUE;
EOD;

$result = Array();

// Perform the query and manage results
$result = $db->Query($query);
$participantListHtml .= "<table>";
while($row = $result->fetch_object()) {
    $numberOfParticipants++;
    $participantListHtml .= "<tr>";
    $imgName = CHTMLHelpers::getArmyValueName($row -> armyUser);
    $participantListHtml .= "<td>{$row->accountUser}</td>";
    $participantListHtml .= "<td>{$row -> armyUser}</td>";
    $participantListHtml .= "</tr>";
}
$participantListHtml .= "</table>";
$result -> close();
// *********************************************
// **      End get participant list
// *********************************************

$mysqli->close();

$htmlPageTitleLink = "";
$htmlPageContent = "";
$htmlPageTextDialog = "";

require_once(TP_PAGESPATH . 'page/PPageEditDialog.php');

// -------------------------------------------------------------------------------------------
//
// Page specific code
//
$htmlMain = <<<EOD
<h1>{$htmlPageTitleLink}</h1>
<div class="whenWhere">
    <p>Från: {$tournament->getTournamentDateFrom()->getDate()} kl: {$tournament->getTournamentDateFrom()->getHour()}:{$tournament->getTournamentDateFrom()->getMinute()}</p>
    <p>Till: {$tournament->getTournamentDateTom()->getDate()} kl: {$tournament->getTournamentDateTom()->getHour()}:{$tournament->getTournamentDateTom()->getMinute()}</p>
</div>
{$htmlPageContent}
<div class="clear"></div>
<hr class="style-two" />
<div id="deltagare">
<div>
<h3>Deltagare (so far)</h3>
{$participantListHtml}
<p>Antal: {$numberOfParticipants}</p>
</div>
</div>
{$htmlPageTextDialog}
EOD;

$htmlLeft 	= "";
$htmlRight	= "";

// -------------------------------------------------------------------------------------------
//
// Create and print out the resulting page
//
$page = new CHTMLPage();

$page->printPage('Turnering - DMF', $htmlLeft, $htmlMain, $htmlRight, $htmlHead, $javaScript, $needjQuery);
exit;

?>