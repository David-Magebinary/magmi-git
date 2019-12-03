<div class="plugin_description">
    This plugins can be used in <b>update </b> mode to generate a price alert file.
</div>
<ul class="formline">
    <li class="label"><span>Price column prefix</span></li>
    <li class="value"><input type="text" name="PCA:prefix" value="<?php echo $this->getParam("PCA:prefix", "")?>"></li>
</ul>

<ul class="formline">
    <li class="label"><span>Threshold</span></li>
    <li class="value">
        <input type="text" name="PCA:threshold" value="<?php echo $this->getParam("PCA:threshold", "")?>">
        <div class="fieldinfo">
            The default threshold is 0.2
        </div>
    </li>
</ul>