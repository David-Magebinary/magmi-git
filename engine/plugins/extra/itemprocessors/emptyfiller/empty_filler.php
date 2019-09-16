<?php
/**
 * Magmi update mode only fills content when the existing product does not have a value
 */
class EmptyFiller extends Magmi_ItemProcessor
{
    public function getPluginInfo()
    {
        return array(
            "name"      => "Empty Value Filler",
            "author"    => "MageBinary",
            "version"   => "0.0.1",
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
        $attributeCode = 'supplier_code';
        $itemData = $this->getItemIdsByAttribute($item[$attributeCode], $attributeCode);

        if (!$itemData || !isset($itemData['pid'])) {
            return false;
        }

        $productId = $itemData['pid'];
        $magentoValue = $this->getMagentoData($item, array('product_id' => $productId));

        foreach ($attributes as $attribute) {
            if (isset($magentoValue[$attribute]) && $magentoValue[$attribute] != '') {
                unset($item[$attribute]);
            }
        }
        return true;
    }
}