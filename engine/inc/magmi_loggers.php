<?php

abstract class Magmi_Logger
{
    abstract public function log($data, $type = null);
}

class FileLogger extends Magmi_Logger
{
    protected $_fname;

    public function __construct($fname = null)
    {
        if ($fname == null) {
            $fname = Magmi_StateManager::getProgressFile(true);
        }
        $this->_fname = $fname;
        $f = fopen($this->_fname, "w");
        if ($f == false) {
            throw new Exception("CANNOT WRITE PROGRESS FILE ");
        }
        fclose($f);
    }

    public function log($data, $type = null)
    {
        $f = fopen($this->_fname, "a");
        if ($f == false) {
            throw new Exception("CANNOT WRITE PROGRESS FILE ");
        }
        $data = preg_replace("/(\r|\n|\r\n)/", "<br>", $data);
        if ($type == null) {
            $type = "default";
        }
        fwrite($f, "$type:$data\n");
        fclose($f);
    }
}

class EchoLogger extends Magmi_Logger
{
    public function log($data, $type = null)
    {
        if ($type != null) {
            $info = explode(";", $type);
            $type = $info[0];
        } else {
            $type = "default";
        }
        echo('<p class="logentry log_' . $type . '">' . $data . "</p>");
    }
}

class CLILogger extends Magmi_Logger
{
    public function log($data, $type = null)
    {
        echo("$type:$data\n");
    }
}


class BinaryConnectLogger extends Magmi_Logger
{
    protected $fileName = '/tmp/magmi.log';

    public function log($data, $type = null)
    {
        date_default_timezone_set('Pacific/Auckland');
        $f = fopen($this->fileName, "a");
        if ($f == false) {
            fwrite($f, '');
             if (!file_exists($this->fileName)) {
                throw new Exception("CANNOT WRITE MAGMI PROGRESS FILE ");
            }
        }
        $data = preg_replace("/(\r|\n|\r\n)/", "<br>", $data);
        if ($type == null) {
            $type = "default";
        }
        fwrite($f, "[". date('Y-m-d H:i:s') ."]" . "$type:$data\n");
        fclose($f);
    }
}
