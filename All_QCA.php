<?php

$bamdir = $argv[1];
$serie = $argv[2];
$ngs_path = $argv[3];
$mode = $argv[4];
$bed_name = $argv[5];
$dp_reseq = $argv[6];
$dp_conta = $argv[7];

if ($handle = opendir($bamdir)) {
    while (false !== ($entry = readdir($handle))) {
        if (preg_match("/.bam$/", $entry)) {
			system("php $ngs_path/Scripts/Genetics_Amplicon/$mode/QCA.php $bamdir/$entry $ngs_path/Genetics/$bed_name $serie $ngs_path $dp_reseq");

			system("php $ngs_path/Scripts/Genetics_Amplicon/$mode/QCA_tech.php $bamdir/$entry $ngs_path/Genetics/$bed_name $serie $ngs_path $dp_reseq $dp_conta");
        }
    }
    closedir($handle);
}

?>
