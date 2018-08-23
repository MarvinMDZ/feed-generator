<?php

class imageCloner{

	public function __construct($data,$originalPath,$finalDirectory){
		echo '<br/>';
		echo 'COPY IMAGES STARTED AT: '.date('d/m/Y H:i:s');
		for ($i=0; $i < count($data); $i++) {
			$this->cloneImage($data[$i]['PROGRAM_ID'],$originalPath,$finalDirectory);
		}
		echo '<br/>';
		echo 'COPY IMAGES ENDED AT: '.date('d/m/Y H:i:s');
	}

	private function cloneImage($programID,$originalPath,$finalDirectory){
		$link = $originalPath.$programID.'.jpg';
		if (!file_exists($finalDirectory.$programID.'.jpg') || md5_file($link) != md5_file($finalDirectory.$programID.'.jpg')) {
			$img = file_get_contents($link);
	    	file_put_contents($finalDirectory.$programID.'.jpg', $img);
		}
	}
}

?>
