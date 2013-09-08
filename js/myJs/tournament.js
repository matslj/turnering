// -------------------------------------------------------------------------
// --
// --          THE TOURNAMENT NAMESPACE (this is the root namespace)
// --
// -------------------------------------------------------------------------

/**
 * This library requires jQuery.
 * 
 * @author Mats Ljungquist, 2013
 */

var tournament = tournament || {};

tournament = {
    // Below are tournament constants
    errorClass     : ".errorMsg",
    
    // Messages
    infoMsg        : "Glöm inte att spara/uppdatera!",
    
    /**
     * Namespace creator
     */
    namespace : function (name) {
        var parts = name.split('.');
        var current = tournament;
        for (var i in parts) {
            if (!current[parts[i]]) {
                current[parts[i]] = {};
            }
            current = current[parts[i]];
        }
    },
    
    /**
     * Creates an error message in the form of a html ul-list.
     * Places the error message in the element with the errorClass class.
     */
    createErrorMsg : function (errors) {
        var retHtml = "<ul>";
        for (var i = 0; i < errors.length; i++) {
            retHtml = retHtml + "<li>" + errors[i] + "</li>";
        }
        retHtml = retHtml + "</ul>";
        $(this.errorClass).html(retHtml);
    },
    
    /**
     * Clears the error messege (if set).
     */
    clearErrorMsg  : function () {
        $(this.errorClass).html('');
    },
    
    /**
     * Checks if an incoming string is a date.
     */
    isDate         : function (input) {
        var validformat=/^\d{4}-\d{2}-\d{2}$/ //Basic check for format validity
        if (!validformat.test(input)) {
            return false;
        } else { 
            //Detailed check for valid date ranges

            var yearfield=input.split("-")[0]
            var monthfield=input.split("-")[1]
            var dayfield=input.split("-")[2]
            
            if (parseInt(yearfield, 10) > 2030) {
                return false;
            }

            var dayobj = new Date(yearfield, monthfield-1, dayfield)
            if ((dayobj.getMonth()+1!=monthfield) || (dayobj.getDate()!=dayfield) || (dayobj.getFullYear()!=yearfield)) {
                return false;
            }
        }
        return true;
    }
};
