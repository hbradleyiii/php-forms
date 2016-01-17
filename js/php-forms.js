/**
 * php-forms - PHP form validation class with automation control
 * Copyright (c) 2010, Harold Bradley III
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301
 * USA
 *
 * @package     php-forms
 * @author      Harold Bradley III <harold@bradleystudio.net>
 * @copyright   2010  Harold Bradley III
 * @license     GNU LESSER GENERAL PUBLIC LICENSE
 * @version     1.0
 */

/**
 * phpFormValidator
 */
var phpFormValidator = {

    /**
     * Initializes phpFormValidator, (called on the bottom of this file)
     * - Fills in the data for the Turing Test.
     * - Sets up an onSubmit listener for all forms on page.
     * - Records the time the form was loaded.
     */
    init: function() {
        var d = new Date();
        phpFormValidator.rules.loadTime = d.getTime(); // Sets the time the form was loaded
        phpFormValidator.jsTuringTest(); // Turing Test; fill in box, hide form and label
        var forms = document.getElementsByTagName("form"); // Get forms
        for (var i = 0, ii = forms.length; i < ii; i++){forms[i].addEventListener("submit", phpFormValidator.validateForm, false);}
    },

    /**
     * Validates the form (this) upon submission
     * - Loops through an array of field elements
     *   - Loops through an array of classes of the field
     *     - If the field is a checkbox only check if it's required
     *     - If the field is a password box check if it has been filled in
     *     - Otherwise test the field according to the rules
     *     Will only catch the first error for each field.
     *     If validation failed:
     *         - set focus on first failed element
     *         - set the fail flag
     *         - setFieldError(field, classes) (can have different implementations)
     *     If validation passed:
     *         -
     *
     */
    validateForm: function(event) { // Upon submission the form (this) is validated.
        var fields = this.elements; // elements: a node list containing all of the form controls in the form.
        var firstError = true; // if an error is found, it will be considered the first (for mouse focus) then this is switched to false
        var formPassedValidation = true; // I like to be optimistic...

        for (var i = 0, ii = fields.length; i < ii; i++){ // Loop through all form controls (field elements). OUTERLOOP
            var fieldPassedValidation = true; // I like to be optimistic...
            var classes = fields[i].className; // Current string of classes for this field element.
            var valTypes = null; // Validation types from the json file
            if(phpFormData["form"][fields[i].name] && phpFormData["form"][fields[i].name]["valTypes"]){valTypes = phpFormData["form"][fields[i].name]["valTypes"];} // Validation types from the json file, if the field exists

                // Checkbox ---------------
            if (fields[i].type === "checkbox") { // If field is a checkbox
                isRequired = false; // 'innocent' until proven guilty...
                for (var k = 0, kk = valTypes.length; k < kk; k++) {if(valTypes[k] === "required"){isRequired = true;}} // loop to find if this is required
                if ((isRequired) && (!fields[i].checked)) { // If checkbox is required and not checked
                    // Failed Validation ->
                    phpFormValidator.setFieldError(fields[i], classes);
                    phpFormValidator.writeErrorMsg(fields[i].name, "required");

                    if (firstError){ // Puts the focus on the first error.
                        fields[i].focus();
                        firstError = false; // Only happens once.
                    }

                    fieldPassedValidation = false;
                    formPassedValidation = false;

                } // else all good, continue to reset possible "fieldErrors"

                // Password ---------------
            } else if (fields[i].type === "password") { // If field is a password box
                if (fields[i].value === "") { // If password is empty
                    // Failed Validation ->
                    phpFormValidator.setFieldError(fields[i], classes);
                    phpFormValidator.writeErrorMsg(fields[i].name, "required");

                    if (firstError){ // Puts the focus on the first error.
                        fields[i].focus();
                        firstError = false; // Only happens once.
                    }

                    fieldPassedValidation = false;
                    formPassedValidation = false;

                } // else all good, continue to reset possible "fieldErrors"

            // TODO: add validation for two password boxes around here

                // All other fields ---------------
            } else if (valTypes){ // If valTypes for this field exist, (All other field types)

                for (var j = 0, jj = valTypes.length; j < jj; j++) { // Loop through valTypes of current field element. INNERLOOP
                    var rule = phpFormValidator.rules[valTypes[j]]; // Set the rule to be the rule for the valType[j].

                    if (rule && !fields[i].disabled && !rule.test(fields[i].value)) {
                      // If there is both a rule for the current class attribute
                      // and if the form field is not disabled
                      // and if the rule failed:
                            // Failed Validation ->
                            phpFormValidator.setFieldError(fields[i], classes); // Set Field Error class
                            phpFormValidator.writeErrorMsg(fields[i].name, valTypes[j]);

                            if (firstError){ // Puts the focus on the first error.
                                fields[i].focus();
                                firstError = false; // Only happens once.
                            }

                            fieldPassedValidation = false;
                            formPassedValidation = false;

                            // break out of classes loop, by forcing next iteration to fail
                            // but continue with field loop
                            // this prevents the possibility of a field failing for multiple classes:
                            j = valTypes.length;
                    } // else ignore this field (no rules or field is disabled)
                } // try next class in current field or continue: INNERLOOP
            }

                // Reset possible "fieldErrors"
            if (fieldPassedValidation){ // After completing the loops for this field, test error flags
                phpFormValidator.resetFieldNoError(fields[i], classes);
                phpFormValidator.writeErrorMsg(fields[i].name, "noError");
            }
        } // end loop through field elements, OUTERLOOP

        // Test Submission time
        var dt = new Date();
        if(dt.getTime() <= (phpFormValidator.rules.loadTime + phpFormValidator.rules.minTime)) {phpFormValidator.submitTooFast(event);} // If submitted too quick call submitTooFast()

        if(!formPassedValidation) {event.preventDefault();} // Prevent the form from submitting
    },

    // A form will be validated based on the CSS class name(s) of that form.
    // Associative array that corresponds to classnames of elements, it contains regexes for validation testing
    rules: {
        email: /^(?!.{255,})(?!.{65,}@)(?:[a-z0-9_-]+(?:\.[a-z0-9_-]+)*)@(?:[a-z0-9]+(?:-[a-z0-9]+)*\.){1,2}[a-z]{2,6}$/,
        empty: /^$/,
        max25: /(^$)|(^.{1,25}$)/,
        min2: /^.{2,}$/,
        loadTime: 0, // I just thought this would be a good place for this...
        minTime: 4100, // and this.
        name: /^[a-zA-Z0-9_ ]*$/,
        required: /./,
        usTelephone: /^(?:(?:\+?1\s*(?:[.-]\s*)?)?(?:\(\s*([2-9]1[02-9]|[2-9][02-8]1|[2-9][02-8][02-9])\s*\)|([2-9]1[02-9]|[2-9][02-8]1|[2-9][02-8][02-9]))\s*(?:[.-]\s*)?)?([2-9]1[02-9]|[2-9][02-9]1|[2-9][02-9]{2})\s*(?:[.-]\s*)?([0-9]{4})(?:\s*(?:#|x\.?|ext\.?|extension)\s*(\d+))?$/
    },

/////////////////////////////////////////////////////////////////////////
////// Project specific functions go here:
/////////////////////////////////////////////////////////////////////////

   /**
    * This is called when there is a validation error in a field.
    * It is used to do specific things accordingly
    *
    * @param  field    The field that failed validation
    * @param  classes  A string of the current classes of the field
    */
    setFieldError: function(field, classes){
        // If "fieldError" does not already exist as a class, adds a new class "fieldError":
        if (classes.search("fieldError") === -1) {field.className = classes + " fieldError";}
    },

   /**
    * This is called when the validation for a field passed
    * It is used to reset any possible errors in classnames
    *
    * @param  field    The field that passed validation
    * @param  classes  A string of the current classes of the field
    */
    resetFieldNoError: function(field, classes){
        classes = classes.replace(" fieldError", "");
        classes = classes.replace("fieldError", ""); // In case 'fieldError' is first class
        field.className = classes;
    },

   /**
    * This is called to write an error message in the label tag of the
    * element that had the error.
    *
    * It uses "errorTags" in json file. If it's not found the method
    * does nothing. "errorTags" contains an array of HTML elements (strings
    * without brackets). They are nested inside the label tag of the element.
    * The error message is nested inside the deepest level of these tags.
    *
    * "errorTagsBranch" is used to determine which of potentially multiple
    * branch of a particular element to use. 0 is the first branch, 1 is the
    * second, etc... for each corresponding index of "errorTags"
    *
    * valError "noError" is sent to reset the ErrorMsg to ""
    *
    * @param  field      The field that failed validation
    * @param  valError   The validation type that caused the field to fail
    */
    writeErrorMsg: function(field, valError) {

        function getLabelByFor(fieldId){ // Get the label for field using fieldId
            var labels = document.getElementsByTagName("label");
            for (var i = 0, ii = labels.length; i < ii; i++) {
                if (labels[i].htmlFor === fieldId){return labels[i];}
            }
            return null;
        }

        if(phpFormData["form"][field]){ // Make sure field is defined in json file

                // Collect required json data
            var errorTags = null;
            if(phpFormData["form"][field]["errorTags"]){ // check for specific field tags
                errorTags = phpFormData["form"][field]["errorTags"];
            } else if (phpFormData["errorTags"]) { // else check for general field tags
                errorTags = phpFormData["errorTags"];
            }

            if(errorTags){ // Make sure error tags exist

                    // Collect other json data, or use default:
                  // errorTagsBranch array:
                  var errorTagsBr = null;
                if(phpFormData["form"][field]["errorTagsBranch"]){ // check for specific Branch data
                    errorTagsBr = phpFormData["form"][field]["errorTagsBranch"];
                } else if(phpFormData["errorTagsBranch"]){ // check for generic json Branch data
                    errorTagsBr = phpFormData["errorTagsBranch"];
                } else { // use default
                    for(var i = 0, ii = errorTags.length; i < ii; i++){errorTagsBranch[i] = 0;} // defaults to 0, the first element found
                }

                  // errorMsg string:
                  var errorMsg = "This field contains an error";
                if(phpFormData["fieldErrorMsg"][valError]){ // check jason data for error message
                    errorMsg = phpFormData["fieldErrorMsg"][valError];
                } else if(valError === "noError") { // sending valType of "noError" will reset the error message
                    errorMsg = "";
                }

                    // Walk the DOM to putErrorMsgHere (The label of the field that has the error)
                  var putErrorMsgHere = getLabelByFor(field);
                for(var i = 0, ii = errorTags.length; i < ii; i++){ // Loop through tags needed for error message
                    if(putErrorMsgHere.getElementsByTagName(errorTags[i])[errorTagsBr[i]]){ // if this tag exists (we don't need to create it) move putErrorMsgHere up a level
                        putErrorMsgHere = putErrorMsgHere.getElementsByTagName(errorTags[i])[errorTagsBr[i]];
                        if(i == errorTags.length - 1){putErrorMsgHere.innerHTML = errorMsg;} // if this is the last tag, REPLACE the innerHTML with errorMsg
                    } else { // else tag does not exist, so we must create it, along with every tag that is nested in it
                        var startTag = "";
                        var endTag = "";
                        for(; i < ii; i++){ // finish the loop, creating tags that do not yet exist
                            startTag = startTag + "<" + errorTags[i] + ">"; // create start tag
                            endTag = "</" + errorTags[i] + ">" + endTag; // Notice the order is reversed
                         }
                        var message = startTag + errorMsg + endTag; // combine message
                        putErrorMsgHere.innerHTML = putErrorMsgHere.innerHTML + message; // We created this, so ADD to the end
                    }
                }

             } // Do nothing... errorTags do not exist
        } // Do nothing... field does not exist in json
    },

   /**
    * This is called when the form validated, but was submitted too quickly
    * This causes the form not to be submitted for convenience on the part of the user
    */
    submitTooFast: function(event){
        event.preventDefault();
        alert("Wow! You sure are fast! Maybe a little too fast... In order to prevent spam, the server will not accept a form submitted this quickly. Please wait 2-3 seconds and try again.");
    },

   /**
    * This is used to automate a Turing test for the convenience
    * of the user with JavaScript enabled.
    * Note that this is not tested in the validation testing.
    *
    */
    jsTuringTest: function() { // CHECK TO MAKE SURE BOX EXISTS!!!!!!!!!!!!!!!!
        turingboxLabel = document.getElementById("turingbox-label");
        if(turingboxLabel){turingboxLabel.style.display = "none";} // Make the label invisible
        turringbox = document.getElementById("turingbox");
        if(turringbox){
            turringbox.style.display = "none"; // Make the box invisible
            turringtext = document.getElementById("turingtext");
            if(turringtext){turringbox.value = turringtext.innerHTML;} // Enter the text into the box
        }
    }
};

phpFormValidator.init(); // Do your thing
