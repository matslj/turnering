<?php
// -------------------------------------------------------------------------------------------
//
// PUsersList.php
//
// Show all users in a list.
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
// Take care of global pageController settings, can exist for several pagecontrollers.
// Decide how page is displayed, review CHTMLPage for supported types.
//
$displayAs = $pc->GETisSetOrSetDefault('pc_display', '');

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

$redirectOnSuccess = 'json';
$javaScript = <<<EOD

function isDate(input){
    var validformat=/^\d{4}-\d{2}-\d{2}$/ //Basic check for format validity
    var returnval=false
    if (!validformat.test(input)) {
        return false;
    } else { 
        //Detailed check for valid date ranges
    
        var yearfield=input.split("-")[0]
        var monthfield=input.split("-")[1]
        var dayfield=input.split("-")[2]
    
        var dayobj = new Date(yearfield, monthfield-1, dayfield)
        if ((dayobj.getMonth()+1!=monthfield) || (dayobj.getDate()!=dayfield) || (dayobj.getFullYear()!=yearfield)) {
            return false;
        }
    }
    return true;
}

var infoMsg = "Glöm inte att spara/uppdatera!";

// ----------------------------------------------------------------------------------------------
//
//
//
(function($){
    $(document).ready(function() {

        $("input#dateFrom").datepicker({
            onSelect : function() {
                $('td#info').html(infoMsg);
            },
            minDate: 0,
            dateFormat: "yy-mm-dd"
        });
        $("input#dateTom").datepicker({
            onSelect : function() {
                $('td#info').html(infoMsg);
            },
            minDate: 0,
            dateFormat: "yy-mm-dd"
        });

        $('#turneringForm').ajaxForm( { beforeSubmit: validate } ); 
        
        $('input').bind('keyup', function() {
            $('td#info').html(infoMsg)
        });
    });
    
    function createErrorMsg(errors) {
            var retHtml = "<ul>";
            for (var i = 0; i < errors.length; i++) {
                retHtml = retHtml + "<li>" + errors[i] + "</li>";
            }
            retHtml = retHtml + "</ul>";
            $(".errorMsg").html(retHtml);
    }
    
    function validate(formData, jqForm, options) { 
        $(".errorMsg").html('');
        var errors = [];
        var dateFrom = $('input[name=dateFrom]').fieldValue()[0]; 
        var hourFrom = $('input[name=hourFrom]').fieldValue()[0];
        var minuteFrom = $('input[name=minuteFrom]').fieldValue()[0];
        var dateTom = $('input[name=dateTom]').fieldValue()[0]; 
        var hourTom = $('input[name=hourTom]').fieldValue()[0];
        var minuteTom = $('input[name=minuteTom]').fieldValue()[0];
        var nrOfRounds = $('input[name=nrOfRounds]').fieldValue()[0];
        var byeScore = $('input[name=byeScore]').fieldValue()[0];

        if (!isDate(dateFrom, '-')) { 
            errors.push("Ogiltigt datumformat på startdatum");
        }
        if (!isDate(dateTom, '-')) { 
            errors.push("Ogiltigt datumformat på slutdatum");
        }
        
        var intRegex = /^\d+$/;
        
        if(!intRegex.test(hourFrom) || hourFrom < 0 || hourFrom > 23) {
           errors.push("Felaktigt format på timmar på startdatum");
        }
        if(!intRegex.test(minuteFrom) || minuteFrom < 0 || minuteFrom > 59) {
           errors.push("Felaktigt format på minuter på startdatum");
        }
        if(!intRegex.test(hourTom) || hourTom < 0 || hourTom > 23) {
           errors.push("Felaktigt format på timmar på slutdatum");
        }
        if(!intRegex.test(minuteTom) || minuteTom < 0 || minuteTom > 59) {
           errors.push("Felaktigt format på minuter på slutdatum");
        }

        if(!intRegex.test(nrOfRounds)) {
           errors.push("Antal rundor måste vara ett positivt heltal");
        }
        if(!intRegex.test(byeScore)) {
           errors.push("Bye score måste vara ett positivt heltal");
        }

        if (errors.length != 0) {
            createErrorMsg(errors);
            return false;
        }
        $('td#info').html('');
    }
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

// -------------------------------------------------------------------------------------------
//
// Prepare and perform a SQL query.
//


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
            <script>
                console.log("hhooooooohoooo");
                console.log("{$tournament->getTournamentDateFrom()->getDate()}");
                console.log("{$tournament->getTournamentDateFrom()->getHour()}");
                console.log("{$tournament->getTournamentDateFrom()->getMinute()}");
            </script>
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
// Close the connection to the database
//

$mysqli->close();


// -------------------------------------------------------------------------------------------
//
// Create and print out the resulting page
//
require_once(TP_SOURCEPATH . 'CHTMLPage.php');

$page = new CHTMLPage(WS_STYLESHEET);

// Creating the left menu panel
$htmlLeft = ""; // $page ->PrepareLeftSideNavigationBar(ADMIN_MENU_NAVBAR, "Admin - undermeny");

// $page->printPage($htmlLeft, $htmlMain, $htmlRight, '', $displayAs);
$page->printPage('Användare', $htmlLeft, $htmlMain, $htmlRight, $htmlHead, $javaScript, $needjQuery);
exit;

?>
