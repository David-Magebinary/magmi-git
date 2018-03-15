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
        static::$message .= PHP_EOL . $message;
    }

    public static function getMessage()
    {
        $this->sendMail(static::$message);
        return static::$message;
    }

    public static function addErrorMessage($errMessage)
    {
        static::$errorMessage .= PHP_EOL . $errMessage;
    }

    public static function getErrorMessage()
    {
        $this->sendMail(static::$errorMessage);
        return static::$errorMessage;
    }

    public function sendMail($message)
    {
        if (isset($message)) {
            $receivers = ["david@magebinary.com"];

            foreach ($receivers as $to) {
                $subject = date('Y-M-D-H-I-S') . "-vendor-import-report";

                // compose headers
                $headers = "From: playtech.co.nz" . PHP_EOL;
                $headers .= "Reply-To: david@magebinary.com" . PHP_EOL;
                $headers .= "X-Mailer: PHP/".phpversion();

                // send email
                mail($to, $subject, $message, $headers);
            }
        }
    }
}