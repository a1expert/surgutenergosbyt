<?php
	class custom {
		public function cms_callMethod($method_name, $args) {
			return call_user_func_array(Array($this, $method_name), $args);
		}
		
		public function __call($method, $args) {
			throw new publicException("Method " . get_class($this) . "::" . $method . " doesn't exists");
		}
		//TODO: Write your own macroses here

public function sitemapnew($template = "default", $max_depth = false, $root_id = false) {
if(def_module::breakMe()) return;
 
if(!$max_depth) $max_depth = getRequest('param0');
if(!$max_depth) $max_depth = 4;
 
if(!$root_id) $root_id = 0;
 
if(cmsController::getInstance()->getCurrentMethod() == "sitemap") {
def_module::setHeader("%content_sitemap%");
}
 
$site_tree = umiHierarchy::getInstance()->getChilds($root_id, false, true, $max_depth - 1);
return self::gen_sitemap($template, $site_tree, $max_depth - 1);
}
 
public function gen_sitemap($template = "default", $site_tree, $max_depth) {
$res = "";
 
list($template_block, $template_item) = def_module::loadTemplates("tpls/content/sitemap/{$template}.tpl", "block", "item");
list($template_block, $template_item) = def_module::loadTemplates("tpls/content/sitemap/{$template}.tpl", "block", "item");
 
$block_arr = Array();
$items = Array();
if(is_array($site_tree)) {
foreach($site_tree as $element_id => $childs) {
if($element = umiHierarchy::getInstance()->getElement($element_id)) {
$link = umiHierarchy::getInstance()->getPathById($element_id);
$update_time = $element->getUpdateTime();
 
$item_arr = Array();
$item_arr['attribute:id'] = $element_id;
$item_arr['attribute:link'] = $link;
$item_arr['attribute:name'] = $element->getObject()->getName();
$item_arr['xlink:href'] = "upage://" . $element_id;
$item_arr['attribute:update-time'] = date('c', $update_time);
 
if($max_depth > 0) {
 
$item_arr['nodes:items'] = $item_arr['void:sub_items'] = (sizeof($childs) && is_array($childs)) ? $this->gen_sitemap($template, $childs, ($max_depth - 1)) : "";
} else {
$item_arr['sub_items'] = "";
}
 
$items[] = def_module::parseTemplate($template_item, $item_arr, $element_id);
 
umiHierarchy::getInstance()->unloadElement($element_id);
} else {
continue;
}
}
}
 
$block_arr['subnodes:items'] = $items;
return def_module::parseTemplate($template_block, $block_arr, 0);
}

	};
?>