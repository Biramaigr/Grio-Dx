<?php 

$serie = $argv[1];
$resultdir = $argv[2];
$ngs_path = $argv[3];

$dbh = new PDO("sqlite:$ngs_path/Genetics/RUNS/$serie/Analysis/DB/$serie.db");

$serie_rc = str_replace("-", "_", $serie);

$table_mutacaller = "variants_mutacaller_$serie_rc";
$table_deva = "variants_deva_$serie_rc";
					
$stmt = $dbh->query("select d.patient_id, d.chr, d.pos, d.ref, d.alt, d.type, d.refdepth, d.altdepth, d.allelicfreq, d.codonchange, d.aachange, m.gene, m.nm, m.exon, m.nt_change, m.aa_change, m.info from $table_mutacaller m inner join $table_deva d on m.identifiant = d.identifiant order by d.identifiant asc");
$stmt->setFetchMode(PDO::FETCH_ASSOC);

$fpn = null;

$patient_current = "";

while($row = $stmt->fetch()) {
	$patient_id = $row['patient_id'];
	$chr = $row['chr'];
	$pos = $row['pos'];
	$ref = $row['ref'];
	$alt = $row['alt'];
	$type = $row['type'];
	$refdepth = $row['refdepth'];
	$altdepth = $row['altdepth'];
	$allelicfreq = $row['allelicfreq'];
	$codonchange = $row['codonchange'];
	$aachange = $row['aachange'];
	
	$gene = $row['gene'];
	$nm = $row['nm'];
	$exon = $row['exon'];
	$nt_change = $row['nt_change'];
	$aa_change = $row['aa_change'];
	$info = $row['info'];
	
	$elts = explode(",", $info);
	$fa = round(explode("=", $elts[1])[1], 2);
	$stle = explode("=", $elts[0]);
	$dp_tot = $stle[1];
	$dp_alt = round($fa*$dp_tot);

	if($gene == "BRCA1"){
		$exon = ($exon >= 4) ? $exon+1 : $exon;
	}
	
	$zygosity = ($fa < 0.70 && $allelicfreq < 0.70 ) ? "htz" : "hmz";
	
	if(preg_match("/ins/", $nt_change)){		
		$motif = "g.".$pos."_".($pos+strlen($alt)-1)."".substr($alt, 1);		
	}
	else if(preg_match("/del/", $nt_change)){		
		$motif = "g.".($pos-1)."_".($pos+strlen($ref)-1)."".substr($ref, 1);
	}
	else{
		$motif = "g.".$pos."".$ref.">".$alt;
	}
	
	$bic_data = request_bic($motif, $gene, $ngs_path);
	$clinically_importance = ($bic_data != "") ? explode("|", $bic_data)[0] : "";
	$category = ($bic_data != "") ? explode("|", $bic_data)[1] : "";
	$hgvs_cdna = ($bic_data != "") ? explode("|", $bic_data)[1] : "";
	
	
	$bic_nomenclature = "";
	
	if(preg_match("/^c./", $nt_change)){
		if(preg_match("/ins/", $nt_change) || preg_match("/del/", $nt_change)){
			if(preg_match("/_/", $nt_change)){
				$bic_nomenclature = indel_transform($nt_change, $gene);
			}
			else{
				$bic_nomenclature = snv_transform($nt_change, $gene);
			}
		}
		else{
			$bic_nomenclature = snv_transform($nt_change, $gene);
		}
	}
	else{
		$bic_nomenclature = $hgvs_cdna;
	}
	
	$umd_data = request_umd($codonchange, $gene, $ngs_path);
	$biological_significance = ($umd_data != "") ? explode("|", $umd_data)[0] : "";
	$validation_by = ($umd_data != "") ? explode("|", $umd_data)[1] : "";
	
	
	$igr_effect = request_igr($nt_change, $gene, $ngs_path);
	
	$is_artefact = request_artefacts($nt_change, $gene, $ngs_path);

	$conclusion = ($is_artefact != "") ? $is_artefact : $igr_effect;

	$line = "$patient_id;$gene;$pos;$ref;$alt;$dp_tot;$dp_alt;$fa;$allelicfreq;$exon;$nt_change;$bic_nomenclature;$aa_change;$clinically_importance;$category;$biological_significance;$validation_by;$igr_effect;$zygosity;BOTH;$conclusion\n";
	
	if($patient_current != $patient_id){
		if($fpn){
			fclose($fpn);
		}
		$fpn = fopen($resultdir."/".$patient_id.".synthesis.ns.csv", 'w');
		fwrite($fpn, "patient_id;gene;pos;ref;alt;dp_tot;dp_alt;allelicfreq_mutascan;allelicfreq_deva;exon;HGVS_nomenclature;bic_nomenclature;aa_change;bic_clinically_importance;bic_category;umd_biological_significance;umd_validation_by;igr_effect;zygosity;soft;Conclusion\n");
		$patient_current = $patient_id;
	}
	
	fwrite($fpn, $line);
	#echo $line;
}



$stmt1 = $dbh->query("select * from $table_deva where identifiant not in(select identifiant from $table_mutacaller) order by identifiant asc");
	
$stmt1->setFetchMode(PDO::FETCH_ASSOC);

$patient_current = "";

while($row1 = $stmt1->fetch()) {
	$patient_id = $row1['patient_id'];
	$chr = $row1['chr'];
	#$gene = ($chr == "13") ? "BRCA2" : "BRCA1";
	$gene = $row1['gene'];
	$pos = $row1['pos'];
	$ref = $row1['ref'];
	$alt = $row1['alt'];
	$type = $row1['type'];
	$refdepth = $row1['refdepth'];
	$altdepth = $row1['altdepth'];
	$allelicfreq = $row1['allelicfreq'];
	$codonchange = $row1['codonchange'];
	$aachange = $row1['aachange'];
	$exon = $row1['exon'];

	if($gene == "BRCA1"){
		$exon = ($exon >= 4) ? $exon+1 : $exon;
	}

	$zygosity = ($allelicfreq < 0.70 ) ? "htz" : "hmz";
	
	if(preg_match("/ins/", $codonchange)){		
		$motif = "g.".$pos."_".($pos+strlen($alt)-1)."".substr($alt, 1);		
	}
	else if(preg_match("/del/", $codonchange)){		
		$motif = "g.".($pos-1)."_".($pos+strlen($ref)-1)."".substr($ref, 1);
	}
	else{
		$motif = "g.".$pos."".$ref.">".$alt;
	}
	
	$bic_data = request_bic($motif, $gene, $ngs_path);
	$clinically_importance = ($bic_data != "") ? explode("|", $bic_data)[0] : "";
	$category = ($bic_data != "") ? explode("|", $bic_data)[1] : "";
	$hgvs_cdna = ($bic_data != "") ? explode("|", $bic_data)[1] : "";
	
	$bic_nomenclature = "";
	
	if(preg_match("/^c./", $codonchange)){
		if(preg_match("/ins/", $codonchange) || preg_match("/del/", $codonchange)){
			if(preg_match("/_/", $codonchange)){
				$bic_nomenclature = indel_transform($codonchange, $gene);
			}
			else{
				$bic_nomenclature = snv_transform($codonchange, $gene);
			}
		}
		else{
			$bic_nomenclature = snv_transform($codonchange, $gene);
		}
	}
	else{
		$bic_nomenclature = $hgvs_cdna;
	}
	
	$umd_data = request_umd($codonchange, $gene, $ngs_path);
	$biological_significance = ($umd_data != "") ? explode("|", $umd_data)[0] : "";
	$validation_by = ($umd_data != "") ? explode("|", $umd_data)[1] : "";
	
	$igr_effect = request_igr($codonchange, $gene, $ngs_path);
	
	$is_artefact = request_artefacts($codonchange, $gene, $ngs_path);

	$conclusion = ($is_artefact != "") ? $is_artefact : $igr_effect;

	$line = "$patient_id;$gene;$pos;$ref;$alt;".($refdepth+$altdepth).";$altdepth;;$allelicfreq;$exon;$codonchange;$bic_nomenclature;$aachange;$clinically_importance;$category;$biological_significance;$validation_by;$igr_effect;$zygosity;Deva2;$conclusion\n";
	
	if($patient_current != $patient_id){
		if($fpn){
			fclose($fpn);
		}
		$fpn = fopen($resultdir."/".$patient_id.".synthesis.ns.csv", 'a');
		if ( 0 == filesize( $resultdir."/".$patient_id.".synthesis.ns.csv" ) ){
			fwrite($fpn, "patient_id;gene;pos;ref;alt;dp_tot;dp_alt;allelicfreq_mutascan;allelicfreq_deva;exon;HGVS_nomenclature;bic_nomenclature;aa_change;bic_clinically_importance;bic_category;umd_biological_significance;umd_validation_by;igr_effect;zygosity;soft;Conclusion\n");
		}
		$patient_current = $patient_id;
	}
	
	fwrite($fpn, $line);
	#echo $line;
}


$stmt2 = $dbh->query("select * from $table_mutacaller where identifiant not in(select identifiant from $table_deva) order by identifiant asc");
	
$stmt2->setFetchMode(PDO::FETCH_ASSOC);

$patient_current = "";

while($row2 = $stmt2->fetch()) {			
	$patient_id = $row2['patient_id'];
	$gene = $row2['gene'];
	$chr = $row2['chr'];
	$pos = $row2['pos'];
	$ref = $row2['ref'];
	$alt = $row2['alt'];
	$type = $row2['type'];	
	$gene = $row2['gene'];
	$nm = $row2['nm'];
	$exon = $row2['exon'];
	$nt_change = $row2['nt_change'];
	$aa_change = $row2['aa_change'];
	$info = $row2['info'];
	
	$elts = explode(",", $info);
	$fa = explode("=", $elts[1])[1];
	$stle = explode("=", $elts[0]);
	$dp_tot = $stle[1];
	$dp_alt = $fa*$dp_tot;
	
	if($gene == "BRCA1"){
		$exon = ($exon >= 4) ? $exon+1 : $exon;
	}

	$zygosity = ($fa < 0.70 ) ? "htz" : "hmz";
	
	if(preg_match("/ins/", $nt_change)){		
		$motif = "g.".$pos."_".($pos+strlen($alt)-1)."".substr($alt, 1);		
	}
	else if(preg_match("/del/", $nt_change)){		
		$motif = "g.".($pos-1)."_".($pos+strlen($ref)-1)."".substr($ref, 1);
	}
	else{
		$motif = "g.".$pos."".$ref.">".$alt;
	}
	
	$bic_data = request_bic($motif, $gene, $ngs_path);
	$clinically_importance = ($bic_data != "") ? explode("|", $bic_data)[0] : "";
	$category = ($bic_data != "") ? explode("|", $bic_data)[1] : "";
	$hgvs_cdna = ($bic_data != "") ? explode("|", $bic_data)[1] : "";
	
	$bic_nomenclature = "";
	
	if(preg_match("/^c./", $nt_change)){
		if(preg_match("/ins/", $nt_change) || preg_match("/del/", $nt_change)){
			if(preg_match("/_/", $nt_change)){
				$bic_nomenclature = indel_transform($nt_change, $gene);
			}
			else{
				$bic_nomenclature = snv_transform($nt_change, $gene);
			}
		}
		else{
			$bic_nomenclature = snv_transform($nt_change, $gene);
		}
	}
	else{
		$bic_nomenclature = $hgvs_cdna;
	}
	
	$umd_data = request_umd($nt_change, $gene, $ngs_path);
	$biological_significance = ($umd_data != "") ? explode("|", $umd_data)[0] : "";
	$validation_by = ($umd_data != "") ? explode("|", $umd_data)[1] : "";
	
	
	$igr_effect = request_igr($nt_change, $gene, $ngs_path);

	$is_artefact = request_artefacts($nt_change, $gene, $ngs_path);

	$conclusion = ($is_artefact != "") ? $is_artefact : $igr_effect;
	
	$line = "$patient_id;$gene;$pos;$ref;$alt;$dp_tot;$dp_alt;$fa;;$exon;$nt_change;$bic_nomenclature;$aa_change;$clinically_importance;$category;$biological_significance;$validation_by;$igr_effect;$zygosity;MutaCaller;$conclusion\n";

	if($patient_current != $patient_id){
		if($fpn){
			fclose($fpn);
		}
		$fpn = fopen($resultdir."/".$patient_id.".synthesis.ns.csv", 'a');
		if ( 0 == filesize( $resultdir."/".$patient_id.".synthesis.ns.csv" ) ){
			fwrite($fpn, "patient_id;gene;pos;ref;alt;dp_tot;dp_alt;allelicfreq_mutascan;allelicfreq_deva;exon;HGVS_nomenclature;bic_nomenclature;aa_change;bic_clinically_importance;bic_category;umd_biological_significance;umd_validation_by;igr_effect;zygosity;soft;Conclusion\n");

		}
		$patient_current = $patient_id;
	}
	
	fwrite($fpn, $line);
	#echo $line;
}


if($fpn){
	fclose($fpn);
}

$dbh = null;


function snv_transform($string, $gene){

	$string_new = "";
	
	if(preg_match("/c\.\*/", $string)){
		$string_new = $string;
	}
	else{
		$obj1 = preg_split("/\./", $string);
		$part1 = $obj1[1];
		
		$obj2 = preg_split("/\-|\+|[a-zA-Z]/", $part1);
		
		if(preg_match("/^\-/", $part1)){
			$pos2 = -1 * $obj2[1];
		}
		else if(preg_match("/^\+/", $part1)){
			$pos2 = $obj2[1];
		}
		else{
			$pos2 = $obj2[0];
		}
		
		if($gene == "BRCA1"){
			$new_pos1 = ($pos2 < 0) ? ($pos2 + 120) : ($pos2 + 119);
			
			$part1_new = str_replace($pos2, $new_pos1, $string);
					
			$string_new = $part1_new;
		}
		else if($gene == "BRCA2"){
			$new_pos1 = ($pos2 < 0) ? ($pos2 + 229) : ($pos2 + 228);
			
			$part1_new = str_replace($pos2, $new_pos1, $string);
					
			$string_new = $part1_new;
		}
	}
	
	return $string_new;
}


function indel_transform($string, $gene){
	$string_new = "";
	
	if(preg_match("/c\.\*/", $string)){
		$string_new = $string;
	}
	else{
		$obj = explode("_", $string);
		$part1 = $obj[0];
		$part2 = $obj[1];
		
		$suffix = substr($part1, 2);
		$obj1 = preg_split("/\-|\+/", $suffix);
		if(preg_match("/^\-/", $suffix)){
			$pos1 = -1 * $obj1[1];
		}
		else if(preg_match("/^\+/", $suffix)){
			$pos1 = $obj1[1];
		}
		else{
			$pos1 = $obj1[0];
		}
			
		$obj2 = preg_split("/\-|\+|[a-zA-Z]/", $part2);
		if(preg_match("/^\-/", $part2)){
			$pos2 = -1 * $obj2[1];
		}
		else if(preg_match("/^\+/", $part2)){
			$pos2 = $obj2[1];
		}
		else{
			$pos2 = $obj2[0];
		}
		
		if($gene == "BRCA1"){
			$new_pos1 = ($pos1 < 0) ? ($pos1 + 120) : $pos1 + 119;
			$new_pos2 = ($pos2 < 0) ? ($pos2 + 120) : $pos2 + 119;
			
			$part1_new = str_replace($pos1, $new_pos1, $part1);
			
			$part2_new = str_replace($pos2, $new_pos2, $part2);
			
			$string_new = $part1_new."_".$part2_new;
		}
		else if($gene == "BRCA2"){
			$new_pos1 = ($pos1 < 0) ? ($pos1 + 229) : $pos1 + 228;
			$new_pos2 = ($pos2 < 0) ? ($pos2 + 229) : $pos2 + 228;
			
			$part1_new = str_replace($pos1, $new_pos1, $part1);
			
			$part2_new = str_replace($pos2, $new_pos2, $part2);
			
			$string_new = $part1_new."_".$part2_new;
		}
	}
	
	return $string_new;
}

function reverse_compl($chaine){
	$eniahc = "";
	
	for($i = strlen($chaine)-1; $i >= 0; $i--){
		$curr_char = substr($chaine, $i, 1);
		
		if($curr_char == "A"){
			$curr_comp = "T";
		}
		else if($curr_char == "T"){
			$curr_comp = "A";
		}
		else if($curr_char == "C"){
			$curr_comp = "G";
		}
		else if($curr_char == "G"){
			$curr_comp = "C";
		}
		
		$eniahc .= $curr_comp;
	}

	return $eniahc;
}

function request_bic($motif, $gene, $ngs_path){
	$dbh = new PDO("sqlite:$ngs_path/Databases/bic.db");
					
	$stmt = $dbh->query("select clinically_importance, category, hgvs_cdna from BIC where hgvs_genomic = '$motif' and gene = '$gene' and hgvs_genomic != ''");
	$stmt->setFetchMode(PDO::FETCH_ASSOC);

	$resultset = "";
	while($row = $stmt->fetch()) {
		$clinically_importance = $row['clinically_importance'];
		$category = $row['category'];
		$hgvs_cdna = $row['hgvs_cdna'];
		
		$resultset = $clinically_importance."|".$category."|".$hgvs_cdna;
		
	}
	
	return $resultset;
}

function request_umd($motif, $gene, $ngs_path){
	$dbh = new PDO("sqlite:$ngs_path/Databases/umd.db");
	$stmt = $dbh->query("select biological_significance, validation_by from UMD where (cdna_nomenclature = '$motif' or cdna_nomenclature like '%($motif)%') and gene = '$gene' and cdna_nomenclature != ''");
	$stmt->setFetchMode(PDO::FETCH_ASSOC);

	$resultset = "";
	while($row = $stmt->fetch()) {
		$biological_significance = $row['biological_significance'];
		$validation_by = $row['validation_by'];
		$resultset = $biological_significance."|".$validation_by;
		
	}
	
	return $resultset;
}


function request_igr($motif, $gene, $ngs_path){
	$dbh = new PDO("sqlite:$ngs_path/Databases/igr.db");
	$stmt = $dbh->query("select effect from IGR where nomenclature_bic = '$motif' and gene = '$gene'");
	$stmt->setFetchMode(PDO::FETCH_ASSOC);

	$resultset = "";
	while($row = $stmt->fetch()) {
		$effect = $row['effect'];
		
		$resultset = $effect;
		
	}
	
	return $resultset;
}


function request_artefacts($motif, $gene, $ngs_path){
	$dbh = new PDO("sqlite:$ngs_path/Databases/artefacts.db");
	$stmt = $dbh->query("select conclusion from ARTEFACTS where nomenclature_hgvs = '$motif' and gene = '$gene'");
	$stmt->setFetchMode(PDO::FETCH_ASSOC);

	$resultset = "";
	while($row = $stmt->fetch()) {
		$conclusion1 = $row['conclusion'];
		
		$resultset = $conclusion1;
		
	}
	
	return $resultset;
}

?>
