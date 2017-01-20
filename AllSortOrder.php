<?php

$vcfdir = $argv[1];
$ngs_path = $argv[2];
$mode = $argv[3];

if ($handle = opendir($vcfdir)) {
    while (false !== ($entry = readdir($handle))) {
        if (!preg_match("/^\./", $entry)) {
			$entry_sorted = str_replace(".synthesis.ns.csv", ".synthesis.csv", $entry);
			
			system("php $ngs_path/Scripts/Genetics_Amplicon/$mode/SortOrder.php $vcfdir/$entry > $vcfdir/$entry_sorted");
			
			unlink("$vcfdir/$entry");
        }
    }
    closedir($handle);
}




?>
