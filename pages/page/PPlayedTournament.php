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

$tManager = new CTournamentManager();

$tournament = $tManager->getTournament($db, $selectedTournament);

$mysqli->close();

// Link to images
$imageLink = WS_IMAGES;

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
EOD;

// -------------------------------------------------------------------------------------------
//
// Initialize javascript
//
$javaScript = "";
            
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

// -------------------------------------------------------------------------------------------
//
// Create the html
//
        
if ($tournament != null) {
$matchupHtml = $tournament->getAllRoundsAsHtmlNoEdit();

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
    $tbOut = $dbTbOne . ", " . $dbTbTwo;
}
if (count($tbList) >= 3) {
    $dbTbThree = getTieBreakerName($tbList[2]);
    $tbOut = $dbTbOne . ", " . $dbTbTwo . ", " . $dbTbThree;
}

$htmlMain .= <<< EOD
<div id="turneringsInfo">
            <table>
                <tr>
                    <td class='konfLabel'>När?</td>
                    <td>
                        {$tournament->getTournamentDateFrom()->getDate()}
                        &nbsp;-&nbsp;
                        {$tournament->getTournamentDateFrom()->getHour()}
                        {$tournament->getTournamentDateFrom()->getMinute()}
                        till
                        {$tournament->getTournamentDateTom()->getDate()}
                        &nbsp;-&nbsp;
                        {$tournament->getTournamentDateTom()->getHour()}
                        {$tournament->getTournamentDateTom()->getMinute()}
                    </td>
                </tr>
                <tr>
                    <td class='konfLabel'>Antal rundor</td>
                    <td>{$tournament->getNrOfRounds()}</td>
                </tr>
                <tr>
                    <td class='konfLabel'>Bye score</td>
                    <td>
                        {$tournament->getByeScore()}
                    </td>
                </tr>
                <tr>
                    <td class='konfLabel'>Tie breakers:</td>
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
