<?php
/**
 * Default Controller phpForm-1.2
 * Sept. 14, 2010
 * Harold Bradley III
 *
 */

include('defaultForm.inc.php'); // include the form

$phpForm = new defaultForm(); // create the class

if (isset($_GET['submit'])){ // Try stage 3
    if ($_GET['submit'] === 'delete'){
         $phpForm->DelSession();
         header( 'Location: controller.php' ) ; // redirect
     } else if($phpForm->SubmitSession()) {
          include('views/submit.inc.php'); // Submit form
     } else {
         include('views/formview.inc.php'); // Show form with error
     }
 } else if (isset($_GET['validate'])){ // Try stage 2
    // validate
    if($phpForm->ValSession()) { // If validates
        include('views/validatedview.inc.php'); // Present data to user
    } else {
        include('views/formview.inc.php'); // Show form with error
    }
} else { // Begin stage 1
    $phpForm->InitSession(); // initialize session
    $phpForm->Clear_fieldErrors(); // clear any previous errors
    include('views/formview.inc.php'); // Show form
}
?>
