<?php
 require_once "Mail.php";

 // You MUST change email address to a real email address.

 $from = "Mail Form <email>";
 $to = "<email>";
 $subject = "Contact form";
 $body = $phpForm->hbStrData();

 $host = "ssl://host";
 $port = "465";
 $username = "username";
 $password = "password";

 $headers = array ('From' => $from,
   'To' => $to,
   'Subject' => $subject);
 $smtp = Mail::factory('smtp',
   array ('host' => $host,
     'port' => $port,
     'auth' => true,
     'username' => $username,
     'password' => $password));

 $mail = $smtp->send($to, $headers, $body);

 ?><!DOCTYPE html>
<html lang="en">
   <head>
      <meta charset="utf-8">
      <title>php-forms</title>
      <link rel="stylesheet" type="text/css" href="css/php-forms.css">
   </head>
   <body>
<?php if (PEAR::isError($mail)) { echo("<h1>" . $mail->getMessage() . "</h1>");
  } else { echo("<h1>Your form has been submitted!</h1>"); } ?>

   </body>
</html>
