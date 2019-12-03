<?php

class EmailReportPlugin extends Magmi_GeneralImportPlugin
{
    /**
     * @var string
     */
    const HTML_NEW_LINE = '<br />';

    protected $_attach;

    /**
     * @var array
     */
    protected $_params;

    public function initialize($params)
    {
        $this->_attach = array();
        $this->_params = $params;
    }

    public function getPluginInfo()
    {
        return array(
            "name"      => "Import Report Mail Notifier",
            "author"    => "David Qian",
            "version"   => "1.1.0",
            "url"       =>  $this->pluginDocUrl("Import_report_mail_notifier")
        );
    }

    public function beforeImport()
    {
        $engine = $this->_callers[0];
        $content = '';
        $datasource = $engine->getPluginInstanceByClassName('datasources', 'Magmi_CSVDataSource');
        if ($datasource) {
            $csvfile = $datasource->getParam('CSV:filename');

            $content .= '<html><body>';
            $content .= sprintf('<b>Import Mode:</b> %s', $this->getMode()) . self::HTML_NEW_LINE;
            $content .= sprintf('<b>Profile:</b> %s', $this->_params['profile']) . self::HTML_NEW_LINE;
            $content .= '<b>Messages:</b>' . self::HTML_NEW_LINE;
            $content .= sprintf('The import job for file %s is going to start.', $csvfile) . self::HTML_NEW_LINE;
            $content .= '</body></html>';

            $this->addAttachment($csvfile);
        }

        $response = $this->send_email($this->getParam("EMAILREP:to"), $this->getParam("EMAILREP:from"), $this->getParam("EMAILREP:from_alias", ""), "BinaryConnect before import notice", $content, $this->getAttachment());

        if (!$response) {
            $this->log("Cannot send email", "error");
        }
    }

    public function send_email($to, $from, $from_name, $subject, $message, $attachments = false)
    {
        $headers = 'From: ' . $from_name . "<" . $from . ">\n";
        $headers .= "Reply-To: " . $from_name . "<" . $from . ">\n";
        $headers .= "Return-Path: " . $from_name . "<" . $from . ">\n";
        $headers .= "Message-ID: <" . time() . "-" . $from . ">\n";
        $headers .= "Date: " . date('r', time()) . "\n"; // Wed, 15 Jan 2014 11:00:13 +0000
        $headers .= "X-Mailer: PHP v" . phpversion();

        $msg_txt = "";
        $email_txt = $message . "\n";

        $semi_rand = md5(time());
        $mime_boundary = "==Multipart_Boundary_x{$semi_rand}x";

        $headers .= "\nMIME-Version: 1.0\n" . "Content-Type: multipart/mixed;\n" . " boundary=\"{$mime_boundary}\"";

        $email_txt .= $msg_txt;
        $email_message = $email_txt;
        $email_message .= "This is a multi-part message in MIME format.\n\n" . "--{$mime_boundary}\n" .
        "Content-Type:text/html; charset=\"iso-8859-1\"\n" . "Content-Transfer-Encoding: 7bit\n\n" . $email_txt .
        "\n\n";

        $attachments = $this->_attach;
        if ($attachments !== false) {

        //Should we zip them?
            $zip = $this->getParam("EMAILREP:attachcsv",false);
            $this->log("Zip: $zip", "info");
            if ($zip){
                $archive = new ZipArchive();
                $fname = sys_get_temp_dir() . '/report.zip';
                if ($archive->open($fname,ZipArchive::OVERWRITE) === true){
                  for ($i = 0; $i < count($attachments); $i++){
                     if (!is_file($attachments[$i])) continue;
                     $fileatt_name = explode(DIRECTORY_SEPARATOR,$attachments[$i]);
                     $fileatt_name = array_pop($fileatt_name);
                     $archive->addFile($attachments[$i],$fileatt_name);
                 }
                 $archive->close();

                 $fileatt = $fname;
                 $fileatt_type = "application/octet-stream";
                 $fileatt_name = "report.zip";
                 $file = fopen($fileatt,'rb');
                 $data = fread($file,filesize($fileatt));
                 fclose($file);
                 $data = chunk_split(base64_encode($data));

                 $email_message .= "--{$mime_boundary}\n" . "Content-Type: {$fileatt_type};\n" .
                 " name=\"{$fileatt_name}\"\n" . "Content-Transfer-Encoding: base64\n\n" . $data . "\n\n";
             } else {
                $email_message .= "\n\nThere was a problem compressing your report\n\n";
            }

            } else {

                for ($i = 0; $i < count($attachments); $i++) {
                    if (is_file($attachments[$i])) {
                        $fileatt = $attachments[$i];
                        $fileatt_type = "application/octet-stream";
                        $start = strrpos($attachments[$i], '/') == -1 ? strrpos($attachments[$i], '//') : strrpos(
                            $attachments[$i], '/') + 1;
                        $fileatt_name = substr($attachments[$i], $start, strlen($attachments[$i]));

                        $file = fopen($fileatt, 'rb');
                        $data = fread($file, filesize($fileatt));
                        fclose($file);

                        $data = chunk_split(base64_encode($data));

                        $email_message .= "--{$mime_boundary}\n" . "Content-Type: {$fileatt_type};\n" .
                        " name=\"{$fileatt_name}\"\n" . "Content-Transfer-Encoding: base64\n\n" . $data . "\n\n";
                    }
                }
            }
        }

        $email_message .= "--{$mime_boundary}--\n";
        $this->log("Sending report to : $to", "info");
        $ok = mail($to, $subject, $email_message, $headers);
        // clean up the attachement array before sending out the next email
        $this->_attach = [];
        return $ok;
    }

    public function addAttachment($fname)
    {
        $this->_attach[] = $fname;
        $this->_attach = array_unique($this->_attach);
    }

    public function getAttachment()
    {
        return $this->_attach;
    }

    public function getPluginParams($params)
    {
        $pp = array();
        foreach ($params as $k => $v) {
            if (preg_match("/^EMAILREP:.*$/", $k)) {
                $pp[$k] = $v;
            }
        }
        return $pp;
    }

    public function afterImport()
    {
        $eng = $this->_callers[0];
        $csvfile = '';
        if ($this->getParam("EMAILREP:to", "") != "" && $this->getParam("EMAILREP:from", "") != "") {
            if ($this->getParam("EMAILREP:attachcsv", false) == true) {
                $ds = $eng->getPluginInstanceByClassName("datasources", "Magmi_CSVDataSource");
                if ($ds != null) {
                    $csvfile = $ds->getParam("CSV:filename");
                    $this->addAttachment($csvfile);
                }
            }

            // if ($this->getParam("EMAILREP:attachlog", false) == true) {
            //     // copy magmi report
            //     $pfile = Magmi_StateManager::getProgressFile(true);
            //     $this->addAttachment($pfile);
            // }

            // $ok = $this->send_email($this->getParam("EMAILREP:to"), $this->getParam("EMAILREP:from"),
            //     $this->getParam("EMAILREP:from_alias", ""), $this->getParam("EMAILREP:subject", "Magmi import report"),
            //     $this->getParam("EMAILREP:body", "report attached"), $this->getAttachment());
            // if (!$ok) {
            //     $this->log("Cannot send email", "error");
            // }
        }

        // if price alert plugin is working
        if (file_exists(PriceChangeAlert::ALERT_FILE) && filesize(PriceChangeAlert::ALERT_FILE)) {
            $content = '<html><body>';
            $content .= sprintf('<b>Import Mode:</b> %s', $this->getMode()) . self::HTML_NEW_LINE;
            $content .= sprintf('<b>Profile:</b> %s', $this->_params['profile']) . self::HTML_NEW_LINE;
            $content .= '<b>Messages:</b>' . self::HTML_NEW_LINE;
            $content .= sprintf('The import job for the file %s is finished.', $csvfile) . self::HTML_NEW_LINE;
            $content .= '</body></html>';
            $this->addAttachment(PriceChangeAlert::ALERT_FILE);
            $response = $this->send_email(
                $this->getParam("EMAILREP:to"),
                $this->getParam("EMAILREP:from"),
                $this->getParam("EMAILREP:from_alias", ""),
                "BinaryConnect Significant Price Change Alert",
                $content,
                $this->getAttachment()
            );

            if (!$response) {
                $this->log("Cannot send email", "error");
            }
        }
    }
}
