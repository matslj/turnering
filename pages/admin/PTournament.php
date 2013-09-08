<?php
// -------------------------------------------------------------------------------------------
//
// PTournament.php
//
// Handles the configuration of a tournament.
// 
// Author: Mats Ljungquist
//

// -------------------------------------------------------------------------------------------
//
// Get pagecontroller helpers. Useful methods to use in most pagecontrollers
//
require_once(TP_SOURCEPATH . 'CPageController.php');

$pc = CPageController::getInstance();

// -------------------------------------------------------------------------------------------
//
// Interception Filter, access, authorithy and other checks.
//
require_once(TP_SOURCEPATH . 'CInterceptionFilter.php');

$intFilter = new CInterceptionFilter();
$intFilter->frontcontrollerIsVisitedOrDie();
$intFilter->UserIsSignedInOrRecirectToSignIn();
$intFilter->UserIsMemberOfGroupAdminOrDie();

// -------------------------------------------------------------------------------------------
//
// Page specific code
//
$js = WS_JAVASCRIPT;
$needjQuery = TRUE;
$htmlHead = <<<EOD
    <!-- jQuery UI -->
    <script src="{$js}jquery-ui/jquery-ui-1.9.2.custom.min.js"></script>
        
    <!-- jQuery Form Plugin -->
    <script type='text/javascript' src='{$js}jquery-form/jquery.form.js'></script>
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
    </style>
EOD;

$javaScript = <<<EOD
(function($){
    $(document).ready(function() {
        tournament.config.init();
    });
})(jQuery);
EOD;

// ------------------------------------------------------------
// --
// --                  Systemhjälp
// --
$helpContent = <<<EOD
<p>
    Här konfigurerar man vissa grundläggande data för turneringen. Om man redan har kört igång
    en turnering, dvs börjat generera matcher, så kanske det inte är så meningsfullt att börja
    skruva på t.ex antalet rundor.
</p>
EOD;

// Provides help facility - include $htmlHelp in main content
require_once(TP_PAGESPATH . 'admin/PHelpFragment.php');
// -------------------- Slut Systemhjälp ----------------------

$htmlMain = <<<EOD
<h1>Turneringsdata</h1>
{$htmlHelp}
EOD;

$htmlRight = "";

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
$tournament = $tManager->getTournament($db);

$mysqli->close();

// -------------------------------------------------------------------------------------------
//
// Show the results of the query
//

$action = "?p=" . $pc->computePage() . "p";
$redirect = "?p=" . $pc->computePage();

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
                    <td><button id="updateTournament" style='margin-top: 20px;' type='submit' name='submit' value='update'>Uppdatera</button></td>
                    <td id="info" style='padding-top: 20px;'></td>
                </tr>
            </table>
    </form>
</div>
EOD;

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
