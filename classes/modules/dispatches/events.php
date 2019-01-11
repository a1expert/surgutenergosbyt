<?php
	new umiEventListener("systemCreateObject", "dispatches", "onCreateObject");
	new umiEventListener("systemModifyObject", "dispatches", "onModifyObject");
	new umiEventListener("cron", "dispatches", "onAutosendDispathes");
	$eipModifyEventListener = new umiEventListener("systemModifyPropertyValue", "dispatches", "onPropertyChanged");
	$eipModifyEventListener->setIsCritical(true);
?>