<?php
	class coreException extends baseException {};
	
	class coreBreakEventsException extends coreException {};

	class selectorException extends coreException {};

	class libXMLErrorException extends coreException  {

		public function __construct($libXMLError) {
			$this->code 	= $libXMLError->code;
			$this->message 	= $libXMLError->message;
			$this->line 	= $libXMLError->line;
			$this->file		= $libXMLError->file;
		}
	}
?>