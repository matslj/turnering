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
    <script type='text/javascript' src='{$js}myJs/build-min.js'></script>
        
    <style>
        .errorTextField {
            background-color: red;
            color: white;
        }
    </style>
EOD;

$redirectOnSuccess = 'json';
$javaScript = <<<EOD
// ----------------------------------------------------------------------------------------------
//
//
//
(function($){
    $(document).ready(function() {
        var createErrorMsg = function(errors) {
            var retHtml = "<ul>";
            for (var i = 0; i < errors.length; i++) {
                retHtml = retHtml + "<li>" + errors[i] + "</li>";
            }
            retHtml = retHtml + "</ul>";
            $(".errorMsg").html(retHtml);
        };
        
        var dialogOptions = {
            validator: function(dialogId) {
                $(".errorMsg").html('');
                var errors = [];
                $("#" + dialogId + " input:text").each(function() {
                    var name = $(this).attr('name');
                    var val = $(this).val();
                    if (name == 'accountname') {
                        if (!val) {
                            errors.push("Användarnamn-fältet måste ha ett värde");
                            $(this).addClass('errorTextField');
                        } else {
                            $(this).removeClass('errorTextField');
                        }
                    } else if (name == 'name') {
                        if (!val) {
                        errors.push("Namn-fältet måste ha ett värde");
                            $(this).addClass('errorTextField');
                        } else {
                            $(this).removeClass('errorTextField');
                        }
                    }
                });
                if (errors.length != 0) {
                    createErrorMsg(errors);
                }
                return (errors.length == 0);
            }
        };
        
        var clearErrors = function(dialogId, clearFields) {
            $(".errorMsg").html('');
            var element = $("#" + dialogId + " input:text").removeClass('errorTextField');
            if (clearFields) {
                element.val('');
            }
        }

        $("#dialogCreate").disimgDialog(dialogOptions);
        $("#dialogEdit").disimgDialog(dialogOptions);
        $("#dialogDelete").disimgDialog();
        
        // Declare buttons
        $(".edit").button({
            icons: {secondary : "ui-icon-pencil"},
            text: false
        }).click(function(event) {
            var substr = $(this).attr('id').split(':');
            $('#dialogEditUserId').val(substr[1]);
            $('#dialogEditAccountName').val(substr[2]);
            $('#dialogEditName').val(substr[3]);
            $("#dialogEditArmy").val(substr[5]);
            var tempActive = (substr[6] == '1') ? true : false;
            $("#dialogEditActive").attr('checked', tempActive);
            $("#dialogEdit").dialog("open");
            clearErrors("dialogEdit", false);
        });
        
        $(".delete").button({
            icons: {secondary : "ui-icon-close"},
            text: false
        }).click(function(event) {
            var substr = $(this).attr('id').split(':');
            $('#dialogDeleteUserId').val(substr[1]);
            $('#dialogDeleteName').html(substr[3]);
            $('#dialogDelete').dialog("open");
            $(".errorMsg").html('');
        });
        
        var options = {
            success:   showResponse,  // post-submit callback 
            dataType:  "json"
        }; 
        // Bind to form
        $('#form1').ajaxForm(options);

        // post-submit callback 
        function showResponse(data) {
            $('#page_id').val(data.pageId);
            $('p.notice').html("Saved: " + data.timestamp);
            $('button#savenow').attr('disabled', 'disabled');
        }

	// ----------------------------------------------------------------------------------------------
	//
	// Event handler for buttons in form. Instead of messing up the html-code with javascript.
	// Using Event bubbling as described in this document:
	// http://docs.jquery.com/Tutorials:AJAX_and_Events
	//
	$('#userList').click(function(event) {
            if ($(event.target).is('.create')) {
                $("#dialogCreate").dialog("open");
                clearErrors("dialogCreate", true);
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
    Den här sidan används för att administrera användare i systemet. Det finns
    två typer av användare: adm (administratörer) och usr (vanliga användare).
    Det går bara att ta bort vanliga användare.
</p>
EOD;

// Provides help facility - include $htmlHelp in main content
require_once(TP_PAGESPATH . 'admin/PHelpFragment.php');
// -------------------- Slut Systemhjälp ----------------------

$htmlMain = <<<EOD
<h1>Användarkonton</h1>
{$htmlHelp}
EOD;

$htmlRight = "";

// -------------------------------------------------------------------------------------------
//
// Take care of _GET variables. Store them in a variable (if they are set).
// Then prepare the ORDER BY SQL-statement, but only if the _GET variables has a value.
//
$orderBy 	= $pc->GETisSetOrSetDefault('orderby', '');
$orderOrder 	= $pc->GETisSetOrSetDefault('order', '');

$orderStr = "";
if(!empty($orderBy) && !empty($orderOrder)) {
    $orderStr = " ORDER BY {$orderBy} {$orderOrder}";
}

// -------------------------------------------------------------------------------------------
//
// Prepare the order by ref, can you figure out how it works?
//
$ascOrDesc = $orderOrder == 'ASC' ? 'DESC' : 'ASC';
$httpRef = "?p=admin_anvandare&amp;order={$ascOrDesc}&orderby=";

// -------------------------------------------------------------------------------------------
//
// Create a new database object, connect to the database.
//
$db 	= new CDatabaseController();
$mysqli = $db->Connect();


// -------------------------------------------------------------------------------------------
//
// Prepare and perform a SQL query.
//
$query = $db->LoadSQL('SAdminList.php');
$res = $db->Query($query);

// -------------------------------------------------------------------------------------------
//
// Show the results of the query
//

$htmlMain .= <<< EOD
<div id="userList">
    <p><a href="#" id="new-user-link" class="dialog-link ui-state-default ui-corner-all create"><span class="ui-icon ui-icon-newwin create"></span>Skapa användare</a></p>
    <table class="disImgTable" style='width: 100%;'>
    <tr>
    <th><a href='{$httpRef}accountUser'>Användarnamn</a></th>
    <th><a href='{$httpRef}nameUser'>Namn</a></th>
    <th><a href='{$httpRef}armyUser'>Armé</a></th>
    <th><a href='{$httpRef}idGroup'>Grupp</a></th>
    <th><a href='{$httpRef}activeUser'>Aktiverad</a></th>
    <th class='knapp' style='width: 80px;'>&nbsp;</th>
    </tr>
EOD;

$i = 0;
while($row = $res->fetch_object()) {
    $activeRewrite = $row->activeUser == 1 ? "Ja" : "";
    $htmlMain .= <<< EOD
    <tr>
        <td id="accountName_{$i}">{$row->accountUser}</td>
        <td id="nameUser_{$i}">{$row->nameUser}</td>
        <td id="armyUser_{$i}">{$row->armyUser}</td>
        <td style="width: 20px; text-align: center;" id="idGroup_{$i}">{$row->idGroup}</td>
        <td style="width: 30px; text-align: center;" id="activeUser_{$i}">{$activeRewrite}</td>
        <td><span style="margin-left: 3px;" id="{$i}:{$row->idUser}:{$row->accountUser}:{$row->nameUser}:{$row->emailUser}:{$row->armyUser}:{$row->activeUser}" class="edit"></span>
EOD;
                
if (strcmp($row->idGroup, 'adm') != 0) {
$htmlMain .= <<< EOD
            <span id="{$i}:{$row->idUser}:{$row->accountUser}:{$row->nameUser}" class="delete"></span>
        </td>
    </tr>
EOD;
}
$i++;
}

$selectArmyCreate = CHTMLHelpers::getHtmlForSelectableArmies('dialogCreateArmy');
$selectArmyEdit = CHTMLHelpers::getHtmlForSelectableArmies('dialogEditArmy');

$action = "?p=" . $pc->computePage() . "p";
$redirect = "?p=" . $pc->computePage();
$htmlMain .= <<< EOD
    </table>
</div>
<!-- ui-dialog create -->
<div id="dialogCreate" title="Skapa ny användare" class="dialog">
    <form id='dialogCreateForm' action='{$action}' method='POST'>
        <input type='hidden' name='redirect' value='{$redirect}'>
        <input type='hidden' name='redirect-failure' value='{$redirect}'>
        <input type='hidden' id='dialogCreateUserId' name='accountid' value=''>
        <input type='hidden' id='dialogCreateAction' name='action' value='create'>
        <fieldset>
            <div class="errorMsg">
            </div>
            <p>Användarnamn används i samband med inloggning</p>
            <table width='99%'>
                <tr>
                    <td><label for="dialogCreateAccountName">Användarnamn: </label></td>
                    <td style='text-align: right;'><input id='dialogCreateAccountName' class='name' type='text' name='accountname' value='' /></td>
                </tr>
                <tr>
                    <td><label for="dialogCreateName">Namn: </label></td>
                    <td style='text-align: right;'><input id='dialogCreateName' class='name' type='text' name='name' value='' /></td>
                </tr>
                <tr>
                    <td><label for="dialogCreateArmy">Armé: </label></td>
                    <td style='text-align: right;'>{$selectArmyCreate}</td>
                </tr>
                <tr colspan="2">
                    <td><input id='dialogCreateActive' checked type="checkbox" name="active" value="true" /><label for='dialogCreateActive'>Anmäld</label></td>
                </tr>
            </table>
        </fieldset>
    </form>
</div>
<!-- ui-dialog edit -->
<div id="dialogEdit" title="Ändra användare" class="dialog">
    <form id='dialogEditForm' action='{$action}' method='POST'>
        <input type='hidden' name='redirect' value='{$redirect}'>
        <input type='hidden' name='redirect-failure' value='{$redirect}'>
        <input type='hidden' id='dialogEditUserId' name='accountid' value=''>
        <input type='hidden' id='dialogEditAction' name='action' value='edit'>
        <fieldset>
            <div class="errorMsg">
            </div>
            <table width='99%'>
                <tr>
                    <td><label for="dialogEditAccountName">Användarnamn: </label></td>
                    <td style='text-align: right;'><input id='dialogEditAccountName' class='name' type='text' name='accountname' value='' /></td>
                </tr>
                <tr>
                    <td><label for="dialogEditName">Namn: </label></td>
                    <td style='text-align: right;'><input id='dialogEditName' class='name' type='text' name='name' value='' /></td>
                </tr>
                <tr>
                    <td><label for="dialogEditArmy">Armé: </label></td>
                    <td style='text-align: right;'>{$selectArmyEdit}</td>
                </tr>
                <tr colspan="2">
                    <td><input id='dialogEditActive' type="checkbox" name="active" value="true" /><label for='dialogEditActive'>Anmäld</label></td>
                </tr>
            </table>
        </fieldset>
    </form>
</div>
<!-- ui-dialog delete -->
<div id="dialogDelete" title="Radera användare" class="dialog">
    <form id='dialogDeleteForm' action='{$action}' method='POST'>
        <input type='hidden' name='redirect' value='{$redirect}'>
        <input type='hidden' name='redirect-failure' value='{$redirect}'>
        <input type='hidden' id='dialogDeleteUserId' name='accountid' value=''>
        <input type='hidden' id='dialogDeleteAction' name='action' value='delete'>
        <fieldset>
            <p>Vill du radera den här användaren?</p>
            <div id="dialogDeleteName"></div>
        </fieldset>
    </form>
</div>
EOD;

$res->close();


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
