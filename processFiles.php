<?php

require_once 'config.php';
require_once 'imageCloner.php';
require_once 'xmlToArray.php';

class ProcessFiles{

	protected $config = array();

	public function __construct($config){
		echo 'PROCESS STARTED AT: '.date('d/m/Y H:i:s');

		$this->config = $config;
		$this->doc  = new DomDocument('1.0', 'UTF-8');
		$this->doc->formatOutput = true;

		$this->root = $this->doc->createElement('FILMS');
		$this->root = $this->doc->appendChild($this->root);

		$this->claims = new xmlToArray($this->config['ctaPath']);
		$this->claim = $this->claims->getClaims();

		$data = $this->loadCSV($this->config['currentMonthPath'],$this->config['nextMonthPath']);
		$cloneImages = new imageCloner($data,$this->config['originalImagePath'],$this->config['cloneDirectory']);
		$this->recordData($data,$this->config['outputPath']);

		echo '<br/>';
		echo 'PROCESS COMPLETED SUCCESFULLY AT: '.date('d/m/Y H:i:s');

	}

	private function loadCSV($inputFilename1,$inputFilename2){
		$currentDay = null;

		$currentMonthCSV = array();
		$nextMonthCSV = array();
		$count = 0;

		$inputFile  = fopen($inputFilename1, 'rt');

		$headers = fgetcsv($inputFile,0,'|');

		while (($row = fgetcsv($inputFile,0,'|')) !== FALSE)
		{

		    $dayValues = array();
		    $currentMonthCSV[$count] = array();

		    foreach($headers as $i => $header)
		    {
		    	if (in_array($header, $this->config['nodesCSV'])) {
		    		$currentMonthCSV[$count][$header] = $row[$i];
				}
		    }

		    $count++;
		}

		if (@fopen($inputFilename2, 'rt') != FALSE) {
			$count = 0;

			$inputFile  = fopen($inputFilename2, 'rt');

			$headers = fgetcsv($inputFile,0,'|');

			while (($row = fgetcsv($inputFile,0,'|')) !== FALSE)
			{

			    $dayValues = array();
			    $nextMonthCSV[$count] = array();

			    foreach($headers as $i => $header)
			    {
			    	if (in_array($header, $this->config['nodesCSV'])) {
			    		$nextMonthCSV[$count][$header] = $row[$i];
					}
			    }

			    $count++;
			}
			echo '<br/>NEXT MONTH DATA MERGED';
		} else {
		  echo '<br/>NEXT MONTH DATA FILE NOT AVAILABLE';
		}

		return $this->reduceData(array_merge($currentMonthCSV,$nextMonthCSV));
	}

	private function recordData($data,$outputFile){

		$session = 1;
		$day = array();

		for ($i = 0; $i < count($data); $i++) {

			switch ($session) {
				case 1:
					if (strtotime($data[$i]["HoraEmision"]) <= strtotime("14:59:00") || strtotime($data[$i]["HoraEmision"]) >= strtotime("22:30:00")) {
						continue;
					}
					if (@$this->blockMinutesRound($data[$i + 3]['HoraEmision']) != "22:00" && @$this->blockMinutesRound($data[$i + 3]['HoraEmision']) != "22:05") {
						continue;
					}
					array_push($day, $data[$i]);
					$session++;
					break;
				case 5:
					array_push($day, $data[$i]);
					$this->recordDay($day);
					if (@strtotime($data[$i+1]["HoraEmision"]) <= strtotime("15:00:00")) {
						$i++;
					}
					$day = array();
					$session = 1;

					break;
				default:
					array_push($day, $data[$i]);
					$session++;
					break;
			}
		}

		if ($session != 1) {
			$this->recordDay($day);
		}

		$strxml = $this->doc->saveXML();
		$handle = fopen($outputFile, "w");
		fwrite($handle, $strxml);
		fclose($handle);
	}

	private function recordDay($dayData){
		$daySessions = array();

			$container = $this->doc->createElement('FILM');
			$this->addNode($container,'ID',date_format(date_create_from_format('d/m/Y',$dayData[0]['FechaEmision']),"Ymd"),false);
			$this->addNode($container,'CLASSIFICATION',$dayData[0]['FechaEmision'],false);
			$this->addNode($container,'NAME',$dayData[0]['FechaEmision'],false);

			for ($j=0; $j < 5 ; $j++) {
				if (!array_key_exists($j,$dayData)) {
					continue;
				}
				$this->addNode($container,'SESSION_'.($j+1).'_NAME',$dayData[$j]['TituloEsp'],true);
				$this->addNode($container,'SESSION_'.($j+1).'_POSTER',$this->getImageURL($dayData[$j]['PROGRAM_ID']),false);
				$this->addNode($container,'SESSION_'.($j+1).'_TIME',$this->blockMinutesRound($dayData[$j]['HoraEmision']),false);
				$this->addNode($container,'SESSION_'.($j+1).'_CLAIM',$this->claim['SESSION_'.($j+1).'_CLAIM'],true);
				$this->addNode($container,'SESSION_'.($j+1).'_TITLE',$this->claim['SESSION_'.($j+1).'_TITLE'],true);
				$this->addNode($container,'SESSION_'.($j+1).'_URL',$this->config['baseUrl'].$this->slugify(utf8_encode($dayData[$j]['TituloEsp'])).$this->config['utms'],false);
				array_push($daySessions, $this->blockMinutesRound($dayData[$j]['HoraEmision']));
			}

			$this->addNode($container,'DATE_RANGE',$this->getDateRange($dayData[0]['FechaEmision']),true);
			$this->addNode($container,'URL',$this->config['guideUrl'].date_format(date_create_from_format('d/m/Y',$dayData[0]['FechaEmision']),"Y/m/d").$this->config['utms'],false);
			$this->addNode($container,'ENABLED',true,false);

	}

	private function addNode($container,$name,$value,$encode){
		$child = $this->doc->createElement($name);
		$child = $container->appendChild($child);
		if ($encode == true) {
			$value = $this->doc->createCDATASection(utf8_encode($value));
		} else {
			$value = $this->doc->createTextNode($value);
		}


		$value = $child->appendChild($value);
		$this->root->appendChild($container);
	}

	private function reduceData($data){
		$finalData = array();
		for ($i = 0; $i < count($data); $i++) {
			if (strtotime($data[$i]["HoraEmision"]) >= strtotime("14:59:00") || strtotime($data[$i]["HoraEmision"]) <= strtotime("01:30:00")) {
					array_push($finalData, $data[$i]);
			}
		}
		return $finalData;
	}

	private function getId($date,$session){
		return $this->formatDate($date).'_'.$session;
	}

	private function getStartTime($session,$date,$daySessions){
		if ($session == 0) {
			return $date.' 00:00';
		}

		return $date.' '.$daySessions[$session - 1];
	}

	private function getEndTime($session,$date,$daySessions){

		if ($session > 2) {
			return $date.' 00:00';
		}
		return $date.' '.$daySessions[$session];
	}

	private function formatDate($date){
		return date_format(date_create_from_format('d/m/Y',$date),"Ymd");
	}

	private function blockMinutesRound($hour, $minutes = '5', $format = "H:i") {
	   $seconds = strtotime($hour);
	   $rounded = floor($seconds / ($minutes * 60)) * ($minutes * 60);
	   return date($format, $rounded);
	}

	private function getImageURL($programId){
		return $this->config['finalImagePath'].$programId.'.jpg';
	}

	private function getDateRange($date){
		$nextDay = date('m/d/Y', strtotime(date_format(date_create_from_format('d/m/Y',$date),"m/d/Y").' +1 day'));
		return date_format(date_create_from_format('d/m/Y',$date),"m/d/Y").' - '.date_format(date_create_from_format('d/m/Y',$date),"m/d/Y");
	}
	private function slugify($text)	{
	  $text = preg_replace('~[^\pL\d]+~u', '-', $text);
	  $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
	  $text = preg_replace('~[^-\w]+~', '', $text);
	  $text = trim($text, '-');
	  $text = preg_replace('~-+~', '-', $text);
	  $text = strtolower($text);

	  if (empty($text)) {
	    return 'n-a';
	  }

	  return $text;
	}

}

$processFiles = new ProcessFiles($config);

?>
