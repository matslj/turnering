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
EOD;



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
$tournaments = $tManager->getTournaments($db, true);
$mysqli->close();

$log->debug("Förbi db kopplandet");

// -------------------------------------------------------------------------------------------
//
// Initialize javascript
//
$urlToProcessPage = "?p=page-save";

$javaScript = <<<EOD
(function($){
    $(document).ready(function() {
        
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
<h1>Mina turneringar</h1>
{$htmlHelp}
EOD;

$htmlRight = "";

// -------------------------------------------------------------------------------------------
//
// Create the html
//

$action = "?p=" . $pc->computePage() . "p";
$redirect = "?p=" . $pc->computePage();

$htmlMain .= <<< EOD
<div id="minaTurneringar">
    <div class="errorMsg"></div>
    
    <form id='turneringForm' action='{$action}' method='POST'>
        <input type='hidden' name='redirect' value='{$redirect}'>
        <input type='hidden' name='redirect-failure' value='{$redirect}'>
    </form>
         
    <table>
EOD;
foreach ($tournaments as $tournament) {
$htmlMain .= <<< EOD
                <tr>
                    <td class='dateFrom'>{$tournament->getTournamentDateFrom()->getDate()}</td>
                    <td class='place'>{$tournament->getPlace()}</td>
                    <td class='place'>{$tournament->getNrOfRounds()}</td>
                </tr>
EOD;
}
$htmlMain .= <<< EOD
            </table>
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

$page->printPage('Mina turneringar', $htmlLeft, $htmlMain, $htmlRight, $htmlHead, $javaScript, $needjQuery);
exit;

?>
