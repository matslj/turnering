<?php
// ===========================================================================================
//
// File: PPairingOfMatches.php
//
// Description: Sets up the matches.
//
// Author: Mats Ljungquist
//

$log = logging_CLogger::getInstance(__FILE__);

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
$intFilter->UserIsSignedInOrRecirectToSignIn();

// -------------------------------------------------------------------------------------------
//
// Take care of _GET/_POST variables. Store them in a variable (if they are set).
//
$createRound = $pc->GETisSetOrSetDefault('cr', 0);
CPageController::IsNumericOrDie($createRound);

$selectedTournament = $pc->GETisSetOrSetDefault('st', 0);
CPageController::IsNumericOrDie($selectedTournament);

$redirect = "?p=" . $pc->computePage();
$action = $redirect . "p";
$actionProcess = $redirect. "ap";

$uo = CUserData::getInstance();
$admin = $uo-> isAdmin();

// -------------------------------------------------------------------------------------------
//
// Get content of file archive from database
//
$db = new CDatabaseController();
$mysqli = $db->Connect();

$tManager = new CTournamentManager();
$log->debug("0: selected t = " . $selectedTournament);
$tournament = $tManager->getTournament($db, $selectedTournament);
$log->debug("1");
if ($tournament -> getCreator() -> getId() == $uo -> getId()) {
    $admin = true;
}
$tournamentHtml = $tManager->getTournamentMatchupsAsHtml($db, $tournament, $admin);
$log->debug("2");
$scoreProxyManager = $tournament->getScoreProxyManager();
$log->debug("3");
$htmlHead = "";
$javaScript = "";

// -------------------------------------------------------------------------------------------
//
// Close DB connection
//
$mysqli->close();

// Link to images
$imageLink = WS_IMAGES;

$nR = $tournament->getNextRound();
$log->debug("4: next round: " . $nR . " and number of rounds: " . $tournament->getNrOfRounds() . " admin: " . $admin);
$redirectRecreate = $actionProcess . "&cr={$nR}&t={$selectedTournament}";
$nextLink = "<a href='{$redirectRecreate}'><img style='border: 0;' src='{$imageLink}play_48.png' /></a>";
if ($nR > $tournament->getNrOfRounds() || !$admin) {
    $log -> debug("Det blir ingen next round");
    $nextLink = "";
}
$log -> debug("nextLink: " . $nextLink);

// -------------------------------------------------------------------------------------------
//
// Add JavaScript and html head stuff related to JavaScript
//
$js = WS_JAVASCRIPT;
$needjQuery = TRUE;
$htmlHead .= <<<EOD
    <!-- jQuery UI -->
    <script src="{$js}jquery-ui/jquery-ui-1.9.2.custom.min.js"></script>

    <!-- jQuery Form Plugin -->
    <script type='text/javascript' src='{$js}jquery-form/jquery.form.js'></script>
    <script type='text/javascript' src='{$js}myJs/build-min.js'></script>
        
    <style>
        .proxyLeft {
            padding-right: 20px;
            color: #33CC33;
        }
        .proxyRight {
            padding-left: 20px;
            color: #33CC33;
        }
    </style>
EOD;

$javaScript .= <<<EOD
(function($){
    $(document).ready(function() {
        var proxyFilter = {$scoreProxyManager->getScoreFilterAsJavascriptObjectArray()};
        tournament.matches.init("{$nextLink}", '{$action}', proxyFilter);
    });
})(jQuery);
EOD;
            
$redirectRecreate = $redirect . "&cr={$tournament->getNextRound()}";
$nextRound = "<div style='width: 48px; margin: 0 auto;' id='scoreSubmitDiv'>";
if ($tournament->isCurrentRoundComplete()) {
    $nextRound .= $nextLink;
}
$nextRound .= "</div>";
// $headerHtml = empty($currentFolderName) ? "Alla bilder" : "Bilder i katalogen: " . $currentFolderName;

// -------------------------------------------------------------------------------------------
//
// Create HTML for page
//
$htmlMain = <<<EOD
    <h1>Rapportera matchresultat</h1>
    <div class='sectionMatchup'>
    {$tournamentHtml}
    {$nextRound}
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

$page->printPage('Matchning', $htmlLeft, $htmlMain, $htmlRight, $htmlHead, $javaScript, $needjQuery, $subNav);
exit;

?>