<?php

date_default_timezone_set('Europe/Paris');
$date = date("j-m-Y");

$variants_dir = $argv[1];
$qcas_dir = $argv[2];
$qclibfile = $argv[3];
$serie = $argv[4];
$run_name = $argv[5];
$ngs_path = $argv[6];
$mode = $argv[7];
$db_extension = $argv[8];

$db = new PDO("mysql:host=localhost;dbname=Genetics$db_extension", "diagnostic", "genpatho");

$serie = str_replace("-", "_", $serie);

$table_run = "RUN_$serie";
$table_variants = "variants_$serie";
$table_amplicons_depth = "amplicons_depth_$serie";
$table_amplicons_depth_tech = "amplicons_depth_tech_$serie";
$table_library = "library_$serie";

$run_serie = getRunSerie($run_name);

$sql = "create table if not exists $table_run(run_name varchar(100), run_serie varchar(100), analysis_date varchar(100), validation_status_tech varchar(3), tech_by text, validation_status_bio varchar(3), bio_by text, check_status_tech varchar(3), chek_tech_by text, check_status_bio varchar(3), chek_bio_by text, observations text, track text)";

$db->exec($sql);

$sql = "insert into $table_run values ('$serie', '$run_serie', '$date', 'No', '', 'No', '', 'No', '', 'No', '', '', 'BWA 0.7.5a;Samtools v-0.1.19;Picard-tools v-1.127;GATK Haplotype v-3.4-46;MutaCaller-1.6;snpEff v-4.0')";
$db->exec($sql);

$sql = "create table if not exists $table_variants(patient_id varchar(100), chr varchar(10), gene varchar(100), position int, reference text, variant text, mutation_type varchar(20), mutation_length int, depth int, depth_alt int, af_mutacaller double, af_deva double, exon varchar(100), codon_change text, bic_nomenclature text, aa_change text, bic_clinically_importance varchar(50), bic_category varchar(50), umd_biological_significance varchar(50), umd_validation_by varchar(50), igr_effect varchar(50), zygosity varchar(10), soft varchar(50), conclusion text, sanger_validation varchar(10), sanger_result varchar(50), user_status varchar(50))";
$db->exec($sql);


if ($handle = opendir($variants_dir)) {
    while (false !== ($variants = readdir($handle))) {
        if (!preg_match("/^\./", $variants) && $variants != "AllPatients.csv") {
			if (file_exists("$variants_dir/$variants")) {
				$lines = file("$variants_dir/$variants");
				$count = count($lines);

				if($count <= 1){
					$patient_id = str_replace(".synthesis.csv", "", basename($variants));
					$sql = "insert into $table_variants(patient_id, chr) values ('$patient_id')";
					$db->exec($sql);
				}
				else{
					for($l = 1; $l < $count; $l++){
						$line = $lines[$l];
						#echo "$line";
						$line = trim($line);
						$elements = explode (";", $line);
						
						$patient_id = $elements[0];
						$gene = $elements[1];
						$position = $elements[2];
						$reference = $elements[3];
						$variant = $elements[4];
						$depth = $elements[5];
						$depth_alt = $elements[6];
						$af_mutacaller = ($elements[7] == "") ? 0.0 : $elements[7];
						$af_deva = ($elements[8] == "") ? 0.0 : $elements[8];
						$exon = $elements[9];
						$codon_change = $elements[10];
						$bic_nomenclature = $elements[11];
						$aa_change = $elements[12];
						$bic_clinically_importance = $elements[13];
						$bic_category = $elements[14];
						$umd_biological_significance = $elements[15];
						$umd_validation_by = $elements[16];
						$igr_effect = $elements[17];
						$zygosity = $elements[18];
						$soft = $elements[19];
						$conclusion = $elements[20]."::system::".$date;
						
						$chr = ($gene == "BRCA1") ? "17" : "13";
						
						if(strlen($reference) > strlen($variant)){
							$mutation_type = "Del";
							$mutation_length = strlen($reference) - 1;
						}
						else if(strlen($variant) > strlen($reference)){
							$mutation_type = "Ins";
							$mutation_length = strlen($variant) - 1;
						}
						else{
							$mutation_type = "SNV";
							$mutation_length = 0;
						}
						
						$sql = "insert into $table_variants(patient_id, chr, gene, position, reference, variant, mutation_type, mutation_length, depth, depth_alt, af_mutacaller, af_deva, exon, codon_change, bic_nomenclature, aa_change, bic_clinically_importance, bic_category, umd_biological_significance, umd_validation_by, igr_effect, zygosity, soft, conclusion, sanger_validation, sanger_result, user_status) values ('$patient_id', '$chr', '$gene', '$position', '$reference', '$variant', '$mutation_type', '$mutation_length', '$depth', '$depth_alt', '$af_mutacaller', '$af_deva', '$exon', '$codon_change', '$bic_nomenclature', '$aa_change', '$bic_clinically_importance', '$bic_category', '$umd_biological_significance', '$umd_validation_by', '$igr_effect', '$zygosity', '$soft', '$conclusion', 'No', '', '')";
						$db->exec($sql);
					}
				}
			}

        }
    }
    closedir($handle);
}

$sql = "create table if not exists $table_amplicons_depth(patient_id varchar(100), amplicon varchar(100), chr varchar(10), start int, stop int, dp_mean int, dp_min int, dp_max int, status varchar(10), exon numeric, sample_number int)";

$db->exec($sql);

if ($handle = opendir($qcas_dir)) {
	while (false !== ($qcas = readdir($handle))) {
		if (!preg_match("/^\./", $qcas) && !preg_match("/\.png/", $qcas) && !preg_match("/Thumbs.db/", $qcas)) {
			if (file_exists("$qcas_dir/$qcas")) {
				$lines = file("$qcas_dir/$qcas");
				$count = count($lines);
			
				for($l = 1; $l < $count; $l++){
					$line = $lines[$l];
					#echo "$line";
					$line = trim($line);
				
					if($line != ""){
						$elements = explode (";", $line);
						$patient_id = $elements[0];
						$amplicon = $elements[1];
						$chr = $elements[2];
						$start = $elements[3];
						$stop = $elements[4];
						$dp_mean = $elements[5];
						$status = $elements[6];
						$exon = $elements[7];
						$dp_min = ($elements[8] != "") ? $elements[8] : 0;
						$dp_max = ($elements[9] != "") ? $elements[9] : 0;
					
						$frags = explode("_", $patient_id);
						$sample_number = substr($frags[1], 1);

						$sql = "insert into $table_amplicons_depth(patient_id, amplicon, chr, start, stop, dp_mean, dp_min, dp_max, status, exon, sample_number) values ('$patient_id', '$amplicon', '$chr', '$start', '$stop', '$dp_mean', '$dp_min', '$dp_max', '$status', '$exon', '$sample_number')";
						$db->exec($sql);
					}
				}
			}
		}
	}
	closedir($handle);
}


$sql = "create table if not exists $table_amplicons_depth_tech(patient_id varchar(100), amplicon varchar(100), group_id text, chr varchar(10), pos int, dp int, status varchar(10), exon numeric, sample_number int, is_cumul varchar(5), start int, stop int, is_reseq varchar(5), reseq_done varchar(5), user_status varchar(50))";
$db->exec($sql);

$qcas_dir_tech = str_replace("QCAs", "QCAs_tech", $qcas_dir);

if ($handle = opendir($qcas_dir_tech)) {
	while (false !== ($qcas_tech = readdir($handle))) {
        	if (!preg_match("/^\./", $qcas_tech) && !preg_match("/\.png/", $qcas_tech) && !preg_match("/Thumbs.db/", $qcas_tech)) {
			if (file_exists("$qcas_dir_tech/$qcas_tech")) {
				$lines = file("$qcas_dir_tech/$qcas_tech");
				$count = count($lines);
	
				$group_id = "";
				for($l = 1; $l < $count; $l++){
					$line = $lines[$l];
					#echo "$line";
					$line = trim($line);
					
					if($line != ""){
						$elements = explode (";", $line);
						$patient_id = $elements[0];
						$amplicon = $elements[1];
						$chr = $elements[2];
						$pos = $elements[3];
						$dp = ($elements[4] != "") ? $elements[4] : 0;
						$status = $elements[5];
						$exon = $elements[6];

						$frags = explode("_", $patient_id);
						$sample_number = substr($frags[1], 1);

						if($group_id == "" || ($pos > $pos_last + 2) || $amplicon != $amplicon_last){
							$group_id = "$patient_id$amplicon$chr$pos";
						}

						$sql = "insert into $table_amplicons_depth_tech(patient_id, amplicon, group_id, chr, pos, dp, status, exon, sample_number, is_cumul, start, stop, is_reseq, reseq_done, user_status) values ('$patient_id', '$amplicon', '$group_id', '$chr', '$pos', '$dp', '$status', '$exon', '$sample_number', 'No', 0, 0, 'NONE', 'No', '')";
						$db->exec($sql);

						$pos_last = $pos;
						$amplicon_last = $amplicon;
					}
				}
			}
		}
	}
	closedir($handle);
}
getFailedRegions($serie, "amplicon", $db_extension);
getFailedRegions($serie, "conta", $db_extension);

$sql = "create table if not exists $table_library (patient_id varchar(100), nb_reads_fw int, nb_reads_q30_fw int, read_q30_percent_fw int, nb_reads_rv int, nb_reads_q30_rv int, read_q30_percent_rv int, read_length_mean_fw varchar(50), read_quality_mean_fw int, read_length_mean_rv varchar(50), read_quality_mean_rv int, nb_mapped_reads_fw int, mapped_reads_percent_fw int, nb_mapped_reads_rv int, mapped_reads_percent_rv int, sample_number int, is_already_view varchar(5), validation_status_tech varchar(3), tech_by varchar(100), validation_status_bio varchar(3), bio_by varchar(100))";
$db->exec($sql);

if (file_exists("$qclibfile")) {
	$lines = file("$qclibfile");
	$count = count($lines);

	for($l = 1; $l < $count; $l++){
		$line = $lines[$l];
		#echo "$line";
		$line = trim($line);
	
		if($line != ""){
			$elements = explode (";", $line);
			$patient_id = $elements[0];
			$nb_reads_fw = $elements[1];
			$nb_reads_q30_fw = $elements[2];
			$read_q30_percent_fw = $elements[3];
			$nb_reads_rv = $elements[4];
			$nb_reads_q30_rv = $elements[5];
			$read_q30_percent_rv = $elements[6];
			$read_length_mean_fw = $elements[7];
			$read_quality_mean_fw = $elements[8];
			$read_length_mean_rv = $elements[9];
			$read_quality_mean_rv = $elements[10];
			$nb_mapped_reads_fw = $elements[11];
			$mapped_reads_percent_fw = $elements[12];
			$nb_mapped_reads_rv = $elements[13];
			$mapped_reads_percent_rv = $elements[14];

			$frags = explode("_", $patient_id);
			$sample_number = substr($frags[1], 1);

			$sql = "insert into $table_library(patient_id, nb_reads_fw, nb_reads_q30_fw, read_q30_percent_fw, nb_reads_rv, nb_reads_q30_rv, read_q30_percent_rv, read_length_mean_fw, read_quality_mean_fw, read_length_mean_rv, read_quality_mean_rv, nb_mapped_reads_fw, mapped_reads_percent_fw, nb_mapped_reads_rv, mapped_reads_percent_rv, sample_number, is_already_view, validation_status_tech, tech_by, validation_status_bio, bio_by) values ('$patient_id', '$nb_reads_fw', '$nb_reads_q30_fw', '$read_q30_percent_fw', '$nb_reads_rv', '$nb_reads_q30_rv', '$read_q30_percent_rv', '$read_length_mean_fw', '$read_quality_mean_fw', '$read_length_mean_rv', '$read_quality_mean_rv', '$nb_mapped_reads_fw', '$mapped_reads_percent_fw', '$nb_mapped_reads_rv', '$mapped_reads_percent_rv', '$sample_number', 'No', 'No', '', 'No', '')";

			$db->exec($sql);
		}
	}
}

function getFailedRegions($serie, $type, $db_extension){
	$pdo = new PDO("mysql:host=localhost;dbname=Genetics$db_extension", "diagnostic", "genpatho");

	$table = "amplicons_depth_tech_$serie";

	if($type != "conta"){
		$query1 = $pdo->prepare("select distinct group_id from $table where patient_id not like '%egatif%'");
	}
	else{
		$query1 = $pdo->prepare("select distinct group_id from $table where patient_id like '%egatif%'");
	}

	$query1->execute();

	
	while($row1=$query1->fetch()){
		$group_id = $row1['group_id'];
		
		if($type != "conta"){
			$query = $pdo->prepare("select patient_id, amplicon, exon, chr, min(pos) as start, max(pos) as stop, avg(dp) as dp_mean from $table where group_id = '$group_id' and patient_id not like '%egatif%' order by sample_number asc");
		}
		else{
			$query = $pdo->prepare("select patient_id, amplicon, exon, chr, min(pos) as start, max(pos) as stop, avg(dp) as dp_mean from $table where group_id = '$group_id' and patient_id like '%egatif%' order by sample_number asc");
		}

		$query->execute();
		
		while($row=$query->fetch()){
			$patient_id = $row['patient_id'];
			$amplicon = $row['amplicon'];
			$exon = $row['exon'];
			$chr = $row['chr'];
			$start = $row['start'];
			$stop = $row['stop'];
			$dp_mean = round($row['dp_mean']);
			$status = "FAIL";

			$frags = explode("_", $patient_id);
			$sample_number = substr($frags[1], 1);

			if($type != "conta"){
				$sql = "insert into $table(patient_id, amplicon, exon, group_id, chr, dp, status, sample_number, is_cumul, start, stop, is_reseq, reseq_done, user_status) values ('$patient_id', '$amplicon', '$exon', '$group_id', '$chr', '$dp_mean', '$status', '$sample_number', 'Yes', '$start', '$stop', 'NONE', 'No', '')";
				$pdo->exec($sql);
			}
			else{
				$sql = "insert into $table(patient_id, amplicon, exon, group_id, chr, dp, status, sample_number, is_cumul, start, stop, is_reseq, reseq_done, user_status) values ('$patient_id', '$amplicon', '$exon', '$group_id', '$chr', '$dp_mean', '$status', '$sample_number', 'Yes', '$start', '$stop', 'NONE', 'No', '')";
				$pdo->exec($sql);
			}
	
		}
	}
	
}

function getRunSerie($run_name){

	$lines = file("/biopathnas/NGS_Génétique/RUNS/$run_name/SampleSheet.csv");

	for($l = 0; $l < count($lines); $l++){
		$line = $lines[$l];
		
		if(preg_match("/Experiment Name/", $line)){	
			$line = trim($line);

			$elements = explode (",", $line);

			$run_serie = $elements[1];
		}
	}

	return $run_serie;
}
?>
