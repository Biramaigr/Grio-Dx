<?php 

$bamfile = $argv[1];
$bedfile = $argv[2];
$serie = $argv[3];
$ngs_path = $argv[4];
$dp_reseq = $argv[5];
$dp_conta = $argv[6];

$patient_id = str_replace(".bam", "", basename($bamfile));

$lines = file($bedfile);
$count = count($lines);

$fpn = fopen("$ngs_path/Genetics/RUNS/$serie/Analysis/QCAs_tech/".$patient_id.".QCA_tech.csv", 'w');

fwrite($fpn, "patient_id;amplicon;chr;pos;dp;status;exon\n");

$array_x = array();
$array_y = array();

for($l = 0; $l < $count; $l++){
	unset($results);
	$line = $lines[$l];
	$line = trim($line);
	$elements = explode ("\t", $line);

	$amplicon = $elements[0];
	$chr = $elements[1];
	$start = $elements[2];
	$stop = $elements[3];
	$exon = $elements[4];
	
	exec("/biopathdata/pipelineuser/NGS/Programs/samtools-0.1.19_no_cap/samtools depth -r $chr:$start-$stop $bamfile", $results);
	
	for($m = 0; $m < count($results); $m++){
		$frags = explode("\t", $results[$m]);

		$chr_curr = $frags[0];
		$pos = $frags[1];
		$dp = $frags[2];

		if (preg_match("/egatif/", $patient_id)) {
			if($dp > $dp_conta){
				$status = "FAIL";
				fwrite($fpn, "$patient_id;$amplicon;$chr;$pos;$dp;$status;$exon\n");
			}
		}
		else{
			if($dp < $dp_reseq){
				$status = "FAIL";
				fwrite($fpn, "$patient_id;$amplicon;$chr;$pos;$dp;$status;$exon\n");
			}
		}
	}
	
}

fclose($fpn);

?>
