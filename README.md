# feed-generator
Project to fetch data from a client external resource and a XML file, process and return a valid xml feed for a Sizmek DCO Campaign.

##Description

For this project we needed to create a cron in our server to process two different xml files and return a xml file with a valid data feed for a DCO campaign. We had to analice dates, create slug urls for the movies and clone the movie posters into our server.

This process will execute automatically each 24h.

##Config File

The config file contains an array with all the necesary paths.

##Example

```php

<?php
	error_reporting(E_ALL | E_STRICT);
	ini_set('display_errors', true);
	ini_set('auto_detect_line_endings', true);

	$config = array(
		'currentMonthPath' => '',
		'nextMonthPath' => '',
		'ctaPath' => '',
		'outputPath' => '',
		'originalImagePath' => '',
		'cloneDirectory' => '',
		'finalImagePath' => '',
		'nodesCSV' => '',
		'guideUrl' => '',
		'baseUrl' => '',
		'utms' => ''
	);

?>

```

