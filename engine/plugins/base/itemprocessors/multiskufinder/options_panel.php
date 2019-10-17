<div class="plugin_description">
	This plugins can be used in <b>update / create</b> mode to match item from custom
	columns in datasource. User may define multiple columns and list them in an order as the prority of the columns. The columns <b>MUST</b> be an attribute code
</div>
<ul class="formline">
	<li class="label"><span>sku find attribute code</span></li>
	<li class="value"><input type="text" name="MSKUF:matchfield"
		value="<?php echo $this->getParam("MSKUF:matchfield", "")?>"></li>
</ul>