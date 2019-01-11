<?php
	class commerceMLExporter extends umiExporter {
		
		public function setOutputBuffer() {
			$buffer = outputBuffer::current('HTTPOutputBuffer');
			$buffer->charset("utf-8");
			$buffer->contentType("text/xml");
			return $buffer;
		}
		
		public function getType() {
			return $this->type;
		}
		
		public function getSourceName() {
			$config = mainConfiguration::getInstance();
			$siteId = $config->get('modules', 'exchange.commerceML.siteId');
			if (!$siteId) {
				$domain = domainsCollection::getInstance()->getDefaultDomain()->getHost();
				$siteId = md5($domain);
			}
			if (strlen($siteId) > 2) {
				$siteId = substr($siteId, 0, 2);
				$config->set('modules', 'exchange.commerceML.siteId', $siteId);
			}
			return $siteId;
		}

		public function export($branches, $excludedBranches) {
			if (!count($branches)) {
				$sel = new selector('pages');
				$sel->where('hierarchy')->page(0)->childs(0);
				$sel->types('hierarchy-type')->name('catalog', 'category');
				$sel->types('hierarchy-type')->name('catalog', 'object');
				$branches = $sel->result();
			}
			
			$exporter = new xmlExporter($this->getSourceName());
			$exporter->addBranches($branches);
			$exporter->setIgnoreRelations();
			$exporter->excludeBranches($excludedBranches);
			$result = $exporter->execute();

			$umiDump = $result->saveXML();

			$style_file = './xsl/export/' . $this->type . '.xsl';
			if (!is_file($style_file)) {
				throw new publicException("Can't load exporter {$style_file}");
			}

			$doc = new DOMDocument("1.0", "utf-8");
			$doc->formatOutput = XML_FORMAT_OUTPUT;
			$doc->loadXML($umiDump);

			$templater = umiTemplater::create('XSLT', $style_file);
			return $templater->parse($doc);
		}
	}
?>