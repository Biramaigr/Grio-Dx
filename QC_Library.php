<?php

$fastq_r1 = $argv[1];
$fastq_r2 = $argv[2];
$bamfile = $argv[3];

$patient_id = str_replace(".bam", "", basename($bamfile));

$lines = file($fastq_r1);
$count = count($lines);

$nb_reads_fw = $count/4;
$nb_reads_q30_fw = 0;
$read_quality_global_tot_fw = 0;

$read_length_tot_fw = 0;

$array1 = array();

for($l = 0; $l < $count; $l++){
	$read_id = trim($lines[$l]);
	$read_seq = trim($lines[$l+1]);
	$read_qual = trim($lines[$l+3]);

	$read_length = (strlen($read_seq) > 0) ? strlen($read_seq) : 1;

	array_push($array1, $read_length);

	$read_length_tot_fw += $read_length;

	$read_quality_tot = 0;
	for($q = 0; $q < $read_length; $q++){
		$char_current = substr($read_qual, $q, 1);
		$qual_current = ord($char_current)-33;
		$read_quality_tot += $qual_current;
	}

	$read_quality_mean = round($read_quality_tot/$read_length);

	$read_quality_global_tot_fw += $read_quality_mean;

	if($read_quality_mean >= 30){
		$nb_reads_q30_fw++;
	}

	$l = $l + 3;
}

$read_length_mean_fw = round(4*$read_length_tot_fw/$count);

$min_fw = min($array1);
$max_fw = max($array1);
$read_length_distrib_fw = "$read_length_mean_fw [$min_fw<->$max_fw]";
$read_quality_global_mean_fw = round(4*$read_quality_global_tot_fw/$count);


$lines2 = file($fastq_r2);
$count2 = count($lines2);

$nb_reads_rv = $count2/4;
$nb_reads_q30_rv = 0;
$read_quality_global_tot_rv = 0;

$read_length_tot_rv = 0;

$array2 = array();

for($l = 0; $l < $count2; $l++){
	$read_id2 = trim($lines2[$l]);
	$read_seq2 = trim($lines2[$l+1]);
	$read_qual2 = trim($lines2[$l+3]);

	$read_length2 = (strlen($read_seq2) > 0) ? strlen($read_seq2) : 1;

	array_push($array2, $read_length2);

	$read_length_tot_rv += $read_length2;

	$read_quality_tot2 = 0;
	for($q = 0; $q < $read_length2; $q++){
		$char_current2 = substr($read_qual2, $q, 1);
		$qual_current2 = ord($char_current2)-33;
		$read_quality_tot2 += $qual_current2;
	}

	$read_quality_mean2 = round($read_quality_tot2/$read_length2);

	$read_quality_global_tot_rv += $read_quality_mean2;

	if($read_quality_mean2 >= 30){
		$nb_reads_q30_rv++;
	}

	$l = $l + 3;
}

$read_length_mean_rv = round(4*$read_length_tot_rv/$count2);

$min_rv = min($array2);
$max_rv = max($array2);
$read_length_distrib_rv = "$read_length_mean_rv [$min_rv<->$max_rv]";
$read_quality_global_mean_rv = round(4*$read_quality_global_tot_rv/$count2);


exec("/biopathdata/pipelineuser/NGS/Programs/samtools-0.1.19/samtools view -F 16 $bamfile | wc -l", $results1);
exec("/biopathdata/pipelineuser/NGS/Programs/samtools-0.1.19/samtools view -f 16 $bamfile | wc -l", $results2);

$nb_mapped_reads_fw = $results1[0];
$nb_mapped_reads_rv = $results2[0];

echo "$patient_id;$nb_reads_fw;$nb_reads_q30_fw;".round(100*$nb_reads_q30_fw/$nb_reads_fw).";$nb_reads_rv;$nb_reads_q30_rv;".round(100*$nb_reads_q30_rv/$nb_reads_rv).";$read_length_distrib_fw;$read_quality_global_mean_fw;$read_length_distrib_rv;$read_quality_global_mean_rv;".$results1[0].";".round(100*$nb_mapped_reads_fw/$nb_reads_fw).";$nb_mapped_reads_rv;".round(100*$nb_mapped_reads_rv/$nb_reads_rv).";\n";

?>
