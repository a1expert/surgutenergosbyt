<?php
	class HTTPDocOutputBuffer extends HTTPOutputBuffer {

		public function clear() {
			parent::clear();

            $level = ob_get_level();
            for ($i = 0; $i < $level; ++$i) {
                if (ob_get_length()) {
                    ob_end_clean();
                }
            }
		}

		public function send() {
			$this->sendHeaders();
			echo $this->buffer;
			parent::clear();
		}
	}

?>
