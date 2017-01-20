<?php 

$variants_mutacaller = $argv[1];
$serie = $argv[2];
$ngs_path = $argv[3];

$patient_id = str_replace(".vcf", "", basename($variants_mutacaller));

$variants_mutacaller_snpeff = str_replace(".vcf", ".snpEff.vcf", $variants_mutacaller);
$variants_mutacaller_temp1 = str_replace(".vcf", ".temp1.vcf", $variants_mutacaller);
$variants_mutacaller_temp2 = str_replace(".vcf", ".temp2.vcf", $variants_mutacaller);
$variants_mutacaller_temp3 = str_replace(".vcf", ".temp3.vcf", $variants_mutacaller);

system("java -Djava.io.tmpdir=$ngs_path/tmp -Xmx8g -jar $ngs_path/Programs/snpEff/SnpSift.jar annotate -v $ngs_path/Databases/snpEff/refDbsnp/dbsnp_138.hg19.vcf $variants_mutacaller > $variants_mutacaller_temp1");

system("java -Djava.io.tmpdir=$ngs_path/tmp -Xmx8g -jar $ngs_path/Programs/snpEff/SnpSift.jar annotate -v $ngs_path/Databases/snpEff/refCosmic/Cosmic_v69_DeVa_Edition.vcf $variants_mutacaller_temp1 > $variants_mutacaller_temp2");

system("java -Djava.io.tmpdir=$ngs_path/tmp -Xmx8g -jar $ngs_path/Programs/snpEff/snpEff.jar eff -c $ngs_path/Programs/snpEff/snpEff.config -onlyTr $ngs_path/Genetics/nm_filter.txt -v -hgvs -noLog -noStats -noMotif -noNextProt hg19 $variants_mutacaller_temp2 > $variants_mutacaller_temp3");

system("java -Djava.io.tmpdir=$ngs_path/tmp -Xmx8g -jar $ngs_path/Programs/snpEff/SnpSift.jar dbnsfp -v -db $ngs_path/Databases/snpEff/dbNSFP/dbNSFP/dbNSFP2.5.txt.gz $variants_mutacaller_temp3 > $variants_mutacaller_snpeff");

unlink($variants_mutacaller_temp1);
unlink($variants_mutacaller_temp2);
unlink($variants_mutacaller_temp3);

$dbh = new PDO("sqlite:$ngs_path/Genetics/RUNS/$serie/Analysis/DB/$serie.db");

$serie_rc = str_replace("-", "_", $serie);

$dbh->exec("CREATE TABLE IF NOT EXISTS variants_mutacaller_$serie_rc (identifiant text, patient_id varchar(100), gene varchar(50), nm varchar(50), exon varchar(50), nt_change text, aa_change text, chr varchar(2), pos int, ref text, alt text, type varchar(10), qual int, info varchar(50), format varchar(50), gt varchar(5), soft varchar(50))");


if (file_exists($variants_mutacaller_snpeff)) {
	$lines = file($variants_mutacaller_snpeff);
	$count = count($lines);
		
	for($l = 0; $l < $count; $l++){
		$line = $lines[$l];
		if($line && ! preg_match("/^#/", $line)){
			$line = trim($line);
			$elements = explode ("\t", $line);

			$chr = $elements[0];
			$pos = $elements[1];
			$id = $elements[2];
			$ref = $elements[3];
			$alt = $elements[4];
			$type = (strlen($ref) == strlen($alt)) ? "SNV" : "INDEL";
			$qual = $elements[5];
			$filter = $elements[6];
			$format = $elements[8];
			$GT = $elements[9];
			
			$info = $elements[7];
			$info_init = $info;

			$info = substr($info, strpos($info, ";EFF="));

			$sgarf = explode (",", $info);

			if(count($sgarf) > 1){
				for($s = 0; $s < count($sgarf); $s++){
					if(preg_match("/^intron/", $sgarf[$s]) || preg_match("/UTR/", $sgarf[$s])){
						$info = $sgarf[$s];
					}
				}
			}

			$stle = explode ("|", $info);
			$gene = $stle[5];
			$nm = $stle[8];
			$exon = $stle[9];
			$frags = explode("/", $stle[3]);
			$nt_change = (count($frags) == 2) ? $frags[1] : $frags[0];
			$aa_change = (count($frags) == 2) ? $frags[0] : "";
			
			$info_init = str_replace(";", ",", $info_init);
			$info_init = str_replace("|", ",", $info_init);

			$soft = "mutacaller";
		
			$info_mutacaller = $patient_id."".$chr."".$pos."".$ref."".$alt;
			
			$dbh->exec("insert into variants_mutacaller_$serie_rc values('$info_mutacaller', '$patient_id', '$gene', '$nm', '$exon', '$nt_change', '$aa_change', '$chr', '$pos', '$ref', '$alt', '$type', '$qual', '$info_init', '$format', '$GT', '$soft')");
			
		}
	}
}

$dbh = null;


?>
