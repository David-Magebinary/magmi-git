<?php
/**
 * The price change alert plugin should be ran at earily stage before the empire filler kicked in
 */
class PriceChangeAlert extends Magmi_ItemProcessor
{
    const ALERT_FILE = '/tmp/magmi/priceAlert.csv';
    const THRESHOLD = 0.2;

    /**
     * @var string
     */
    protected $_priceColumnName;

    public function getPluginInfo()
    {
        return array(
            "name"      => "Price Change Alert",
            "author"    => "Siyu Qian",
            "version"   => "0.0.1",
            "url"       => 'http://wiki.magebinary.com'
        );
    }

    public function beforeImport()
    {
        // remove the file first
        if (file_exists(self::ALERT_FILE)) {
            if (!unlink(self::ALERT_FILE)) {
                $this->log('Price alert file cannot be deleted.', 'error');
            } else {
                $this->log('Price alert file has been deleted', 'info');
            }
        }
    }

    public function getPluginParams($params)
    {
        $pp = array();

        foreach ($params as $k=>$v) {
            if (preg_match("/^PCA:.*$/", $k)) {
                $pp[$k]=$v;
            }
        }

        return $pp;
    }

    public function processItemBeforeId(&$item, $params = null)
    {
        $prefixes = explode(',', $this->getParam("PCA:prefix"));
        $threshold = $this->getParam('PCA:threshold', self::THRESHOLD);
        $itemData = $this->getItemIds($item);
        // the product does not exist in the system, then price change alert logic should not be needed
        if (!$itemData || !isset($itemData['pid'])) {
            return true;
        }

        $importData = $item;
        $header = ['sku', 'supplier_code', 'orig_price', 'new_price', 'changes', 'timestamp'];

        $fileFolder = "/tmp/magmi";

        if (!file_exists($fileFolder)) {
            mkdir($fileFolder, 0777, true);
        }

        $fileHandler = fopen(self::ALERT_FILE, "a+");

        $magentoValue = $this->getMagentoData($item, array('product_id' => $itemData['pid']));
        $newValue = $item['price'];
        $origValue = $magentoValue['price'];

        foreach ($prefixes as $prefix) {
            $prefix = trim($prefix);
            $priceColumnName = $prefix . "price";
            if (in_array($priceColumnName, array_keys($importData))) {
                if (!isset($item[$priceColumnName]) || !isset($magentoValue[$priceColumnName])) {
                    $this->log(json_encode($item), "info");
                    $this->log(json_encode($magentoValue), "info");
                    $this->log(sprintf("%s field is not existing in Magento or Imported CSV file. Skipping price alert for #%i", $priceColumnName, $this->getCurrentRow()), "warning");
                    // if Magento does not have the value or the sheet is having issue
                    return true;
                }
                $this->_priceColumnName = $priceColumnName;
                $newValue = $item[$priceColumnName];
                $origValue = $magentoValue[$priceColumnName];
            }
        }

        $ratio = abs($newValue - $origValue) / $origValue;
        $precentage = round($ratio * 100, 2) . "%";
        $timestamp = new DateTime(null, new DateTimeZone('Pacific/Auckland'));
        if ($ratio > $threshold) {
            // your file is empty
            if (!filesize(self::ALERT_FILE)) {
                fputcsv($fileHandler, $header);
            }
            $alertRow = [ $importData['sku'], $importData['supplier_code'], $origValue, $newValue, $precentage, $timestamp->format('Y-m-d H:i:s') ];
            fputcsv($fileHandler, $alertRow);

            // need to skip the update of the price attributes
            if ($this->getParam('PCA:skipping', 1)) {
                $item[$this->_priceColumnName] = $origValue;
            }
        }

        fclose($fileHandler);
        // for get correct file size after write operation
        clearstatcache();
        return true;
    }
}