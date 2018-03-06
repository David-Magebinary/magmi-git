<?php
// TODO: quick fix of log improvement

///////////////
// Dirty fix //
///////////////

class Magmi_Message
{
    public static $message;

    public static $errorMessage;

    public static function addMessage($message)
    {
        static::$message .= $message;
    }

    public static function getMessage()
    {
        return static::$message;
    }

    public static function addErrorMessage($errMessage)
    {
        static::$errorMessage .= $errMessage;
    }

    public static function getErrorMessage()
    {
        if (isset(static::$errorMessage)) {
            $to = "david@magebinary.com";
            $subject = date('Y-M-D') . "-vendor-import-error-report";

            // compose headers
            $headers = "From: playtech.co.nz" . PHP_EOL;
            $headers .= "Reply-To: david@magebinary.com" . PHP_EOL;
            $headers .= "X-Mailer: PHP/".phpversion();

            // send email
            mail($to, $subject, static::$errorMessage, $headers);
        }
        return static::$errorMessage;
    }
}