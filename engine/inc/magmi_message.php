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
        return static::$message;
    }

    public static function addErrorMessage($errMessage)
    {
        static::$errorMessage .= PHP_EOL . $errMessage;
    }

    public static function getErrorMessage()
    {
        return static::$errorMessage;
    }
}