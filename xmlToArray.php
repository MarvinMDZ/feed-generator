<?php

	class xmlToArray{

		protected $nodes = array();

		public function __construct($xmlPath){
			$xml = simplexml_load_file($xmlPath, 'SimpleXMLElement', LIBXML_NOCDATA);
			$this->nodes = json_decode(json_encode((array)$xml),TRUE);
		}

		public function getClaims(){
			return $this->nodes;
		}
	}

?>
