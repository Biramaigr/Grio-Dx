<?php

$serie = $argv[1];
$ngs_path = $argv[2];
$mode = $argv[3];
$bed_name = $argv[4];
$dp_reseq = $argv[5];
$dp_conta = $argv[6];

mkdir("$ngs_path/Genetics/RUNS/$serie/Analysis/QCAs");
mkdir("$ngs_path/Genetics/RUNS/$serie/Analysis/QCAs_tech");
mkdir("$ngs_path/Genetics/RUNS/$serie/Analysis/Synthesis");
mkdir("$ngs_path/Genetics/RUNS/$serie/Analysis/DB");

system("php $ngs_path/Scripts/Genetics_Amplicon/$mode/All_Synthesis.php $ngs_path/Genetics/RUNS/$serie/Analysis/Resultats_mutacaller $ngs_path/Genetics/RUNS/$serie/Analysis/Resultats_deva $serie $ngs_path $mode");
 
system("php $ngs_path/Scripts/Genetics_Amplicon/$mode/All_QCA.php $ngs_path/Genetics/RUNS/$serie/Analysis/Bams $serie $ngs_path $mode $bed_name $dp_reseq $dp_conta");
 
system("php $ngs_path/Scripts/Genetics_Amplicon/$mode/All_QC_Library.php $serie $ngs_path $mode > $ngs_path/Genetics/RUNS/$serie/Analysis/QC_Libraries.csv");


?>
