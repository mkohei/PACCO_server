<?php 
	function makeDir ($roomId) {
		$filePath = '../uploaded_file/'. $roomId;

		if (!file_exists($filePath)) {
			if (!mkdir ($filePath, 0766)) {
				return false;
			}
			chmod ($filePath, 0766);
		}
		return true;
	}
?>
