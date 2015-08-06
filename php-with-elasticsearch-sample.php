<?php

if(php_sapi_name() == "cli") {
	include getcwd() . "/lib/tools.php";
	include getcwd() . "/lib/error-handler.php";
	include getcwd() . "/lib/elio.php";
	include getcwd() . "/lib/doc.php";
	include getcwd() . "/lib/question.php";

	if($argc <= 1) {
		die("Options: all debug clear mapping questions\n");
	}

	$all = in_array("all", $argv);
	$debug = in_array("debug", $argv);

	$clear		= $all || in_array("clear", $argv);
	$mapping	= $all || in_array("mapping", $argv);
	$questions	= $all || in_array("questions", $argv);

	$configDir = getcwd() . "/../";

	if($clear) {
		p_if_d(ELIO\es([
			"method" => "DELETE"
		]));
	}

	if($mapping) {
		p_if_d(ELIO\es(["method" => "POST"]));
		p_if_d(ELIO\es([
			"method" => "POST",
			"endpoint" => "_mapping/question",
			"data" => [
				"question" => [
					"properties" => [
						"question" => [
							"type" => "string",
							"index" => "not_analyzed"
						]
					]
				]
			]
		]));
		p_if_d(ELIO\es([
			"method" => "POST",
			"endpoint" => "_mapping/group",
			"data" => [
				"group" => [
					"properties" => [
						"location" => [
							"properties" => [
								"pin" => [
									"type" => "geo_point",
									"lat_lon" => true
								]
							]
						],
						"tags" => [
							"type" => "string",
							"index" => "not_analyzed",
							"position_offset_gap" => 100000
						]
					]
				]
			]
		]));
		p_if_d(ELIO\es([
			"method" => "POST",
			"endpoint" => "_mapping/profile",
			"data" => [
				"profile" => [
					"properties" => [
						"email" => [
							"type" => "string",
							"index" => "not_analyzed"
						],
						"activation_hash" => [
							"type" => "string",
							"index" => "not_analyzed"
						],
						"location" => [
							"properties" => [
								"pin" => [
									"type" => "geo_point",
									"lat_lon" => true
								]
							]
						],
						"tags" => [
							"properties" => [
								"hobbies" => [
									"type" => "string",
									"index" => "not_analyzed",
									"position_offset_gap" => 100000
								],
								"games" => [
									"type" => "string",
									"index" => "not_analyzed",
									"position_offset_gap" => 100000
								],
								"sports" => [
									"type" => "string",
									"index" => "not_analyzed",
									"position_offset_gap" => 100000
								],
								"movies" => [
									"type" => "string",
									"index" => "not_analyzed",
									"position_offset_gap" => 100000
								],
								"shows" => [
									"type" => "string",
									"index" => "not_analyzed",
									"position_offset_gap" => 100000
								],
								"music" => [
									"type" => "string",
									"index" => "not_analyzed",
									"position_offset_gap" => 100000
								],
							]
						]
					]
				]
			]
		]));
	}

	if($questions) {
		$qInArr = preg_split("/\\n\\n/", file_get_contents($configDir."questions.txt"));
		
		foreach($qInArr as $qIn) {
			$qIn = explode("\n", trim($qIn));
			if(sizeof($qIn) > 1) {
				$question = new Question();

				$question->question = array_shift($qIn);
				for($i = 0, $l = 'a'; $i < sizeof($qIn); $i++, $l++) {
					$question->answers[$l] = $qIn[$i];
				}
				
				p_if_d($question->save());
				echo ".";
			}
		}	
	}

}

function p_if_d($in) {
	global $debug;

	if($debug) {
		print_r($in);
	}
}
