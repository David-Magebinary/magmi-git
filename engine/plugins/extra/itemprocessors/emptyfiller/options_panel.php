<div class="plugin_description">
	This plugins can be used in <strong>update </strong> mode to only import the data when the product's attribute has empty value. You may define the fields which would have to <strong>follow</strong> this rule.
</div>
<ul class="formline">
	<li class="label"><span>attribute codes (use comma to separate)</span></li>
	<li class="value"><input type="text" name="EMF:attributecodes"
		value="<?php echo $this->getParam("EMF:attributecodes", "")?>"></li>
</ul>