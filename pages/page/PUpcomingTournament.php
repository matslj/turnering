<?php
// -------------------------------------------------------------------------------------------
//
// PTournament.php
//
// Handles the configuration of a tournament.
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
// Interception Filter, access, authorithy and other checks.
//
require_once(TP_SOURCEPATH . 'CInterceptionFilter.php');

$intFilter = new CInterceptionFilter();
$intFilter->frontcontrollerIsVisitedOrDie();

$selectedTournament = $pc->GETisSetOrSetDefault('st', 0);

CPageController::IsNumericOrDie($selectedTournament);

// -------------------------------------------------------------------------------------------
//
// Page specific code
//
$js = WS_JAVASCRIPT;

$htmlLeft = "";
$htmlMain = "";
$htmlRight = "";
$htmlHead = "";
$javaScript = "";
$needjQuery = TRUE;

// In order to create tournament specific page names (so that I can use tournament specific
// title and content for a page) I add _T<tournamentId>
$pageName = basename(__FILE__) . "_T" . $selectedTournament;

$titleLink 	= "";

$content 	= "";
$isEditable = "";
$hideTitle = true;
$pageId = 0;

// -------------------------------------------------------------------------------------------
//
// Take care of _GET variables. Store them in a variable (if they are set).
// Then prepare the ORDER BY SQL-statement, but only if the _GET variables has a value.
//

// -------------------------------------------------------------------------------------------
//
// Create a new database object, connect to the database.
//
$db 	= new CDatabaseController();
$mysqli = $db->Connect();

$tManager = new CTournamentManager();

$tournament = $tManager->getTournament($db, $selectedTournament);

$title      = "Warhammer, {$tournament->getTournamentDateFrom()->getDate()}";

// Get the SP names
$spGetSidaDetails	= DBSP_PGetSidaDetails;

$query = <<< EOD
CALL {$spGetSidaDetails}('{$pageName}', 0);
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

// Link to images
$imageLink = WS_IMAGES;
$siteLink = WS_SITELINK;
            
// ------------------------------------------------------------
// --
// --                  Systemhjälp
// --
$helpContent = <<<EOD
<p>
    Här visas detaljinformation för vald turnering. Bara de rundor som faktiskt
    påbörjades visas i fliksystemet nedan.
</p>
EOD;

// Provides help facility - include $htmlHelp in main content
require_once(TP_PAGESPATH . 'admin/PHelpFragment.php');
// -------------------- Slut Systemhjälp ----------------------



$log->debug("Inför tie breaking!!");
// -------------------------------------------------------------------------------------------
//
// Deal with the tie breaking functionality
//

function getTieBreakerName($theTb) {
    if ($theTb instanceof tiebreak_CInternalWinner) {
        return CHTMLHelpers::getLabelForTieBreakerValue("internalwinner");
    } else if ($theTb instanceof tiebreak_CMostWon) {
        return CHTMLHelpers::getLabelForTieBreakerValue("mostwon");
    } else if ($theTb instanceof tiebreak_COrgScore) {
        return CHTMLHelpers::getLabelForTieBreakerValue("orgscore");
    }
    return "";
}

// -------------------------------------------------------------------------------------------
//
// Create the html
//
        
if ($tournament != null) {

$tbList = $tournament->getTieBreakers();
$dbTbOne = "";
$dbTbTwo = "";
$dbTbThree = "";
$tbOut = "";
if (count($tbList) >= 1) {
    $dbTbOne = getTieBreakerName($tbList[0]);
    $tbOut = $dbTbOne;
}
if (count($tbList) >= 2) {
    $dbTbTwo = getTieBreakerName($tbList[1]);
    $tbOut = "1) " . $dbTbOne . ", 2) " . $dbTbTwo;
}
if (count($tbList) >= 3) {
    $dbTbThree = getTieBreakerName($tbList[2]);
    $tbOut = "1) " . $dbTbOne . ", 2) " . $dbTbTwo . ", 3) " . $dbTbThree;
}

// -----------------------------------------------------------------------------
// -- Preparing tabs for played rounds
// --
$matchupHtml = "<div id='matchesTabs'>";
$matchupHtmlTitle = "<ul>";
$matchupHtmlContent = "";

$nr = $tournament -> getNrOfRoundsInMatrix();
for ($index = 1; $index <= $nr; $index++) {
    $matchupHtmlTitle .= "<li><a href='#tab{$index}'>Runda {$index}</a></li>";
    $matchupHtmlContent .= "<div id='tab{$index}'>" . $tournament -> getRoundAsHtml($index, false, false, false) . "</div>";
}

$matchupHtmlTitle .= "</ul>";
$matchupHtml .= $matchupHtmlTitle . $matchupHtmlContent;
$matchupHtml .= "</div>";

// -----------------------------------------------------------------------------
// -- The main html content
// --
$htmlMain .= <<< EOD
<h1>{$htmlPageTitleLink}</h1>
{$htmlHelp}
{$htmlPageContent}
<div class="clear"></div>
<hr class="style-two" />
{$htmlPageTextDialog}
<div id="turneringsInfo">
            <table>
                <tr>
                    <td class='konfLabel'>Plats: </td>
                    <td>{$tournament->getPlace()}</td>
                </tr>
                <tr>
                    <td class='konfLabel'>Skapad av: </td>
                    <td>{$tournament->getCreator()->getName()}</td>
                </tr>
                <tr>
                    <td class='konfLabel'>Tid: </td>
                    <td>
                        {$tournament->getTournamentDateFrom()->getDate()},
                        {$tournament->getTournamentDateFrom()->getHour()}:{$tournament->getTournamentDateFrom()->getMinute()}
                        till
                        {$tournament->getTournamentDateTom()->getDate()},
                        {$tournament->getTournamentDateTom()->getHour()}:{$tournament->getTournamentDateTom()->getMinute()}
                    </td>
                </tr>
                <tr>
                    <td class='konfLabel'>Antal rundor: </td>
                    <td>{$tournament->getNrOfRounds()}</td>
                </tr>
                <tr>
                    <td class='konfLabel'>Bye score: </td>
                    <td>
                        {$tournament->getByeScore()}
                    </td>
                </tr>
                <tr>
                    <td class='konfLabel'>Tie breakers: </td>
                    <td>
                        {$tbOut}
                    </td>
                </tr>
            </table>
            {$matchupHtml}
            
</div>
EOD;
            
} else {

$htmlMain .= <<<EOD
Ingen turnering är vald. Skapa en ny turnering eller välj en i listan.
EOD;

}

// -------------------------------------------------------------------------------------------
//
// Create and print out the resulting page
//
require_once(TP_SOURCEPATH . 'CHTMLPage.php');

$page = new CHTMLPage(WS_STYLESHEET);

// Creating the left menu panel
$htmlLeft = ""; // $page ->PrepareLeftSideNavigationBar(ADMIN_MENU_NAVBAR, "Admin - undermeny");

$page->printPage('Turneringsdata', $htmlLeft, $htmlMain, $htmlRight, $htmlHead, $javaScript, $needjQuery);
exit;

?>
