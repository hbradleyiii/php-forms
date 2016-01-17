<?php
/**
 * Default form class extending php-forms
 *
 */

include('php-forms.php'); // include the validation class


class defaultForm extends phpForm {

   /**
    * Constructor function for defaultForm
    *
    * Parses json form data then calls phpForm constructor
    *
    */
    public function __construct(){

        $jsonData = file_get_contents("defaultForm.json"); // Collect form data
        if ($jsonData === false){exit("ERROR: Unable to open json file");} // Crash and burn
        $phpFormData = json_decode(substr($jsonData, strpos($jsonData, "{")), true); // Remove the extra javascript stuff (everything before first '{') and decode the json

        phpForm::__construct($phpFormData); // run parent constructor
    }

   /**
    * Displays the form ;-)
    *
    */
    public function displayForm() { // Passed a reference to the instantiated validation class object ?>
        <form action="controller.php?validate=<?php $this->Pr_valLink(); ?>" method="post">
<?php $this->Pr_formErrorMsg("<h3 id=\"formErrorMsg\">", "</h3>"); // use the 2 param to surround error msg ?>
            <div><?php // Label is expected to have for attribute ?>
                <label class="hide" for="empty">Do not put anything in this field: <?php $this->Pr_fieldErrorMsg('empty', "<span><strong  class=\"errorMsg\">", "</strong></span>"); ?></label>
                <input type="text" class="hide<?php $this->Pr_onFieldError("empty", " fieldError"); ?>" id="empty" name="empty" value="<?php $this->Pr_fieldValue("empty"); ?>">
            </div>

            <div>
                <label for="firstName">First Name: <span><abbr title="Required field">*</abbr><?php $this->Pr_fieldErrorMsg('firstName', "<strong>", "</strong>"); ?></span></label>
                <input type="text" <?php $this->Pr_onFieldError("firstName", 'class="fieldError"'); ?> id="firstName" name="firstName" value="<?php $this->Pr_fieldValue("firstName"); ?>">
            </div>
            <div>
                <label for="lastName">Last Name: <span><abbr title="Required field">*</abbr><?php $this->Pr_fieldErrorMsg('lastName', "<strong>", "</strong>"); ?></span></label>
                <input type="text" <?php $this->Pr_onFieldError("lastName", 'class="fieldError"'); ?> id="lastName" name="lastName" value="<?php $this->Pr_fieldValue("lastName"); ?>">
            </div>
            <div>
                <label for="usTelephone">Phone Number: <span><abbr title="Required field">*</abbr><?php $this->Pr_fieldErrorMsg('usTelephone', "<strong>", "</strong>"); ?></span></label>
                <input type="text" <?php $this->Pr_onFieldError("usTelephone", 'class="fieldError"'); ?> id="usTelephone" name="usTelephone" value="<?php $this->Pr_fieldValue("usTelephone"); ?>">
            </div>
            <div>
                <label for="email">Email address: <span><abbr title="Required field">*</abbr><?php $this->Pr_fieldErrorMsg('email', "<strong>", "</strong>"); ?></span></label>
                <input type="text" <?php $this->Pr_onFieldError("email", 'class="fieldError"'); ?> id="email" name="email" value="<?php $this->Pr_fieldValue("email"); ?>">
            </div>
            <div>
                <label for="car">What Car do you drive: <span><abbr title="Required field">*</abbr><?php $this->Pr_fieldErrorMsg('car', "<strong>", "</strong>"); ?></span></label>
                <select <?php $this->Pr_onFieldError("car", 'class="fieldError"'); ?> id="car" name="car">
                    <option value="" <?php $this->Pr_onFieldSelected("car", "")?>></option>
                    <option value="volvo" <?php $this->Pr_onFieldSelected("car", "volvo")?>>Volvo</option>
                    <option value="saab" <?php $this->Pr_onFieldSelected("car", "saab")?>>Saab</option>
                    <option value="mercedes" <?php $this->Pr_onFieldSelected("car", "mercedes")?>>Mercedes</option>
                    <option value="audi" <?php $this->Pr_onFieldSelected("car", "audi")?>>Audi</option>
                </select>
            </div>
            <div>
                <label id="message-label" for="message">Message: <span><abbr title="Required field">*</abbr><?php $this->Pr_fieldErrorMsg('message', "<strong>", "</strong>"); ?></span></label>
                <textarea rows=5 cols=40 <?php $this->Pr_onFieldError("message", 'class="fieldError"'); ?> id="message" name="message"><?php $this->Pr_fieldValue("message"); ?></textarea>
            </div>
            <div>
                <label for="bike" class="checkbox"><?php $this->Pr_fieldErrorMsg('bike', "<span><strong>", "</strong></span>"); ?>I have a bike</label>
                <input type="checkbox" class="checkbox<?php $this->Pr_onFieldError("bike", " fieldError"); ?>" name="bike" value="bike"<?php $this->Pr_onFieldChecked("bike"); ?> />
            </div>
            <div>
                <label id="turingbox-label" for="turingbox">Please type the following to help prevent automated scripts: <em id="turingtext"><?php $this->Pr_turingText(); ?></em><span><abbr title="Required field">*</abbr><?php $this->Pr_fieldErrorMsg('turingbox', "<strong>", "</strong>"); ?></span></label>
                <input type="text" <?php $this->Pr_onFieldError("turingbox", 'class="fieldError"'); ?> id="turingbox" name="turingbox" value="<?php $this->Pr_fieldValue("turingbox"); ?>" />
            </div>

            <div>
            <input class="submit" type="submit" value="Submit">
            </div>
      </form><?php
      }

   /**
    * Displays submitted form data
    *
    * @return  string    $strData   a string containing the data collected by the form
    */
    public function displayData() { // Passed a reference to the instantiated validation class object ?>

        <strong>First Name:</strong> <?php $this->Pr_fieldValue("firstName"); ?><br />
        <strong>Last Name:</strong> <?php $this->Pr_fieldValue("lastName"); ?><br />
        <strong>Email:</strong> <?php $this->Pr_fieldValue("email"); ?><br />
        <strong>Phone Number:</strong> <?php $this->Pr_fieldValue("usTelephone"); ?><br />
        <strong>Car:</strong> <?php $this->Pr_fieldValue("car"); ?><br />
        <strong>Message:</strong> <?php $this->Pr_fieldValue("message"); ?><br />
        <?php if($this->Pr_fieldValue("bike")){echo "I have a bike<br />";}?><br />


        <a href="controller.php?submit=delete"> Delete</a>
        <a href="controller.php"> Edit</a>
        <a href="controller.php?submit=<?php $this->Pr_submitLink(); ?>"> Submit</a><?php
    }

   /**
    * Collects form data and returns it as a string
    *
    * @return  string    $strData   a string containing the data collected by the form
    */
    public function strData() { // Passed a reference to the instantiated validation class object
        $strData = $this->StrExtraData();

        $strData .= "First Name: " . "\t\t\t" . $this->Str_fieldValue("firstName") . "\n";
        $strData .= "Last Name: " . "\t\t\t" . $this->Str_fieldValue("lastName") . "\n";
        $strData .= "Phone Number: " . "\t\t\t" . $this->Str_fieldValue("usTelephone") . "\n";
        $strData .= "Email: " . "\t\t\t\t" . $this->Str_fieldValue("email") . "\n";
        $strData .= "Car: " . "\t\t\t\t" . $this->Str_fieldValue("car") . "\n";
        $strData .= "Has a Bike: " . "\t\t\t" . $this->Str_fieldValue("bike") . "\n";
        $strData .= "Message: " . "\n" . $this->Str_fieldValue("message") . "\n";

        return $strData;
    }

}
?>
