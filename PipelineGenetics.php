<?php

$serie = $argv[1];
$bwa_algo = $argv[2];
$is_pp = $argv[3];
$ngs_path = $argv[4];
$mode = $argv[5];
$length_min = $argv[6];
$is_indel_realign = $argv[7];
$db_extension = $argv[8];
$bed = $argv[9];
$bed_name = $argv[10];
$dp_reseq = $argv[11];
$dp_conta = $argv[12];
$depth_variation_threshold = $argv[13];
$af_threshold = $argv[14];
$dp_threshold = $argv[15];

if(strtoupper($mode) == "PRODUCTION"){
	$mode = "Production";
}
else{
	$mode = "Development";
}

$run = $serie;
$serie = str_replace("-", "_", $serie);

mkdir("$ngs_path/Genetics/RUNS/$serie");
mkdir("$ngs_path/Genetics/RUNS/$serie/fastQ");
mkdir("$ngs_path/Genetics/RUNS/$serie/Analysis");
mkdir("$ngs_path/Genetics/RUNS/$serie/Analysis/Bams");
mkdir("$ngs_path/Genetics/RUNS/$serie/Analysis/Bams_MEM");
mkdir("$ngs_path/Genetics/RUNS/$serie/Analysis/Bams_RAW");
mkdir("$ngs_path/Genetics/RUNS/$serie/Analysis/Resultats_deva");
mkdir("$ngs_path/Genetics/RUNS/$serie/Analysis/Resultats_mutacaller");
mkdir("$ngs_path/Genetics/RUNS/$serie/Analysis/Logs");
mkdir("$ngs_path/Genetics/RUNS/$serie/Analysis/Synchro");
mkdir("$ngs_path/Genetics/RUNS/$serie/fastQ_group1");
mkdir("$ngs_path/Genetics/RUNS/$serie/fastQ_group2");
mkdir("$ngs_path/Genetics/RUNS/$serie/fastQ_group3");
mkdir("$ngs_path/Genetics/RUNS/$serie/fastQ_group4");

system("cp /biopathnas/NGS_Génétique/RUNS/$run/Data/Intensities/BaseCalls/*.fastq* $ngs_path/Genetics/RUNS/$serie/fastQ/");
system("rm -f $ngs_path/Genetics/RUNS/$serie/fastQ/Undetermined*.fastq*");

$size = GroupPartition("$ngs_path/Genetics/RUNS/$serie/fastQ", "illumina");

system("php $ngs_path/Scripts/Genetics_Amplicon/$mode/BWAM.php $serie $bwa_algo $is_pp $ngs_path $mode $length_min $is_indel_realign fastQ_group1 $bed $depth_variation_threshold $af_threshold $dp_threshold 1> $ngs_path/Genetics/RUNS/$serie/Analysis/Logs/output.txt 2> $ngs_path/Genetics/RUNS/$serie/Analysis/Logs/error.txt&");
system("php $ngs_path/Scripts/Genetics_Amplicon/$mode/BWAM.php $serie $bwa_algo $is_pp $ngs_path $mode $length_min $is_indel_realign fastQ_group2 $bed $depth_variation_threshold $af_threshold $dp_threshold 1> $ngs_path/Genetics/RUNS/$serie/Analysis/Logs/output.txt 2> $ngs_path/Genetics/RUNS/$serie/Analysis/Logs/error.txt&");

system("php $ngs_path/Scripts/Genetics_Amplicon/$mode/BWAM.php $serie $bwa_algo $is_pp $ngs_path $mode $length_min $is_indel_realign fastQ_group3 $bed $depth_variation_threshold $af_threshold $dp_threshold 1> $ngs_path/Genetics/RUNS/$serie/Analysis/Logs/output.txt 2> $ngs_path/Genetics/RUNS/$serie/Analysis/Logs/error.txt&");
system("php $ngs_path/Scripts/Genetics_Amplicon/$mode/BWAM.php $serie $bwa_algo $is_pp $ngs_path $mode $length_min $is_indel_realign fastQ_group4 $bed $depth_variation_threshold $af_threshold $dp_threshold 1> $ngs_path/Genetics/RUNS/$serie/Analysis/Logs/output.txt 2> $ngs_path/Genetics/RUNS/$serie/Analysis/Logs/error.txt&");

while(1 > 0){
	if(alreadydone("$ngs_path/Genetics/RUNS/$serie/Analysis/Synchro") == $size/2){
		break;
	}
}


#IGR DATABASE COMMIT
system("cp $ngs_path/Databases/igr_apache.db $ngs_path/Databases/igr.db");
system("php $ngs_path/Scripts/Genetics_Amplicon/$mode/FinalStep.php $serie $ngs_path $mode $bed_name $dp_reseq $dp_conta");

system("php $ngs_path/Scripts/Genetics_Amplicon/$mode/DB_Storage.php $ngs_path/Genetics/RUNS/$serie/Analysis/Synthesis $ngs_path/Genetics/RUNS/$serie/Analysis/QCAs $ngs_path/Genetics/RUNS/$serie/Analysis/QC_Libraries.csv $serie $run $ngs_path $mode $db_extension");
 
//Nom Index pour Alamut
system("php $ngs_path/Scripts/Genetics_Amplicon/$mode/RenameBamsIndex.php $ngs_path/Genetics/RUNS/$serie/Analysis/Bams");
system("php $ngs_path/Scripts/Genetics_Amplicon/$mode/RenameBamsIndex.php $ngs_path/Genetics/RUNS/$serie/Analysis/Bams_MEM");

system("cat $ngs_path/Genetics/RUNS/$serie/Analysis/Synthesis/* > $ngs_path/Genetics/RUNS/$serie/Analysis/Synthesis/AllPatients.csv");

system("rm -rf $ngs_path/Genetics/RUNS/$serie/Analysis/Bams_RAW");
system("rm -rf $ngs_path/Genetics/RUNS/$serie/Analysis/Synchro");
system("rm -rf $ngs_path/Genetics/RUNS/$serie/fastQ_group1");
system("rm -rf $ngs_path/Genetics/RUNS/$serie/fastQ_group2");
system("rm -rf $ngs_path/Genetics/RUNS/$serie/fastQ_group3");
system("rm -rf $ngs_path/Genetics/RUNS/$serie/fastQ_group4");
system("rm -rf $ngs_path/Genetics/RUNS/$serie/fastQ");

mkdir("/biopathnas/NGS_Génétique/Analyses/$serie");
system("cp -r $ngs_path/Genetics/RUNS/$serie/Analysis/* /biopathnas/NGS_Génétique/Analyses/$serie/");


function getFiles($dir){
	$listfiles = scandir($dir);
	$tab = array();
	for($f = 0; $f < count($listfiles); $f++){
		$entry = $listfiles[$f];
		
		if (!preg_match("/^\./", $entry)) {
			array_push($tab, $entry);	
		}
	}
	
	sort($tab);
	return $tab;
}


function alreadydone($dir1) {
	$elts = array();
	if ($handle = opendir($dir1)) {
    	while (false !== ($entry = readdir($handle))) {
    		if ($entry != "." && $entry != ".."){
        		array_push($elts, $entry);
        	}
    	}
    	closedir($handle);
	}
	
	$size = count($elts);
	
	return $size;
}

function GroupPartition($folder, $sequencer){
	
	$elts = getFiles($folder);
	$increment = 0;
	$nbtot = count($elts);

	for($f = 0; $f < count($elts); $f++){
		$entry = $elts[$f];
	
		$increment++;
		
		if($nbtot < 8){
			if($sequencer == "illumina"){
				if($nbtot == 2){
					system("cp $folder/$entry ".$folder."_group1/");
				}
				else if($nbtot == 4){
					if($increment <= 2){
						system("cp $folder/$entry ".$folder."_group1/");
					}
					else{
						system("cp $folder/$entry ".$folder."_group2/");
					}
				}
				else if($nbtot == 6){
					if($increment <= 2){
						system("cp $folder/$entry ".$folder."_group1/");
					}
					else if($increment <= 4){
						system("cp $folder/$entry ".$folder."_group2/");
					}
					else{
						system("cp $folder/$entry ".$folder."_group3/");
					}
				}
			}
			else{
				if($nbtot == 1){
					system("cp $folder/$entry ".$folder."_group1/");
				}
				else if($nbtot == 2){
					if($increment == 1){
						system("cp $folder/$entry ".$folder."_group1/");
					}
					else{
						system("cp $folder/$entry ".$folder."_group2/");
					}
				}
				else if($nbtot == 3){
					if($increment == 1){
						system("cp $folder/$entry ".$folder."_group1/");
					}
					else if($increment == 2){
						system("cp $folder/$entry ".$folder."_group2/");
					}
					else{
						system("cp $folder/$entry ".$folder."_group3/");
					}
				}
				else if($nbtot == 4){
					if($increment == 1){
						system("cp $folder/$entry ".$folder."_group1/");
					}
					else if($increment == 2){
						system("cp $folder/$entry ".$folder."_group2/");
					}
					else if($increment == 3){
						system("cp $folder/$entry ".$folder."_group3/");
					}
					else{
						system("cp $folder/$entry ".$folder."_group4/");
					}
				}
				else if($nbtot == 5){
					if($increment == 1){
						system("cp $folder/$entry ".$folder."_group1/");
					}
					else if($increment == 2){
						system("cp $folder/$entry ".$folder."_group2/");
					}
					else if($increment == 3){
						system("cp $folder/$entry ".$folder."_group3/");
					}
					else if($increment == 4){
						system("cp $folder/$entry ".$folder."_group4/");
					}
					else{
						system("cp $folder/$entry ".$folder."_group1/");
					}
				}
				else if($nbtot == 6){
					if($increment == 1){
						system("cp $folder/$entry ".$folder."_group1/");
					}
					else if($increment == 2){
						system("cp $folder/$entry ".$folder."_group2/");
					}
					else if($increment == 3){
						system("cp $folder/$entry ".$folder."_group3/");
					}
					else if($increment == 4){
						system("cp $folder/$entry ".$folder."_group4/");
					}
					else if($increment == 5){
						system("cp $folder/$entry ".$folder."_group1/");
					}
					else{
						system("cp $folder/$entry ".$folder."_group2/");
					}
				}
				else if($nbtot == 7){
					if($increment == 1){
						system("cp $folder/$entry ".$folder."_group1/");
					}
					else if($increment == 2){
						system("cp $folder/$entry ".$folder."_group2/");
					}
					else if($increment == 3){
						system("cp $folder/$entry ".$folder."_group3/");
					}
					else if($increment == 4){
						system("cp $folder/$entry ".$folder."_group4/");
					}
					else if($increment == 5){
						system("cp $folder/$entry ".$folder."_group1/");
					}
					else if($increment == 6){
						system("cp $folder/$entry ".$folder."_group2/");
					}
					else{
						system("cp $folder/$entry ".$folder."_group3/");
					}
				}
			}
		}
		else{
			
			if($increment <= (intval($nbtot/4) + (intval($nbtot/4))%2)){
				system("cp $folder/$entry ".$folder."_group1/");
			}
			else if($increment <= intval($nbtot/4) + (intval($nbtot/4))%2 + intval($nbtot/4) + (intval($nbtot/4))%2){
				system("cp $folder/$entry ".$folder."_group2/");
			}
			else if($increment <= intval($nbtot/4) + (intval($nbtot/4))%2 + intval($nbtot/4) + (intval($nbtot/4))%2 + intval($nbtot/4) + (intval($nbtot/4))%2){
				system("cp $folder/$entry ".$folder."_group3/");
			}
			else{
				system("cp $folder/$entry ".$folder."_group4/");
			}
		}
	}
	
	return $nbtot;

}


/*
function GroupPartition($folder){
	
	$elts = getFiles($folder);
	$increment = 0;
	$nbtot = count($elts);

	for($f = 0; $f < count($elts); $f++){
		$entry = $elts[$f];
	
		$increment++;
		
		if($nbtot < 8){
			system("cp $folder/$entry ".$folder."_group1/");
		}
		else{
			if(($nbtot/4)%4 == 0){
				if($increment <= $nbtot/4){
					system("cp $folder/$entry ".$folder."_group1/");
				}
				else if($increment <= $nbtot/2){
					system("cp $folder/$entry ".$folder."_group2/");
				}
				else if($increment <= $nbtot*3/4){
					system("cp $folder/$entry ".$folder."_group3/");
				}
				else{
					system("cp $folder/$entry ".$folder."_group4/");
				}
			}
			else{
				if($increment <= (intval($nbtot/4) + $nbtot%4)){
					system("cp $folder/$entry ".$folder."_group1/");
				}
				else if($increment <= (intval($nbtot/4) + $nbtot%4 + intval($nbtot/4))){
					system("cp $folder/$entry ".$folder."_group2/");
				}
				else if($increment <= (intval($nbtot/4) + $nbtot%4 + intval($nbtot/4) + intval($nbtot/4))){
					system("cp $folder/$entry ".$folder."_group3/");
				}
				else{
					system("cp $folder/$entry ".$folder."_group4/");
				}
			}
		}
	}
	
	return $nbtot;

}
*/

?>
