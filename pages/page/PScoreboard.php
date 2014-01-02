<?php
// ===========================================================================================
//
// File: PScoreboard.php
//
// Description: Presents a scoreboard on current development in the 
// selected tournament. Can also present the scoreboard as PDF.
//
// Author: Mats Ljungquist
//

// $log = logging_CLogger::getInstance(__FILE__);

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

$selectedTournament = $pc->GETisSetOrSetDefault('st', 0);
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
$htmlMain = <<<EOD
    <h1>Aktuellt turneringsresultat<span style="float: right;"><a href="{$siteLink}?p=pdfscoreboard"><img src="{$imageLink}/PDF-icon.png"></a></span></h1>
    <div class="clear"></div>
    <div class='section'>
        <table id='scoreboardPresentation'>
            <tr>
                <td id='firstColScore'></td>
                <td id='secondColScore'>
                    {$scoreBoardHtmlTable}
                </td>
                <td id='thirdColScore'></td>
            </tr>
        </table>
    </div>
EOD;

$htmlRight = "";

$subNav = "";
$uo = CUserData::getInstance();
if ($uo -> isAuthenticated()) {
    $tStr = "";
    if (!empty($selectedTournament)) {
        $tStr = "&st=" . $selectedTournament;
    }
    $menu = unserialize(SUB_MENU_NAVBAR);
    $subNav = "<div id='subNav'>" . CHTMLHelpers::getSubMenu($menu, $tStr) . "</div>";
}

// -------------------------------------------------------------------------------------------
//
// Create and print out the resulting page
//
$page = new CHTMLPage();

// Creating the left menu panel
$htmlLeft = "";

$page->printPage('Resultatlista', $htmlLeft, $htmlMain, $htmlRight, $htmlHead, $javaScript, $needjQuery, $subNav);
exit;

?>