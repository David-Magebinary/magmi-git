<?php

class MultiSkuFinderItemProcessor extends Magmi_ItemProcessor
{
    /**
     * @var boolean
     */
    protected $_compatibility = false;

    /**
     * Display plugin basic information
     */
    public function getPluginInfo()
    {
        return array(
            'name'      => 'Multiple SKU Finder',
            'author'    => 'Siyu Qian',
            'version'   => '0.0.1',
            'url'       => 'http://wiki.magebinary.com'
    }

    public function getPluginParams($params)
    {
        $pp = array();

        foreach ($params as $k=>$v) {
            if (preg_match("/^MSKUF:.*$/", $k)) {
                $pp[$k]=$v;
            }
        }

        return $pp;
    }

    public function processItemBeforeId(&$item, $params = null)
    {
        $param = trim($this->getParam("MSKUF:matchfield");

        // configuration is not set up
        if (!$param) {
            $this->log("No value for the multiskufinder was found in the configuration", "warning");
            return true;
        }

        $fields = explode(',', trim($this->getParam("MSKUF:matchfield")));

        foreach ($fields as $key => $matchfield) {
            $matchfield = trim($matchfield);

            // protection from tricky testers ;)
            if ($matchfield == "sku") {
                return true;
            }

            // check if the column existing in the processed file
            if (!isset($item[$matchfield])) {
                continue;
            }

            $attinfo = $this->getAttrInfo($matchfield);
            $attrVal = $item[$matchfield];

            $this->checkCompatibility($attrInfo, $matchfield);

            // no item data for selected matching field, skipping
            if (!isset($item[$matchfield]) && trim($item["matchfield"]) !== '') {
                $this->log("No value for $matchfield in datasource", "error");
                return false;
            }

            $productInfo = $this->getItemIdsByAttributeCode($attrVal, $matchfield);

            // If did not find the existing value, check next attribute code
            if (!$productInfo) {
                $this->log("No $matchfield found matching value : " . $attrVal, "warning");
                continue;
            }

            $magentoValue = $this->getMagentoData($item, array(
                'product_id' => $productInfo['pid']
            ));

            if (!$magentoValue) {
                continue;
            }

            $item['sku'] = $magentoValue['sku'];
            $this->log("Sku" . $result["sku"] . " match $matchfield value : " . $item[$matchfield], "info");
        }
    }

    /**
     * getItemIdsByAttributeCode
     * @param  string $attributeValue
     * @param  array  $options
     */
    public function getItemIdsByAttributeCode(string $attributeValue, string $attributeName)
    {
        $mainTable = $this->tablename('catalog_product_entity');
        $joinTableName = $this->tablename('catalog_product_entity_varchar');
        $attributeTable = $this->tablename('eav_attribute');

        $query  = "SELECT DISTINCT sku, $mainTable.entity_id as pid, attribute_set_id as asid, created_at, updated_at, type_id as type FROM $mainTable left join $joinTableName on $mainTable.entity_id = $joinTableName.entity_id AND $joinTableName.attribute_id = (SELECT attribute_id FROM $attributeTable where attribute_code = '$attributeName') WHERE $joinTableName.value = '$attributeValue'";

        $result = $this->selectAll($query);

        if (count($result)) {
            $pids = $result[0];
            $pids["__new"] = false;
            return $pids;
        }
        return false;
    }

    public function checkCompatibility(array $attInfo, string $matchfield)
    {
        if ($this->_compatibility == false) {
            // Checking attribute compatibility with sku matching
            if ($attinfo == null) {
                $this->log("$matchfield is not a valid attribute", "error");
                $item["__MAGMI_LAST__"] = 1;
                return false;
            }
            if ($attinfo["is_unique"] == 0 || $attinfo["is_global"] == 0) {
                $this->log("sku matching attribute $matchfield must be unique & global scope");
                $item["__MAGMI_LAST__"] = 1;
                return false;
            }

            if ($attinfo["backend_type"] == "static") {
                $this->log("$matchfield is " . $attinfo["backend_type"] . ", it cannot be used as sku matching field.",
                    "error");
                $item["__MAGMI_LAST__"] = 1;
                return false;
            }

            if ($attinfo["frontend_input"] == "select" || $attinfo["frontend_input"] == "multiselect") {
                $this->log(
                    "$matchfield is " . $attinfo["frontend_input"] . ", it cannot be used as sku matching field.",
                    "error");
                $item["__MAGMI_LAST__"] = 1;
                return false;
            }
            $this->_compatibility = true;
        }
    }

    public static function getCategory()
    {
        return "Input Data Preprocessing";
    }
}
