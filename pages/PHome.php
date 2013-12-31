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

$redirect = "?p=t"; // $pc->computeRedirect();
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

$tManager = new CTournamentManager();
$tournaments = $tManager->getTournaments($db);

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

$mysqli->close();

$htmlPageTitleLink = "";
$htmlPageContent = "";
$htmlPageTextDialog = "";

require_once(TP_PAGESPATH . 'page/PPageEditDialog.php');

// -------------------------------------------------------------------------------------------
//
// Preparing the tournament lists
//

$htmlLeft 	= "";
$htmlRight	= "";

$tournamentsHtml = "";
$upcomingTournamentsHtml = "";
foreach ($tournaments as $tempT) {
    if($tempT->getActive()) {
        if($tempT->isUpcoming()) {
            $upcomingTournamentsHtml .= <<< EOD
            <table>
            <tr>
                <td id="mtt{$tempT->getId()}">
                    <a href="{$redirect}upc&st={$tempT->getId()}">{$tempT->getTournamentDateFrom()->getDate()} - {$tempT->getTournamentDateTom()->getDate()}, {$tempT->getPlace()}</a>
                </td>
            </tr>
            </table>
EOD;
        } else {
            $tournamentsHtml .= <<< EOD
            <table>
            <tr>
                <td id="mtt{$tempT->getId()}">
                    <a href="{$redirect}past&st={$tempT->getId()}">{$tempT->getTournamentDateFrom()->getDate()} - {$tempT->getTournamentDateTom()->getDate()}, {$tempT->getPlace()}</a>
                </td>
            </tr>
            </table>
EOD;
        }
    }
}

if (empty($tournamentsHtml)) {
    $tournamentsHtml = "<div style='height: 100px; width: 100px'>Inga spelade turneringar</div>";
}

if (empty($upcomingTournamentsHtml)) {
    $upcomingTournamentsHtml = "<div style='height: 200px; width: 200px'>Inga kommande turneringar</div>";
}


$htmlRight .= <<< EOD
<div id="nyaTurneringar">
    Kommande
    {$upcomingTournamentsHtml}
</div>
<div id="gamlaTurneringar">
    Spelade
    {$tournamentsHtml}
</div>
EOD;

// -------------------------------------------------------------------------------------------
//
// Page specific code
//
$htmlMain = <<<EOD
<h1>{$htmlPageTitleLink}</h1>
{$htmlPageContent}
<div class="clear"></div>
<hr class="style-two" />
{$htmlPageTextDialog}
EOD;

// -------------------------------------------------------------------------------------------
//
// Create and print out the resulting page
//
$page = new CHTMLPage();

$page->printPage('Turnering - DMF', $htmlLeft, $htmlMain, $htmlRight, $htmlHead, $javaScript, $needjQuery);
exit;

?>