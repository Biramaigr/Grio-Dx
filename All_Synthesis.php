<?php

$vcfdir_mutacaller = $argv[1];
$vcfdir_deva = $argv[2];
$serie = $argv[3];
$ngs_path = $argv[4];
$mode = $argv[5];

$dbh = new PDO("sqlite:$ngs_path/Genetics/RUNS/$serie/Analysis/DB/$serie.db");

$serie_rc = str_replace("-", "_", $serie);

$dbh->exec("DROP TABLE IF EXISTS variants_mutacaller_$serie_rc");

$dbh->exec("DROP TABLE IF EXISTS variants_deva_$serie_rc");

if ($handle = opendir($vcfdir_mutacaller)) {
    while (false !== ($entry = readdir($handle))) {
        if (!preg_match("/^\./", $entry) && !preg_match("/.snpEff.vcf$/", $entry)) {
			system("php $ngs_path/Scripts/Genetics_Amplicon/$mode/MutaCaller2DB.php $vcfdir_mutacaller/$entry $serie $ngs_path");
        }
    }
    closedir($handle);
}

if ($handle1 = opendir($vcfdir_deva)) {
    while (false !== ($entry = readdir($handle1))) {
	if (!preg_match("/^\./", $entry) && !preg_match("/.snpEff.vcf$/", $entry)) {
        //if (preg_match("/.vcf$/", $entry)) {
			system("php $ngs_path/Scripts/Genetics_Amplicon/$mode/Deva2DB.php $vcfdir_deva/$entry $serie $ngs_path");
        }
    }
    closedir($handle1);
}

system("php $ngs_path/Scripts/Genetics_Amplicon/$mode/Synthesis.php $serie $ngs_path/Genetics/RUNS/$serie/Analysis/Synthesis $ngs_path");

system("php $ngs_path/Scripts/Genetics_Amplicon/$mode/AllSortOrder.php $ngs_path/Genetics/RUNS/$serie/Analysis/Synthesis $ngs_path $mode");

?>
