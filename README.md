# php-forms
A php class for managing html forms and preventing bot submissions.

Generally, I've used this code as a starting point or a "boilerplate," using
only the pieces I need.  This is legacy code uploaded to github for archiving
purposes.  Use at your own risk.

Harold Bradley III
http://www.haroldbradleyiii.com/
Copyright 2010
version 1.2
license GNU Lesser General Public License
(http://www.gnu.org/copyleft/lesser.html)

## Form Validation

In php-forms, forms go through three sessions:
 1. initSession() - the data for the form is initialized and the form is
    presented to the user.
 2. valSession() - the data for the form is submitted by the user to this to be
    validated.
 3. submitSession() - the data for the form was presented for the user to
    check, the user clicked the link; this function checks the link and
    validates it.

These three methods return true or false, the data must be handled
appropriately by the controller.

'Pr' methods are used for printing specific form data.
'Str' methods are used for returning strings of specific form data.
'Set' methods are used for setting private values.

## Session Data

'''php
$fmName                              // The name of the current form, this
                                     // allows for possible multiple forms per
                                     // page.
$_SESSION[$fmName]['form']           // An associative array containing the data
                                     // for the form, indices are fields.
$_SESSION[$fmName]['formErrorMsg']   // A string containing '', or the current
                                     // form Error message
$_SESSION[$fmName]['hbCounter']      // A cookie page counter, set the first
                                     // time this is called.
$_SESSION[$fmName]['IP']             // The ip address of the current user.
$_SESSION[$fmName]['submitLink']     // A string containing a 32 character
                                     // submit link.
$_SESSION[$fmName]['sessionCalls']   // The number of times this class has been
                                     // constructed.
$_SESSION[$fmName]['time']           // The time that the initSession was last
                                     // called.
$_SESSION[$fmName]['turingText']     // A string containing a random turing text
                                     // the user is to type.
$_SESSION[$fmName]['valLink']        // A string containing a 32 character
                                     // validation link.

'''

## Files

php-forms.inc.php

  - Contains the class php-forms that does most of the validation
    work

*Form.json

  - Contains the data that is used for the form

  "errorTags"               describes the tags (in order) that should be used
                            inside the label element to surround the error
                            message (see "errorTagsBranches") If this is not
                            declared, the error message will NOT be used.

  "errorTagsBranches"       describes the branch that the corresponding index
                            of "errorTags" is referring to. If this is not
                            declared, a default index of 0 will be used.


*Form.inc.php

  - Contains the form


*(controller).php

  - Contains the controlling scripts that determine what page is being loaded
    and runs php-forms methods accordingly
