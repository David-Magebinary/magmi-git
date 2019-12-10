<?php

/**
 * @see  The file has been renamed with z as the first letter to make sure this plugin will be loaded as the last
 * Magmi update mode only fills content when the existing product does not have a value
 */
class EmptyFiller extends Magmi_ItemProcessor
{
    public function getPluginInfo()
    {
        return array(
            "name"      => "Empty Value Filler",
            "author"    => "Siyu Qian",
            "version"   => "0.0.2",
            "url"       => 'http://wiki.magebinary.com'
        );
    }

    public function getPluginParams($params)
    {
        $pp=array();

        foreach ($params as $k=>$v) {
            if (preg_match("/^EMF:.*$/", $k)) {
                $pp[$k]=$v;
            }
        }

        return $pp;
    }

    public function processItemBeforeId(&$item, $params = null)
    {
        $attributes = explode(',', trim($this->getParam("EMF:attributecodes")));
        $itemData = $this->getItemIds($item);

        // the product does not exist in the system, then empty filler logic should not be needed
        if (!$itemData || !isset($itemData['pid'])) {
            return true;
        }

        $magentoValue = $this->getMagentoData($item, array('product_id' => $itemData['pid']));

        foreach ($attributes as $attribute) {
            if (isset($magentoValue[$attribute]) && $magentoValue[$attribute] != '') {
                $magentoValue = $this->replaceOptionIdWithValue($attribute, $magentoValue);
                $item[$attribute] = $magentoValue[$attribute];
            }
        }
        return true;
    }

    public function replaceOptionIdWithValue(string $attribute, array $magentoValue)
    {
        // Dirty fix: double handle for the attribute type
        $tableName = $this->tablename("eav_attribute");
        $extra = $this->tablename("catalog_eav_attribute");
                // SQL for selecting attribute properties for all wanted attributes
        $sql = "SELECT `$tableName`.*,$extra.* FROM `$tableName`
        LEFT JOIN $extra ON $tableName.attribute_id=$extra.attribute_id
        WHERE  ($tableName.attribute_code=?) AND (entity_type_id=?) ORDER BY $tableName.attribute_id";

        $attrInfo = current($this->selectAll($sql, array($attribute, 4)));
                // attribute is dropdown, get the text value
        if ($attrInfo['source_model'] == 'eav/entity_attribute_source_table') {
            $tableName = $this->tablename('eav_attribute_option_value');
            $sql = "SELECT `$tableName`.value FROM `$tableName` WHERE ($tableName.option_id=?)";
            $optionValue = current($this->selectAll($sql, array($magentoValue[$attribute])))['value'];
            $magentoValue[$attribute] = $optionValue;
        }

        return $magentoValue;
    }
}