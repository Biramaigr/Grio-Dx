<?php

$vcffile = $argv[1];

$lines = file($vcffile);
$count = count($lines);

echo trim($lines[0])."\n";

$tableau_brca1 = array();
$tableau_brca2 = array();

for($l = 0; $l < $count; $l++){
	$line = $lines[$l];
	if($line && ! preg_match("/^patient_id/", $line)){
		$line = trim($line);
		$elements = explode (";", $line);

		$gene = $elements[1];
		$chr = ($gene == "BRCA2") ? "13" : "17";
		$pos = $elements[2];
		$ref = $elements[3];
		$alt = $elements[4];
		
		$key = "$chr$pos$ref$alt";
	
		if($gene == "BRCA2"){
			$tableau_brca2[$key] = $line;
		}
		else{
			$tableau_brca1[$key] = $line;
		}
	}
}

krsort($tableau_brca1);
ksort($tableau_brca2);

foreach($tableau_brca2 as $elements2){
	echo "$elements2\n";
}

foreach($tableau_brca1 as $elements1){
	echo "$elements1\n";
}

?>
