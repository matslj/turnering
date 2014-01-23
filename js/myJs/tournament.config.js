// -------------------------------------------------------------------------
// --
// --          THE CONFIG NAMESPACE
// --
// -------------------------------------------------------------------------

/**
 * Js for the PTournament.php page.
 * This library requires jQuery.
 *
 * @author Mats Ljungquist, 2013
 */

// This is for the PTournament.php page
tournament.namespace('config');

/**
 * Tournament config class. First some form-mapping constants and datepattern and
 * then the methods.
 */
tournament.config = {
    // Id constants
    idDateFrom        : "input#dateFrom",
    idDateTom         : "input#dateTom",
    idInfo            : "td#info",
    idForm            : "#turneringForm",
    idPointFilterForm : "#dialogPointFilterForm",
    idPointFilterCbx  : "#pointFilterCbx",
    idPointFilterDiv  : "#pointFilterDiv",
    idTable           : "#minaTurneringar table tbody",
    
    // Links & Paths
    images            : "",
    
    // The selector for the score filter.
    sfSelector        : "td.sfCell input",
    nrInputOnRow      : 4,
    
    sfOrgFrom         : "orgFrom",
    sfOrgTom          : "orgTom",
    sfNewFrom         : "newFrom",
    sfNewTom          : "newTom",
    
    // Holder for tournamentId
    tournamentId      : 0,
    
    // Date pattern
    datePattern       : "yy-mm-dd",
    
    rowIdError        : "Error: rowId must be an integer.",
    inputError        : "Varje fält måste innehålla en siffra och för varje rad så måste den andra kolumnen ha större värden än den första.",
    
    responseCallbackFunction : null,
    
    /**
     * Initializes javascript for the tournament config page.
     * <ul>
     * <li>Initializes date pickers (jquery ui) for from and tom dates.
     * <li>Initializes toggler score filter checkbox. When the checkbox is checked
     *     a div becomes unhidden (it contains a clickable link which in turn, when
     *     clicked, presents a score filter dialog). Deselecting the checkbox hides
     *     the div again.
     * <li>Initializing form-plugin for form processing.
     * <li>Initializing keyup listener on input fields (so that I can inform the user
     *     that something has been changed and needs to be saved). Simple but 
     *     functional.
     * </ul>
     */
    init : function (responseCallback, imagesPath, tournamentId) {
        tournament.config.tournamentId = tournamentId;
        tournament.config.images = imagesPath;
        tournament.config.responseCallbackFunction = responseCallback;
        $(this.idDateFrom).datepicker({
            onSelect : function() {
                $(tournament.config.idInfo).html(tournament.infoMsg);
            },
            minDate: 0,
            dateFormat: tournament.config.datePattern
        });
        $(this.idDateTom).datepicker({
            onSelect : function() {
                $(tournament.config.idInfo).html(tournament.infoMsg);
            },
            minDate: 0,
            dateFormat: tournament.config.datePattern
        });
        
        // Code for toggling filter link
        var cbxChecked = $(tournament.config.idPointFilterCbx).attr('checked');
        if (!cbxChecked) {
            $(tournament.config.idPointFilterDiv).hide();
        }
        $(tournament.config.idPointFilterCbx).click(function() {
            $(tournament.config.idPointFilterDiv).toggle(400);
            $(tournament.config.idInfo).html(tournament.infoMsg);
        });

        $(this.idForm).ajaxForm({
            dataType    : "json",
            beforeSubmit: tournament.config.validate,
            success:      tournament.config.response
        }); 
        
        $('input').bind('keyup', function() {
            $(tournament.config.idInfo).html(tournament.infoMsg);
        });
    },
    
    /** 
     * Validates tournament configuration form input on the client side.
     * Method signature according to form-plugin method signature.
     * 
     * @param formData not used
     * @param jqForm not used
     * @param options not used
     */
    validate : function (formData, jqForm, options) {
        tournament.clearErrorMsg();
        if ($('input[name=dateFrom]').prop('disabled')) {
            $(tournament.config.idInfo).html('');
            return true;
        }
        var errors = [];
        var dateFrom = $('input[name=dateFrom]').fieldValue()[0]; 
        var hourFrom = $('input[name=hourFrom]').fieldValue()[0];
        var minuteFrom = $('input[name=minuteFrom]').fieldValue()[0];
        var dateTom = $('input[name=dateTom]').fieldValue()[0]; 
        var hourTom = $('input[name=hourTom]').fieldValue()[0];
        var minuteTom = $('input[name=minuteTom]').fieldValue()[0];
        var nrOfRounds = $('input[name=nrOfRounds]').fieldValue()[0];
        var byeScore = $('input[name=byeScore]').fieldValue()[0];

        if (!tournament.isDate(dateFrom, '-')) { 
            errors.push("Ogiltigt datumformat på startdatum");
        }
        if (!tournament.isDate(dateTom, '-')) { 
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
            tournament.createErrorMsg(errors);
            return false;
        }
        $(tournament.config.idInfo).html('');
    },
    
    createTable: function(tournamentListJson) {
        var htmlOut = "";
        var counter = 0;
        for (var i in tournamentListJson) {
            var lActive = "";
            var mRow = "";
            if (tournamentListJson[i].active) {
                lActive = "*";
            }
            if (tournamentListJson[i].id == tournament.config.tournamentId) {
                mRow = " class='markedRow'"
            }
            htmlOut += "<tr>";
            htmlOut += "<td" + mRow + " style='width:10px; text-align: right;'>";
            htmlOut += lActive;
            htmlOut += "</td>";
            htmlOut += "<td" + mRow + ">";
            htmlOut += "<a href='?p=admin_tournament&st=" + tournamentListJson[i].id +"'>" + tournamentListJson[i].fromDate + " - " + tournamentListJson[i].tomDate + "</a>";
            htmlOut += "</td>";
            htmlOut += "<td" + mRow + ">";
            var tempRounds = parseInt(tournamentListJson[i].playedRounds, 10);
            if (!tempRounds || tempRounds <= 1) {
                htmlOut += "<a href='?p=admin_tournamentdp&tId=" + tournamentListJson[i].id +"'><img style='vertical-align: bottom; border: 0;' src='" + tournament.config.images + "close_16.png' /></a>";
            } else {
                htmlOut += "&nbsp;"
            }
            htmlOut += "</td>";
            htmlOut += "</tr>";
            counter++;
        }
        $(tournament.config.idTable).html(htmlOut);
    },
    
    /**
     * Callback method for the ajax call which is set up in the init method.
     * If errors are returned from the server, these are presented here.
     */
    response : function (data) {
        tournament.clearErrorMsg();
        if (data) {
            if (data.status != "ok"){
                tournament.createErrorMsg(data.message);
            } else {
                console.log(data.active);
                console.log(data.tournaments);
                tournament.config.createTable(data.tournaments);
                // tournament.config.responseCallbackFunction(data.active);
            }
        }
    },
    
    updateScoreFilter : function (theTournamentId, callback) {
        // First extract form data into an array of objects
        var scoreFilter = [];
        var sfObject = null;
        // var lastRowId = -1;
        var lastValue = -1;
        var inputCount = 1;
        var errorFound = false;
        var actionUrl = $(tournament.config.idPointFilterForm).attr("action");
        console.log("actionUrl: " + actionUrl);
        
        $(tournament.config.sfSelector).each( function() {
            
            var _self = $(this);
            
            var re = /^\d+$/g; // every input field must have at least one digit and only digits
            if (!re.test(_self.val())) {
                _self.addClass("errorBackground");
                errorFound = true;
            } else {
                _self.removeClass("errorBackground");
            }
            
            var selfVal = parseInt(_self.val(), 10);
            
            if (inputCount == 1) {
                sfObject = {};
                scoreFilter.push(sfObject);
                lastValue = selfVal;
            } else if (inputCount == 2) {
                if (lastValue > selfVal) {
                    _self.addClass("errorBackground");
                    errorFound = true;
                    console.log("error: " + lastValue + " - " + selfVal);
                }
            }
            
            
            // Pick out the index for each row (its behind the hashmark on the
            // id attribute on the input fields).
//            var tempId = $(this).attr('id');
//            var indexOfHashmark = tempId.indexOf('#');
//            var rowId = parseInt(tempId.substring(indexOfHashmark + 1), 10);
//            
//            if (isNaN(rowId)) {
//                throw new Error(tournament.config.rowIdError)
//            }
//
//            // Create a new object if the last id and new id does not match.
//            // Also push the new object to the list of objects.
//            if (rowId != lastRowId) {
//                // console.log("Nytt objekt skapas! rowId: " + rowId + " lastRowId: " + lastRowId);
//                lastRowId = rowId;
//                sfObject = {};
//                scoreFilter.push(sfObject);
//            }
            var theClass = _self.attr("class");
            sfObject[theClass] = selfVal;
            
            if (inputCount == tournament.config.nrInputOnRow) {
                inputCount = 1;
            } else {
                inputCount++;
            }
        });
        
        if (errorFound) {
            data = {};
            data.errorMsg = tournament.config.inputError;
            callback(data);
        } else {
            var jsonScore = JSON.stringify(scoreFilter);

            console.log(jsonScore);

            // Förbered Ajax-call
            $.ajax({
                url: actionUrl,
                type:'POST',
                dataType: "json",
                data: {"tournamentId": theTournamentId, "scores":jsonScore},
                success: function(data) {
//                    if (data.status == 'ok') {
//                        console.log("klar!!");
//                    } else {
//                        console.log(data.message);
//                    }
                    data = {};
                    callback(data);
                }
            });
        }
    }
    
};
