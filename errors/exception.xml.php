<?php
echo '<?xml version="1.0" encoding="utf-8"?>';
?>
<result xmlns:xlink="http://www.w3.org/TR/xlink">
	<data><error code="<?php echo $exception->code; ?>" type="<?php echo $exception->type; ?>"><?php
			echo $exception->message;
		?></error><?php

		if (DEBUG_SHOW_BACKTRACE) :
		?><backtrace><?php

			foreach ($exception->trace as $traceString) :
				?><trace><?php
					echo $traceString;
				?></trace><?php
			endforeach;

		?></backtrace><?php
		endif;
		?></data>
</result>