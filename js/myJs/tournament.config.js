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

tournament.config = {
    // Id constants
    idDateFrom  : "input#dateFrom",
    idDateTom   : "input#dateTom",
    idInfo      : "td#info",
    idForm      : "#turneringForm",
    
    // Date pattern
    datePattern : "yy-mm-dd",
    
    init : function () {
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

        $(this.idForm).ajaxForm({
            dataType    : "json",
            beforeSubmit: tournament.config.validate,
            success:      tournament.config.response
        }); 
        
        $('input').bind('keyup', function() {
            $(tournament.config.idInfo).html(tournament.infoMsg);
        });
    },
    validate : function (formData, jqForm, options) {
        tournament.clearErrorMsg();
        
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
    response : function (data) {
        tournament.clearErrorMsg();
        if (data) {
            tournament.createErrorMsg(data);
        }
    }
    
};
