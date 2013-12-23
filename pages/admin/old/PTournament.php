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
$intFilter->UserIsSignedInOrRecirectToSignIn();

$createTournament = $pc->GETisSetOrSetDefault('c', 0);
$selectedTournament = $pc->GETisSetOrSetDefault('st', 0);
CPageController::IsNumericOrDie($createTournament);
CPageController::IsNumericOrDie($selectedTournament);

// $intFilter->UserIsMemberOfGroupAdminOrDie();

$uo = CUserData::getInstance();
$admin = $uo-> isAdmin();

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
$needjQuery = FALSE;


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
$tournament = null;
$tManager = new CTournamentManager();

// Three possible scenarios for tournament retrieval:
// 1) Create new tournament
// 2) Get specified tournament (by id) - user must be admin
// 3) Get active tournament (each user can only have one active tournament)
if ($createTournament == 1) {
    $tournament = $tManager->createTournament($db);
} else if (!empty($selectedTournament) && $admin) {
    $tournament = $tManager->getTournament($db, $selectedTournament);
} else {
    $tournament = $tManager->getActiveTournament($db);
}
// $tournament = $tManager->getTournament($db);
$log->debug("efter trour");
$mysqli->close();

if ($tournament != null) {

$spManager = $tournament->getScoreProxyManager();
$log->debug("efter spman");

$needjQuery = TRUE;

// -------------------------------------------------------------------------------------------
//
// Set header - page specific jslibs and page specific style
//
$htmlHead = <<<EOD
    <!-- jQuery UI -->
    <script src="{$js}jquery-ui/jquery-ui-1.9.2.custom.min.js"></script>
        
    <!-- jQuery Form Plugin -->
    <script type='text/javascript' src='{$js}jquery-form/jquery.form.js'></script>
    <script type='text/javascript' src='{$js}jquery-context-menu/jquery.ui-contextmenu.min.js'></script>
    <script type='text/javascript' src='{$js}myJs/build-min.js'></script>
    
    <style>
        input.date {
            width: 80px;
        }
        input.time {
            width: 20px;
        }
        td.konfLabel {
            width: 100px;
        }
        span.example {
            font-style: italic;
            font-size: 9px;
        }
        .errorMsg {
            background-color: red;
            color: white;
        }
        #pointFilterDiv {
            margin-left: 25px;
        }
        div.dialog {
            width: 300px;
        }
        div.dialog table {
            border-collapse: collapse;
            margin: 0 auto;
        }
        div.dialog th {
            width: 80px;
            background-color: #817865;
            color: #FFF;
        }
        td.dpfCell {
            width: 50px;
            padding: 0;
            margin: 0;
        }
        td.dpfCell input {
            width: 50px;
            padding: 0;
            margin: 0;
        }
        td.minus,
        td.slash {
            width: 10px;
            padding: 0;
            margin: 0;
            text-align: center;
        }
        div.dialog th.dbfEmptyCell,
        div.dialog td.dbfEmptyCell {
            width: 2px;
        }
        
        div.dialog td.dbfEmptyCell {
            border-left: 3px solid #FEEEBD;
            border-right: 3px solid #FEEEBD;
            background-color: #817865;
        }
        
        .ui-menu {
            z-index: 5000;
        }
        
        div.dialog table tr th.dbfHandle,
        div.dialog table tr td.dbfHandle {
            width: 30px;
        }
        
        div.dialog table tr td.dbfHandle div.dbfHTarget {
            margin: 2px;
            margin-left: 4px;
            margin-right: 4px;
            background-color: green;
            width: 22px;
            cursor: pointer;
        }
        
        div.dialog table tr:hover {
            background-color: #817865;
        }
        
        .errorBackground {
            background-color: red;
        }
        
        .inlineBlockSpan {
            background-color: green;
            width: 22px;
            height: 16px;
            display: inline-block;
        }

        div.errorMsgDialog {
            border: 1px solid black;
            padding: 5px;
            background-color: red;
        }
    </style>
EOD;

// -------------------------------------------------------------------------------------------
//
// Initialize javascript
//
$urlToProcessPage = "?p=page-save";

$javaScript = <<<EOD
(function($){
    $(document).ready(function() {
        tournament.config.init();
        
        $(".scoreFilterTable").contextmenu({
            delegate: ".dbfHTarget",
            preventSelect: true,
            taphold: true,
            menu: [
                {title: "Lägg till ny rad", cmd: "add", uiIcon: "ui-icon-plus"},
                {title: "Ta bort rad", cmd: "remove", uiIcon: "ui-icon-minus"}
            ],
            position: function(event, ui){
                return {of: ui.target};
            },
            // Handle menu selection to implement a fake-clipboard
            select: function(event, ui) {
                var \$target = ui.target;
                switch(ui.cmd){
                    case "add":
                        \$target.parent().parent().after(
                            $('<tr />').html(
                                "<td class='sfCell dbfHandle'><div class='dbfHTarget'>&nbsp;</div></td>" +
                                "<td class='sfCell dpfCell'><input id='dpfOriginalFrom#' class='orgFrom' type='text' name='dpfOriginalFrom#' value='' /></td>" + 
                                "<td class='sfCell minus'>-</td>" + 
                                "<td class='sfCell dpfCell'><input id='dpfOriginalTom#' class='orgTom' type='text' name='dpfOriginalTom#' value='' /></td>" +
                                "<td class='sfCell dbfEmptyCell'>&nbsp;</td>" + 
                                "<td class='sfCell dpfCell'><input id='dpfNewFrom#' class='newFrom' type='text' name='dpfNewFrom#' value='' /></td>" + 
                                "<td class='sfCell slash'>-</td>" +
                                "<td class='sfCell dpfCell'><input id='dpfNewTom#' class='newTom' type='text' name='dpfNewTom#' value='' /></td>"
                            )
                        );
                        break
                    case "remove":
                        \$target.parent().parent().remove();
                        break
                }
                // alert("select " + ui.cmd + " on " + target.text());
                // Optionally return false, to prevent closing the menu now
            }
        });

        var dialogOptions = {
            width: 340,
            url: "{$urlToProcessPage}",
//            buttons: [
//            {
//                text: "Avbryt",
//                click: function() {
//                    $("#dialogPointFilter").dialog( "close" );
//                }
//            }],
            callback: function(data) {
                if (data.errorMsg) {
                    $("div#dialogError").html(data.errorMsg);
                    $("div#dialogError").addClass("errorMsgDialog");
                } else {
                    $("div#dialogError").html("");
                    $("div#dialogError").removeClass("errorMsgDialog");
                }
            }
        };
        var formData = {
            tournamentId: {$tournament->getId()}
        };
        $("#dialogPointFilter").pointFilterDialog(dialogOptions, formData);
        
        $('#pointFilterDiv').click(function(event) {
            if ($(event.target).is('.openFilterDialog')) {
                $("#dialogPointFilter").dialog("open");
                event.preventDefault();
            }
        });
    });
})(jQuery);
EOD;
            
// ------------------------------------------------------------
// --
// --                  Systemhjälp
// --
$helpContent = <<<EOD
<p>
    Här konfigurerar man vissa grundläggande data för turneringen. Det går att ändra
    även under pågående turnering, men ens valmöjligheter kan då komma att begränsas
    något - t.ex. är det inte möjligt att sätta ett lägre antal rundor än det antal
    rundor som redan är spelade.
</p>
<p style="font-weight: bold;">Tie breakers</p>
<p>
    Ponera att spelare x och spelare y har samma poäng. Om man har angivit en eller flera
    tie breakers, så appliceras dessa, i ordning, på x och y i ett vidare försök att lösa
    x och ys ranking. De tie breakers som finns är:
</p>
<ul>
    <li>Inbördes möte: Om x och y har mötts tidigare, så rankas den högre som vann deras möte.</li>
    <li>Flest vinster: Den som har flest vinster rankas högre</li>
    <li>Originalpoäng: Om man kör med ett poängfilter, så kan man använda oförändrad originalpoäng som tie breaker.
        Valet har ingen effekt om man inte har valt poängfilter och kommer då inte att sparas i databasen.
    </li>
</ul>
Man kan också välja att inte ha någon tie breaker.
EOD;

// Provides help facility - include $htmlHelp in main content
require_once(TP_PAGESPATH . 'admin/PHelpFragment.php');
// -------------------- Slut Systemhjälp ----------------------

$htmlMain = <<<EOD
<h1>Turneringsdata</h1>
{$htmlHelp}
EOD;

$htmlRight = "";


$log->debug("Inför tie breaking!!");
// -------------------------------------------------------------------------------------------
//
// Deal with the tie breaking functionality
//

function getTieBreakerName($theTb) {
    if ($theTb instanceof tiebreak_CInternalWinner) {
        return "internalwinner";
    } else if ($theTb instanceof tiebreak_CMostWon) {
        return "mostwon";
    } else if ($theTb instanceof tiebreak_COrgScore) {
        return "orgscore";
    }
    return "";
}

$tbList = $tournament->getTieBreakers();
$dbTbOne = "";
$dbTbTwo = "";
$dbTbThree = "";
if (count($tbList) >= 1) {
    $dbTbOne = getTieBreakerName($tbList[0]);
}
if (count($tbList) >= 2) {
    $dbTbTwo = getTieBreakerName($tbList[1]);
}
if (count($tbList) >= 3) {
    $dbTbThree = getTieBreakerName($tbList[2]);
}

$selectTieBreakOne = CHTMLHelpers::getHtmlForSelectableTieBreakers('tieBreakOne', 'tbone', $dbTbOne);
$selectTieBreakTwo = CHTMLHelpers::getHtmlForSelectableTieBreakers('tieBreakTwo', 'tbtwo', $dbTbTwo);
$selectTieBreakThree = CHTMLHelpers::getHtmlForSelectableTieBreakers('tieBreakThree', 'tbthree', $dbTbThree);

$log->debug("Efter tie breaking!!");

// -------------------------------------------------------------------------------------------
//
// Create the html
//

$action = "?p=" . $pc->computePage() . "p";
$redirect = "?p=" . $pc->computePage();

$checked = $tournament->getUseProxy() ? " checked='checked'" : "";

$htmlMain .= <<< EOD
<div id="turneringsInfo">
    <form id='turneringForm' action='{$action}' method='POST'>
        <input type='hidden' name='redirect' value='{$redirect}'>
        <input type='hidden' name='redirect-failure' value='{$redirect}'>
        <input type='hidden' id='tId' name='tId' value='{$tournament->getId()}'>
            <div class="errorMsg">
            </div>
            <table>
                <tr>
                    <td class='konfLabel'><label for="dateFrom">Start: </label></td>
                    <td>
                        <input id='dateFrom' class='date' type='text' name='dateFrom' value='{$tournament->getTournamentDateFrom()->getDate()}' />
                        &nbsp;-&nbsp;
                        <input id='hourFrom' class='time' type='text' name='hourFrom' value='{$tournament->getTournamentDateFrom()->getHour()}' maxlength='2' /> :
                        <input id='minuteFrom' class='time' type='text' name='minuteFrom' value='{$tournament->getTournamentDateFrom()->getMinute()}' maxlength='2' />
                        <span class='example'>(Exempel: 2013-08-20 - 09:00)</span>
                    </td>
                </tr>
                <tr>
                    <td class='konfLabel'><label for="dateTom">Slut: </label></td>
                    <td>
                        <input id='dateTom' class='date' type='text' name='dateTom' value='{$tournament->getTournamentDateTom()->getDate()}' />
                        &nbsp;-&nbsp;
                        <input id='hourTom' class='time' type='text' name='hourTom' value='{$tournament->getTournamentDateTom()->getHour()}' maxlength='2' /> :
                        <input id='minuteTom' class='time' type='text' name='minuteTom' value='{$tournament->getTournamentDateTom()->getMinute()}' maxlength='2' />
                        <span class='example'>(Exempel: 2013-08-20 - 21:00)</span>
                    </td>
                </tr>
                <tr>
                    <td class='konfLabel'><label for="nrOfRounds">Antal rundor: </label></td>
                    <td><input id='nrOfRounds' class='time' type='text' name='nrOfRounds' value='{$tournament->getNrOfRounds()}' maxlength='2' /></td>
                </tr>
                <tr>
                    <td class='konfLabel'><label for="byeScore">Bye score: </label></td>
                    <td>
                        <input id='byeScore' class='date' type='text' name='byeScore' value='{$tournament->getByeScore()}' />
                        <span class='example'>(kompensationspoäng för spelare som måste stå över en runda)</span>
                    </td>
                </tr>
                <tr>
                    <td class='konfLabel'>&nbsp;</td>
                    <td>Ange eventuella tie breakers (nedan) i den ordning du vill att de ska appliceras</td>
                </tr>
                <tr>
                    <td class='konfLabel'><label for="tieBreakOne">Tie break 1: </label></td>
                    <td>
                        {$selectTieBreakOne}
                    </td>
                </tr>
                <tr>
                    <td class='konfLabel'><label for="tieBreakTwo">Tie break 2: </label></td>
                    <td>
                        {$selectTieBreakTwo}
                    </td>
                </tr>
                <tr>
                    <td class='konfLabel'><label for="tieBreakTwo">Tie break 2: </label></td>
                    <td>
                        {$selectTieBreakThree}
                    </td>
                </tr>
                <tr>
                    <td class='konfLabel'>&nbsp;</label></td>
                    <td>
                        <input id='pointFilterCbx' type="checkbox" name="pointFilterCbx" value="true"{$checked} />
                        <label for='pointFilterCbx'>Använd poängfilter</label>
                        <div id='pointFilterDiv'><a class='openFilterDialog' href='#'>Öppna filterdefinition</a></div>
                    </td>
                </tr>
                <tr>
                    <td><button id="updateTournament" style='margin-top: 20px;' type='submit' name='submit' value='update'>Uppdatera</button></td>
                    <td id="info" style='padding-top: 20px;'></td>
                </tr>
            </table>
            {$_SESSION['errorMessage']}
    </form>
</div>
<!-- ui-dialog pointFilter -->
<div id="dialogPointFilter" title="Poängfilter" class="dialog">
    <form id='dialogPointFilterForm' action='{$action}d' method='POST'>
        <input type='hidden' name='redirect' value='{$redirect}'>
        <input type='hidden' name='redirect-failure' value='{$redirect}'>
        <input type='hidden' id='dialogEditUserId' name='accountid' value=''>
        <input type='hidden' id='dialogEditAction' name='action' value='edit'>
        <fieldset>
            <p>
                Här kan du förändra poängfiltret. Högerklicka på de gröna rektanglarna
                (<span class="inlineBlockSpan">&nbsp;</span>) nedan för att lägga till/ta bort
                rader. Observera att systemet inte håller reda på om du matar in överlappande 
                (dvs felaktiga) intervall.
            </p>
            <div id="dialogError">
            </div>
            {$spManager->getScoreFilterAsHtmlTable()}
        </fieldset>
    </form>
</div>
EOD;
            
} else {

$htmlMain = <<<EOD
<h1>Turneringsdata</h1>
Du har ingen aktiv turnering - vill du skapa en turnering?
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
