<?php
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
 * @version     1.2
 */

/**
 * phpForm validates an array of submitted form data in order to prevent
 * automation
 *
 * phpForm Forms go through three sessions:
 * 1. initSession() - the data for the form is initialized and the form is
 *    presented to the user.
 * 2. valSession() - the data for the form is submitted by the user to this to
 *    be validated.
 * 3. submitSession() - the data for the form was presented for
 *    the user to check, the user clicked the link; this function checks the
 *    link and validates it.
 *
 * These three methods return true or false, the data must be handled
 * appropriately by the controller.
 *
 * 'Pr' methods are used for printing specific form data.
 * 'Str' methods are used for returning strings of specific form data.
 * 'Set' methods are used for setting private values.
 *
 */
class phpForm {

   /**
    * Session Data:
    *
    * $fmName                              the name of the current form, this
    *                                      allows for possible multiple forms
    *                                      per page
    * $_SESSION[$fmName]['form']           an associative array containing the
    *                                      data for the form, indices are fields
    * $_SESSION[$fmName]['formErrorMsg']   a string containing '', or the
    *                                      current form Error message
    * $_SESSION[$fmName]['counter']      a cookie page counter, set the first
    *                                      time this is called
    * $_SESSION[$fmName]['IP']             the ip address of the current user
    * $_SESSION[$fmName]['submitLink']     a string containing a 32 character
    *                                      submit link
    * $_SESSION[$fmName]['sessionCalls']   the number of times this class has
    *                                      been constructed
    * $_SESSION[$fmName]['time']           the time that the initSession was
    *                                      last called
    * $_SESSION[$fmName]['turingText']     a string containing a random turing
    *                                      text the user is to type
    * $_SESSION[$fmName]['valLink']        a string containing a 32 character
    *                                      validation link
    *
    */

   /**
    * The name of the form
    *
    * It is used as the first index of SESSION array to distinguish
    * possible multiple forms.
    *
    * @var string
    */
    private $_fmName = "defaultForm"; // Initialized at construction

   /**
    * The current time in milliseconds
    *
    * @var integer
    */
    private $_curTime = 0; // Initialized at construction

   /**
    * The minimun allowable submission time.
    *
    * @var integer
    */
    private $_minTime = 4; // Approx. 4 sec.

   /**
    * The maximum allowable submission time.
    *
    * @var integer
    */
    private $_maxTime = 1800; // Approx. 30 min.

   /**
    * An array of strings one of which can be randomly used for a (reverse)
    * Turing test.
    *
    * @var array
    */
    private $_turingText = array('I am a human', 'This is not spam',
        'No spam here', 'Human', 'I hate spam', 'Not a spammer', 'Humans only',
        'No bots', 'Humans rule', 'Anti-spam box');

   /**
    * An array of data containing default form error messages
    *
    * If custom form error messages are not set, these default messages are used
    *
    * 0. no error - validation passed.
    * 1. validation error - one or more fields has errors in it.
    * 2. time error - form was submitted too slowly, form has expired.
    * 3. time error - form submitted too quickly, appears to be automation
    * 4. link error - validation link is inaccurate.
    * 5. cookie error - cookies are not present or session data has not been
    *    initialized.
    * 6. link error - submission link is fake or init was not
    *    called first. (should not ever happen under normal circumstances)
    *
    * @var array
    */
    private $_formErrorMsg = array(
        '', // Index 0 is not an error
        'The form contains errors. Please correct these errors and submit again.', // Error 1
        'Your form has expired. Please try again.', // Error 2
        'The submission of this form appeared to be automated. Please wait a few seconds and try again.', // Error 3
        'There was an error processing your form. Please try again.', // Error 4
        'Your browser must support cookies and have them enabled in order to submit this form.', // Error 5
        'ERROR: There was an error processing your form. Please try again.' // Error 6
    );

   /**
    * An associative array of data containing default field error messages
    *
    * If custom field error messages are not set, these default messages are used.
    * Array indices correspond with valTypes.
    *
    * @var array
    */
    private $_fieldErrorMsg = array(
        'email' => 'Please enter a valid email address',
        'empty' => 'Please leave this field empty',
        'max25' => 'This field can have no more than 25 characters',
        'message' => 'Please enter 1000 characters or less',
        'min2' => 'This field must have at least 2 characters',
        'name' => 'Please only use letters, spaces, and numbers',
        'nopassword' => 'Please enter your password',
        'required' => 'This field is required',
        'selectnovalue' => 'ERROR!',
        'turing' => 'This field is not correct',
        'usTelephone' => 'Please enter a valid US telephone number'
    );

   /**
    * Constructor function for phpForm
    *
    * Initializes the current time and form name variable.
    * Session is started here if a session has not already been started elsewhere.
    * Initializes the session data for the form if not  done already.
    * Initializes any custom Form and Field error messages.
    *
    * @param    array   $fmData      an array of data that describes the form
    */
    public function __construct($fmData) {
        if(!isset($_SESSION)){session_start();} // Start Session if one has not already been started

        $this->_curTime = time(); // Sets the time this was called at the server
        $this->_fmName = $fmData['formName']; // Sets the form name

            // Initialize Session Data
        if(!isset($_SESSION[$this->_fmName]['form'])) { // Set up the Session array if it has not been already
            $_SESSION[$this->_fmName]['form'] = $fmData['form'];
            for($fields=array_keys($_SESSION[$this->_fmName]['form']), $i=0, $ii=count($fields); $i<$ii; $i++){
                $_SESSION[$this->_fmName]['form'][$fields[$i]]['error'] = false; // Set defaults, no errors,
                $_SESSION[$this->_fmName]['form'][$fields[$i]]['errorMsg'] = ""; // no error messages
            }
        }
        if(!isset($_SESSION[$this->_fmName]['valLink'])) {$_SESSION[$this->_fmName]['valLink'] = 'fail';} // Set a failing validation link if a real one has not been set yet (this prevents a passing null validation link)
        if(!isset($_SESSION[$this->_fmName]['submitLink'])) {$_SESSION[$this->_fmName]['submitLink'] = 'fail';} // Set a failing submit link if a real one has not been set yet (this prevents a passing null submit link)

            // Session Meta data
        $_SESSION[$this->_fmName]['IP'] = $_SERVER['REMOTE_ADDR']; // Just go ahead and reset every time for now.
        $_SESSION[$this->_fmName]['formErrorMsg'] = ''; // Reset any former error messages

        if(isset($_SESSION[$this->_fmName]['sessionCalls'])){ // Record number of calls
            $_SESSION[$this->_fmName]['sessionCalls'] = $_SESSION[$this->_fmName]['sessionCalls'] + 1;
        } else {
            $_SESSION[$this->_fmName]['sessionCalls'] = 1;
        }

        if(isset($_COOKIE['counter'])) {$_SESSION[$this->_fmName]['counter'] = $_COOKIE['counter'];} // How many pageviews before viewing form

            // Initialize custom Error messages
        if (isset($fmData['formErrorMsg'])){ // initialize any customized form error messages
            for($j=0, $jj=count($this->_formErrorMsg); $j<$jj; $j++){
                if ($fmData['formErrorMsg'][$j] !== ''){$this->_formErrorMsg[$j] = $fmData['formErrorMsg'][$j];}
            }
        }
        if (isset($fmData['fieldErrorMsg'])){ // initialize any customized field error messages
            for($key=array_keys($fmData['fieldErrorMsg']), $k=0, $kk=count($key); $k<$kk; $k++){$this->_fieldErrorMsg[$key[$k]] = $fmData['fieldErrorMsg'][$key[$k]];}
        }
    }

   /**
    * Returns a string of useful data collected outside of any form data.
    *
    * @access   public
    * @return   string
    */
    public function StrExtraData(){
        $string = 'Session calls: ' . $_SESSION[$this->_fmName]['sessionCalls'] . "\n" . 'IP: '. $_SESSION[$this->_fmName]['IP'] . "\n";
        if(isset($_SESSION[$this->_fmName]['counter'])){$string .= 'counter: ' . $_SESSION[$this->_fmName]['counter'] . "\n";}
        return $string;
    }

   /**
    * Sets the max allowable submission time
    *
    * @param    integer
    * @access   public
    */
    public function Set_maxTime($time) {
        $this->_maxTime = $time;
    }

   /**
    * Sets the min allowable submission time
    *
    * @param    integer
    * @access   public
    */
    public function Set_minTime($time) {
        $this->_minTime = $time;
    }

   /**
    * Sets customized $_turingText for Turing test
    *
    * @param    array   an array of strings used for Turing test
    * @access   public
    */
    public function Set_turingText($text) {
        $this->_turingText = $text;
    }

    /**
    * Prints the sessions currently set turingText
    *
    * @access   public
    */
    public function Pr_turingText(){
        echo $_SESSION[$this->_fmName]['turingText'];
    }

   /**
    * Prints the sessions currently set validation link
    *
    * @access   public
    */
    public function Pr_valLink() {
        echo $_SESSION[$this->_fmName]['valLink'];
    }

   /**
    * Prints the sessions currently set submit link
    *
    * @access   public
    */
    public function Pr_submitLink() {
        echo  $_SESSION[$this->_fmName]['submitLink'];
    }

   /**
    * Prints the value of $field
    *
    * @param    string  $field
    * @access   public
    */
    public function Pr_fieldValue($field){
        echo $_SESSION[$this->_fmName]['form'][$field]['value'];
    }

   /**
    * Returns a string of the field value of $field.
    *
    * @access   public
    * @param    string  $field
    * @return   string
    */
    public function Str_fieldValue($field){
        return $_SESSION[$this->_fmName]['form'][$field]['value'];
    }

   /**
    * Prints $string for an option of the html element select ($field), if the value ($value) is selected
    *
    * @param    string  $field
    * @param    string  $value
    * @param    string  $string
    * @access   public
    */
    public function Pr_onFieldSelected($field, $value, $string='selected=\'selected\''){
        if($_SESSION[$this->_fmName]['form'][$field]['value']===$value) {
            echo $string;
        }
    }

   /**
    * Prints $string for a checkbox field, if the value is checked (true)
    *
    * @param    string  $field
    * @param    string  $string
    * @access   public
    */
    public function Pr_onFieldChecked($field, $string=' checked=\'checked\''){
        if($_SESSION[$this->_fmName]['form'][$field]['value']) { // If its already checked
            echo $string; // Show it as checked
        }
    }

   /**
    * Prints a form error message if one exists
    *
    * Can takes two strings that will surround the error message
    *
    * @access   public
    * @param    string  $string1
    * @param    string  $string2
    */
    public function Pr_formErrorMsg($string1 = "", $string2 = ""){
        if($_SESSION[$this->_fmName]['formErrorMsg'] !== ''){
            echo $string1, $_SESSION[$this->_fmName]['formErrorMsg'], $string2;
        }
    }

   /**
    * Prints $field error message if one exists
    *
    * Takes three strings, $field (required), and two strings with which to
    * surrounds the error message.
    *
    * @param    string  $field
    * @param    string  $string1
    * @param    string  $string2
    * @access   public
    */
    public function Pr_fieldErrorMsg($field, $string1 = "", $string2 = ""){
        if($_SESSION[$this->_fmName]['form'][$field]['error']) {
            echo $string1, $_SESSION[$this->_fmName]['form'][$field]['errorMsg'], $string2;
        }
    }

   /**
    * Prints $string on $field error
    *
    * @param    string  $field
    * @param    string  $string
    * @access   public
    */
    public function Pr_onFieldError($field, $string){
        if($_SESSION[$this->_fmName]['form'][$field]['error']) {
            echo $string;
        }
    }

   /**
    * Clears any previous field message errors for a fresh look at the form
    *
    * @access   public
    */
    public function Clear_fieldErrors() {
        for($field=array_keys($_SESSION[$this->_fmName]['form']), $i=0, $ii=count($field); $i<$ii; $i++){
            $_SESSION[$this->_fmName]['form'][$field[$i]]['errorMsg'] = '';
            $_SESSION[$this->_fmName]['form'][$field[$i]]['error'] = false;
        }
    }

   /**
    * Initializes form validation process.
    *
    * Form must be initialized before being validated.
    *
    * Sets $_SESSION[$this->_fmName]['time'] to validate submission time.
    * Sets $_SESSION[$this->_fmName]['valLink'] that must be submitted (as a link: ?validate=xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx)
    * in order to validate the form. This forces initial contact before submission.
    * Sets $_SESSION[$this->_fmName]['turingText'] for possible (reverse) turing test.
    *
    * @access   public
    * @return   bool
    */
    public function InitSession() {
            // Set up data for this session:
        $_SESSION[$this->_fmName]['time'] = time(); // (Re)sets the time form was sent from server (used to test against) (Start time)
        $_SESSION[$this->_fmName]['valLink'] = $this->_LinkUID(); // (Re)sets form submission (validation) link every call
        $_SESSION[$this->_fmName]['turingText'] = array_rand(array_flip($this->_turingText), 1); // Randomly selects a text for Turing Test
           // Precaution: Make sure validation occurs and passes before a valid submit link is even available
        $_SESSION[$this->_fmName]['submitLink'] = 'fail';
        return true;
    }

   /**
    * Attempts to validate the submited form data. Returns true if passed.
    *
    * $_POST data is ignored until this session. Before validation, $_POST (form)
    * data is filled in $_SESSION, so no data is lost on failure, frustrating users.
    *
    * Validation first checks for a valid validation link
    * Validation then checks submission time for being too quick, then being to late.
    * Validation then actually validates data.
    *
    * Upon validation:
    * Sets $_SESSION[$this->_fmName]['submitLink'] that must be submitted
    * (as a link: ?submit=xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx) in order to verify
    * that validation occured and passed. This link is not set until validation
    * passes. (It is also unset [=fail] at every init as a precaution)
    *
    * @access   public
    * @return   bool
    */
    public function ValSession($debug = false) { // Optionally allows for debuging, returning the error number
        if (isset($_SESSION[$this->_fmName]['form']) && isset($_SESSION[$this->_fmName]['valLink']) && isset($_SESSION[$this->_fmName]['time'])) { // test if the needed var are init (if not, either cookies are not set or this was accessed out of order)

                // Add $_POST data to $_SESSION array, done here so data is retained even upon failure, loosing data doesn't hamper bots but does frustrate users
            for($field=array_keys($_SESSION[$this->_fmName]['form']), $i=0, $ii=count($field); $i<$ii; $i++){ // loop through session data fields, this will ignore any posted data that does not correspond with FormData array
                if (isset($_POST[$field[$i]])) { // If data was posted for this field
                    $_SESSION[$this->_fmName]['form'][$field[$i]]['value'] = $_POST[$field[$i]]; // Populate Session with submitted content data
                } else if ($_SESSION[$this->_fmName]['form'][$field[$i]]['type'] === 'checkbox') { // Else if this field is a checkbox (with unsubmitted [unchecked] data because it slipped through the previous if)
                    $_SESSION[$this->_fmName]['form'][$field[$i]]['value'] = false; // unset the checkbox, checkbox data is not sent when checkbox is unchecked
                }
            }

                // Begin validation
            if (($_GET['validate'] === $_SESSION[$this->_fmName]['valLink']) && ($_SESSION[$this->_fmName]['valLink'] !== 'fail')) { // Checks ?validate=x link
                if ($this->_curTime >= ($_SESSION[$this->_fmName]['time'] + $this->_minTime)) { // Test min time
                    if ($this->_curTime <= ($_SESSION[$this->_fmName]['time'] + $this->_maxTime)) { // Test max time
                        if ($this->_ValidateData()){ // Validate data
                            $_SESSION[$this->_fmName]['submitLink'] = $this->_LinkUID(); // Sets a unique final submission link - Validation must pass before this is even created
                            return ($debug)? 0 : true; // Everything Passed
                        } else { // Invalid data
                            $this->InitSession(); // force reinitilization, keep submitted content
                            $_SESSION[$this->_fmName]['formErrorMsg'] = $this->_formErrorMsg[1];
                            return ($debug) ? 1 : false;
                        }
                    } else { // Form has expired
                        $this->InitSession(); // force reinitilization, keep submitted content
                        $_SESSION[$this->_fmName]['formErrorMsg'] = $this->_formErrorMsg[2];
                        return ($debug) ? 2 : false;
                    }
                } else { // Form was submitted too quickly
                    $this->InitSession(); // force reinitilization, keep submitted content
                    $_SESSION[$this->_fmName]['formErrorMsg'] = $this->_formErrorMsg[3];
                    return ($debug) ? 3 : false;
                }
            } else { // wrong validation link
                $this->InitSession(); // force reinitilization, keep submitted content
                $_SESSION[$this->_fmName]['formErrorMsg'] = $this->_formErrorMsg[4];
                return ($debug) ? 4 : false;
            }
        } else { // Cookies have not been set (for sessions) or this func was called before init
            $this->InitSession(); // force reinitilization, keep submitted content
            $_SESSION[$this->_fmName]['formErrorMsg'] = $this->_formErrorMsg[5];
            return ($debug) ? 5 : false;
        }
    }

   /**
    * Validates form data, returns true if form data validates correctly.
    *
    * Loops through each field element then each validation type for each element
    * then attempts to validate. Form error flag, field error flag and field
    * error message(s) are set accordingly.
    *
    * @access   private
    * @return   bool
    */
    private function _ValidateData() {
        $formPassedValidation = true; // I like to be optimistic, this flag is changed upon encountering any errors

            // Loop Through Every Field
        for($field=array_keys($_SESSION[$this->_fmName]['form']), $i=0, $ii=sizeOf($field); $i<$ii; $i++){ // loop through every field
            $_SESSION[$this->_fmName]['form'][$field[$i]]['errorMsg'] = ''; // Clear possible previous errors
            $_SESSION[$this->_fmName]['form'][$field[$i]]['error'] = false; // Reset any field errors

                // if password field ---------------
            if (($_SESSION[$this->_fmName]['form'][$field[$i]]['type'] === 'password') && $_SESSION[$this->_fmName]['form'][$field[$i]]['value'] === ''){ // If field is a password and password is empty
                $this->_SetFieldError($field[$i], 'nopassword');
                $formPassedValidation = false; // password failed, so form failed

                // if select field or radio button -----------------
            } else if (($_SESSION[$this->_fmName]['form'][$field[$i]]['type'] === 'select') || ($_SESSION[$this->_fmName]['form'][$field[$i]]['type'] === 'radio')) {
                    // loop through possible values
                for($j=0, $jj=sizeOf($_SESSION[$this->_fmName]['form'][$field[$i]]['posValues']); $j<$jj; $j++){
                    if ($_SESSION[$this->_fmName]['form'][$field[$i]]['value'] === $_SESSION[$this->_fmName]['form'][$field[$i]]['posValues'][$j]){
                        $equalsPosValue = true;
                        break; // stop the loop through possible values
                    }
                    $equalsPosValue = false;
                }
                if(!$equalsPosValue){ // It did not equal a possible value
                    $_SESSION[$this->_fmName]['form'][$field[$i]]['errorMsg'] = $this->_fieldErrorMsg['selectnovalue'];
                    $_SESSION[$this->_fmName]['form'][$field[$i]]['error'] = true;
                    $formPassedValidation = false; // select failed, so form failed
                }

                // if other field -----------------
            } else { // Else validate with validation types
                for($k=0, $kk=sizeOf($_SESSION[$this->_fmName]['form'][$field[$i]]['valTypes']); $k<$kk; $k++){ // loop through every validation type of the field

                    switch ($_SESSION[$this->_fmName]['form'][$field[$i]]['valTypes'][$k]) { // Validate according to the validation type

                        case 'required': // Required Validation -------------------
                            if($_SESSION[$this->_fmName]['form'][$field[$i]]['value'] == ''){
                                $this->_SetFieldError($field[$i], 'required');
                                $formPassedValidation = false;
                                break 2; // break out of validation loop
                            }
                            break;

                        case 'email': // Email Validation -------------------------
                            if(!filter_var($_SESSION[$this->_fmName]['form'][$field[$i]]['value'], FILTER_VALIDATE_EMAIL)){
                                $this->_SetFieldError($field[$i], 'email');
                                $formPassedValidation = false;
                                break 2; // break out of validation loop
                            }
                                $email = $_SESSION[$this->_fmName]['form'][$field[$i]]['value']; // get email
                                $domain = substr($email, strrpos($email, "@")+1); // get domain
                                //TODO: maybe just validate first part and check dnsrecord of domain??
                            if(!(checkdnsrr($domain, 'MX')) || !(checkdnsrr($domain, 'A'))){ // does email dns have a or mx record
                                $this->_SetFieldError($field[$i], 'email');
                                $formPassedValidation = false;
                                break 2; // break out of validation loop
                            }
                            break;

                        case 'name': // Name Validation ---------------------------
                            if(!filter_var($_SESSION[$this->_fmName]['form'][$field[$i]]['value'], FILTER_VALIDATE_REGEXP, array('options'=>array('regexp'=>'/^[A-Za-z0-9 _]*$/')))){
                                $this->_SetFieldError($field[$i], 'name');
                                $formPassedValidation = false;
                                break 2; // break out of validation loop
                            }
                            break;

                        case 'max25': // max25 Validation -------------------------
                            if(strlen($_SESSION[$this->_fmName]['form'][$field[$i]]['value']) >= 26){
                                $this->_SetFieldError($field[$i], 'max25');
                                $formPassedValidation = false;
                                break 2; // break out of validation loop
                            }
                            break;

                        case 'max5000': // max25 Validation -------------------------
                            if(strlen($_SESSION[$this->_fmName]['form'][$field[$i]]['value']) >= 5000){
                                $this->_SetFieldError($field[$i], 'max25');
                                $formPassedValidation = false;
                                break 2; // break out of validation loop
                            }
                            break;

                        case 'message': // Message Validation ---------------------
                            $_SESSION[$this->_fmName]['form'][$field[$i]]['value'] = filter_var($_SESSION[$this->_fmName]['form'][$field[$i]]['value'], FILTER_SANITIZE_STRING); // Sanatize data
                            if(strlen($_SESSION[$this->_fmName]['form'][$field[$i]]['value']) >= 5000){
                                $this->_SetFieldError($field[$i], 'message');
                                $formPassedValidation = false;
                                break 2; // break out of validation loop
                            }
                            break;

                        case 'min2': // max25 Validation -------------------------
                            if(strlen($_SESSION[$this->_fmName]['form'][$field[$i]]['value']) < 2){
                                $this->_SetFieldError($field[$i], 'min2');
                                $formPassedValidation = false;
                                break 2; // break out of validation loop
                            }
                            break;

                        case 'usTelephone': // Phone Validation -------------------------
                            if(!filter_var($_SESSION[$this->_fmName]['form'][$field[$i]]['value'], FILTER_VALIDATE_REGEXP, array('options'=>array('regexp'=>'/^(?:(?:\+?1\s*(?:[.-]\s*)?)?(?:\(\s*([2-9]1[02-9]|[2-9][02-8]1|[2-9][02-8][02-9])\s*\)|([2-9]1[02-9]|[2-9][02-8]1|[2-9][02-8][02-9]))\s*(?:[.-]\s*)?)?([2-9]1[02-9]|[2-9][02-9]1|[2-9][02-9]{2})\s*(?:[.-]\s*)?([0-9]{4})(?:\s*(?:#|x\.?|ext\.?|extension)\s*(\d+))?$/')))){
                                $this->_SetFieldError($field[$i], 'usTelephone');
                                $formPassedValidation = false;
                                break 2; // break out of validation loop
                            }
                            break;

                        case 'turing': // Turing Validation -----------------------
                            if($_SESSION[$this->_fmName]['form'][$field[$i]]['value'] !== $_SESSION[$this->_fmName]['turingText']){
                                $this->_SetFieldError($field[$i], 'turing');
                                $_SESSION[$this->_fmName]['form'][$field[$i]]['value'] = ''; // Clear value
                                $formPassedValidation = false;
                                break 2; // break out of validation loop
                            }
                            break;

                        case 'empty': // Empty Validation -------------------------
                            if($_SESSION[$this->_fmName]['form'][$field[$i]]['value'] !== ''){
                                $this->_SetFieldError($field[$i], 'empty');
                                $_SESSION[$this->_fmName]['form'][$field[$i]]['value'] = ''; // Clear value
                                $formPassedValidation = false;
                                break 2; // break out of validation loop
                            }
                            break;

                        default: // If valTypes case doesn't exist, just falls right on through...
                            break;
                    } // End of the Switch
                } // End of loop through validation types
            } // End of else, all other fields
        } // End of loop through fields
        return $formPassedValidation;
    }

   /**
    * Sets a field error and field error message for a field based on $valType
    *
    * @param    string   $field
    * @param    string   $valType
    * @access   private
    */
    private function _SetFieldError($field, $valType) {
        $_SESSION[$this->_fmName]['form'][$field]['error'] = true;
        $_SESSION[$this->_fmName]['form'][$field]['errorMsg'] = $this->_fieldErrorMsg[$valType];
    }

   /**
    * Continues Session, checks for a valid submission link, returns true upon complete validaion.
    *
    * @access   public
    * @return   bool
    */
    public function SubmitSession($debug = false) {
        if(((isset($_GET['submit'])) && isset($_SESSION[$this->_fmName]['submitLink'])) && (!($_SESSION[$this->_fmName]['submitLink'] == 'fail')) && ($_GET['submit']) == $_SESSION[$this->_fmName]['submitLink']) { // If variables exist and they agree (and link is not set to default)
            return ($debug)? 0 : true;
        } else { // wrong submition link
            $this->InitSession(); // force reinitilization, keep submitted content
            $_SESSION[$this->_fmName]['formErrorMsg'] = $this->_formErrorMsg[6];
            return ($debug)? 6 : false; // Return error (6)
        }
    }

   /**
    * Clears all form fields
    *
    * @access   public
    * @return   bool
    */
    public function ClearFields() {
        for($field=array_keys($_SESSION[$this->_fmName]['form']), $i=0, $ii=count($field); $i<$ii; $i++){ // loop through all fields
            $_SESSION[$this->_fmName]['form'][$field[$i]]['value'] = ''; // clear the values
        }
    }

   /**
    * Deletes form session data
    *
    * @access   public
    * @return   bool
    */
    public function DelSession() {
        unset($_SESSION[$this->_fmName]);
    }

   /**
    * Returns a 32 character unique identifier used for links.
    *
    * @access   private
    * @return   string
    */
    private function _LinkUID() {
        return md5($_SERVER['REMOTE_ADDR'] . "Harold's salt" . microtime()); // just mix it up...
    }

}
?>
