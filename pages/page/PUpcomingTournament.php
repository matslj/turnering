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

$uo = CUserData::getInstance();

$redirect = "?p=" . $pc->computePage();
$action = $redirect . "p";

// -------------------------------------------------------------------------------------------
//
// Page specific code
//
$js = WS_JAVASCRIPT;
$siteLink = WS_SITELINK;

$htmlLeft = "";
$htmlMain = "";
$htmlRight = "";
$htmlHead = <<<EOD
    <!-- jQuery UI -->
    <script src="{$js}jquery-ui/jquery-ui-1.9.2.custom.min.js"></script>

    <!-- jQuery Form Plugin -->
    <script type='text/javascript' src='{$js}myJs/build-min.js'></script>
        
    <style>
        .konfLabel {
            font-weight: bold;
        }
        #aptable {
            margin: 0 auto;
        }
        #aptable th, #aptable td {
            background-color: transparent;
            color: #000;
            font-size: small;
        }
    </style>
EOD;
    
// -------------------------------------------------------------------------------------------
//
// Create a new database object, connect to the database.
//
$db 	= new CDatabaseController();
$mysqli = $db->Connect();

$canEdit = CTournamentManager::mayEditTournament($db, $selectedTournament);
$canEditText = $canEdit ? "true" : "false";
    
$javaScript = <<<EOD
(function($){
    $(document).ready(function() {
        tournament.participation.init("{$action}", {$selectedTournament});
        
        if ({$canEditText}) {
            $("#dialog").dialog({
                autoOpen: false,
                width: 250,
                modal: true,
                buttons: [
                    {
                        text: "Ändra",
                        click: function() {
                            $("#pForm").submit();
                            $( element ).dialog( "close" );
                        }
                    },
                    {
                        text: "Avbryt",
                        click: function() {
                            $(this).dialog("close");
                        }
                    }
                ],
                open: function(event) {
                    $(this).load("{$siteLink}?p=spl&st={$selectedTournament}");
                }
            });

            // Bind link to dialog open
            $("#pLink").click(function(event) {
                $("#dialog").dialog("open");
                event.preventDefault();
            });
        }
    });
})(jQuery);
EOD;
$needjQuery = TRUE;

// In order to create tournament specific page names (so that I can use tournament specific
// title and content for a page) I add _T<tournamentId>
$pageName = basename(__FILE__) . "OOOO" . $selectedTournament;

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

$tManager = new CTournamentManager();

$tournament = $tManager->getTournament($db, $selectedTournament);
$participating = CTournamentManager::isPartOfTournament($db, $uo -> getId(), $selectedTournament);

$title      = "Warhammer, {$tournament->getTournamentDateFrom()->getDate()}";

// *********************************************
// **      Get participant list
// *********************************************
$tUser = DBT_User;
$tUserTournament = DBT_UserTournament;
$imgUrl = WS_IMAGES;
$participantListHtml = "";
$numberOfParticipants = 0;
$query = <<< EOD
SELECT
	idUser,
	accountUser,
    nameUser,
    armyUser
FROM {$tUser} AS U INNER JOIN {$tUserTournament} AS UT ON UserTournament_idUser = idUser
WHERE U.deletedUser = FALSE
      AND U.activeUser = TRUE AND
      UT.UserTournament_idTournament = {$tournament -> getId()};
EOD;

$result = Array();

// Perform the query and manage results
$result = $db->Query($query);
$participantListHtml .= "<table id='participantList'>";
while($row = $result->fetch_object()) {
    $numberOfParticipants++;
    $participantListHtml .= "<tr id='plUser_{$row->idUser}'>"; // This is matched in the tournament.paticipation.js
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

$notAdminButAllowedToEdit = false;
if ($tournament != null) {
    if ($tournament->getCreator()->getId() == $uo -> getId()) {
        $notAdminButAllowedToEdit = true;
    }
}

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
            
// ------------------------------------------------------------
// --
// --                  Systemhjälp
// --
$helpContent = <<<EOD
<p>
    Här visas detaljinformation för en kommande turnering. Här kan man (om man är inloggad)
    gå med i/lämna den valda turneringen. Om man är skapare av turneringen så kan man
    även lägga till/ta bort andra deltagare från turneringen.
</p>
<p>
    Man kan (om man är skapare av turneringen) ändra inledningstexten till turneringen genom att klicka på titelraden
    (den som börjar med 'Warhammer, 20...').
</p>
EOD;

// Provides help facility - include $htmlHelp in main content
require_once(TP_PAGESPATH . 'admin/PHelpFragment.php');
// -------------------- Slut Systemhjälp ----------------------

/**
 * Converter -> from tiebreaker-class to label.
 * 
 * @param type $theTb tie breaker object
 * @return string the label matching the parameter tie breaker object
 */
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

// Create text output for tie breaker(s) that has been used in the tournament.
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

$htmlLoginJoinLeave = "<div id='loginJoinLeave'>";
if ($uo -> isAuthenticated()) {
    if (!$participating) {
        $htmlLoginJoinLeave .= "<a id='join' class='joinLeave' href='#'>Gå med i turneringen</a>";
    } else {
        $htmlLoginJoinLeave .= "<a id='leave' class='joinLeave' href='#'>Lämna turneringen</a>";
    }
} else {
    $htmlLoginJoinLeave .= "<a id='login' href='?p=login'>Logga in</a>";
}
$htmlLoginJoinLeave .= "</div>";

$editParticipantListLink = $canEdit ? " <a id='pLink' href='#'>Fler/Färre</a>" : "";

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
</div>
<hr class="style-two" />
{$htmlLoginJoinLeave}
<div id="deltagare">
    <div>
        <h3>Deltagare (so far){$editParticipantListLink}</h3>
        {$participantListHtml}
        <p id="antalDeltagare">Antal: {$numberOfParticipants}</p>
    </div>
    <div id="dialog" title="Administrera deltagare"></div>
</div>
EOD;
            
} else {

$htmlMain .= <<<EOD
Vald turnering existerar inte.
EOD;

}

$subNav = "";
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
require_once(TP_SOURCEPATH . 'CHTMLPage.php');

$page = new CHTMLPage(WS_STYLESHEET);

// Creating the left menu panel
$htmlLeft = ""; // $page ->PrepareLeftSideNavigationBar(ADMIN_MENU_NAVBAR, "Admin - undermeny");

$page->printPage('Kommande turnering', $htmlLeft, $htmlMain, $htmlRight, $htmlHead, $javaScript, $needjQuery, $subNav);
exit;

?>
