<?php
// ===========================================================================================
//
// File: PScoreboard.php
//
// Description: This provides the content for a score board dialog in html format.
//
// Author: Mats Ljungquist
//

// $log = logging_CLogger::getInstance(__FILE__);

$pc = CPageController::getInstance();

// -------------------------------------------------------------------------------------------
//
// Interception Filter, controlling access, authorithy and other checks.
//
$intFilter = new CInterceptionFilter();
$intFilter->FrontControllerIsVisitedOrDie();

$selectedTournament = $pc->GETisSetOrSetDefault('st', 0);

// $log->debug("@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@ starting this page @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@");

CPageController::IsNumericOrDie($selectedTournament);

// -------------------------------------------------------------------------------------------
//
// Get content of file archive from database
//
$db = new CDatabaseController();
$mysqli = $db->Connect();

$tManager = new CTournamentManager();
$scoreBoardHtmlTable = $tManager->getScoreboardAsHtml($db, $selectedTournament);

// -------------------------------------------------------------------------------------------
//
// Close DB connection
//
$mysqli->close();

$siteLink = WS_SITELINK;
$imageLink = WS_IMAGES;

// -------------------------------------------------------------------------------------------
//
// Create HTML for page
//
$htmlMain = "";
if (!empty($scoreBoardHtmlTable)) {
$htmlMain .= <<<EOD
    <table id='scoreboardPresentation'>
        <tr>
            <td id='firstColScore'></td>
            <td id='secondColScore'>
                {$scoreBoardHtmlTable}
            </td>
            <td id='thirdColScore'></td>
        </tr>
    </table>
EOD;
}
                
// $log->debug($htmlMain);

// Print the header and page
$charset	= WS_CHARSET;
header("Content-Type: text/html; charset={$charset}");
echo $htmlMain;
exit;

?>