<?php

$serie = $argv[1];
$bwa_algo = $argv[2];
$is_pp = $argv[3];
$ngs_path = $argv[4];
$mode = $argv[5];
$length_min = $argv[6];
$is_indel_realign = $argv[7];
$fastq_group = $argv[8];
$bed = $argv[9];
$depth_variation_threshold = $argv[10];
$af_threshold = $argv[11];
$dp_threshold = $argv[12];

$files = getFiles("$ngs_path/Genetics/RUNS/$serie/$fastq_group");

sort($files);

for($f = 0; $f < count($files); $f++){
	$read1 = $files[$f];
	$read2 = $files[$f+1];
	
	$read1_filtered = preg_replace("/.fastq.gz|.fastq/", ".filtered.fastq", $read1);
	$read2_filtered = preg_replace("/.fastq.gz|.fastq/", ".filtered.fastq", $read2);
	
	$out = preg_replace("/_R1_001.fastq.gz|_R1_001.fastq/", "", $read1)."_001";
	
	if(preg_match("/.gz$/", $read1)){
		system("gzip -d $ngs_path/Genetics/RUNS/$serie/fastQ/$read1");
	}
	
	if(preg_match("/.gz$/", $read2)){
		system("gzip -d $ngs_path/Genetics/RUNS/$serie/fastQ/$read2");
	}
	
	$read1_gunzip = preg_replace("/.gz$/", "", $read1);
	$read2_gunzip = preg_replace("/.gz$/", "", $read2);
	
	if($is_pp == "Yes"){
		mkdir("$ngs_path/Genetics/RUNS/$serie/fastQ_filter");
		system("perl $ngs_path/Scripts/Genetics_Amplicon/$mode/preProcess.pl $ngs_path/Genetics/RUNS/$serie/fastQ/$read1_gunzip $ngs_path/Genetics/RUNS/$serie/fastQ_filter/$read1_filtered 30 $length_min 160 50 >>$ngs_path/Genetics/RUNS/$serie/Analysis/Logs/$out.log 2>&1");
		system("perl $ngs_path/Scripts/Genetics_Amplicon/$mode/preProcess.pl $ngs_path/Genetics/RUNS/$serie/fastQ/$read2_gunzip $ngs_path/Genetics/RUNS/$serie/fastQ_filter/$read2_filtered 30 $length_min 160 50 >>$ngs_path/Genetics/RUNS/$serie/Analysis/Logs/$out.log 2>&1");
		
		system("/biopathdata/pipelineuser/NGS/Programs/bwa-0.7.5a/bwa-0.7.5a/bwa mem -t 2 $ngs_path/Databases/hg19/hg19.fa $ngs_path/Genetics/RUNS/$serie/fastQ_filter/$read1_filtered $ngs_path/Genetics/RUNS/$serie/fastQ_filter/$read2_filtered | /biopathdata/pipelineuser/NGS/Programs/samtools-0.1.19/samtools view -bT $ngs_path/Databases/hg19/hg19.fa -> $ngs_path/Genetics/RUNS/$serie/Analysis/Bams_RAW/$out.q0.mem.bam");	
		
		system("/biopathdata/pipelineuser/NGS/Programs/bwa-0.7.5a/bwa-0.7.5a/bwa bwasw -t 2 -q 1 -r 1 $ngs_path/Databases/hg19/hg19.fa $ngs_path/Genetics/RUNS/$serie/fastQ_filter/$read1_filtered $ngs_path/Genetics/RUNS/$serie/fastQ_filter/$read2_filtered | /biopathdata/pipelineuser/NGS/Programs/samtools-0.1.19/samtools view -bT $ngs_path/Databases/hg19/hg19.fa -> $ngs_path/Genetics/RUNS/$serie/Analysis/Bams_RAW/$out.q0.bam");			
		
	}
	else{
		system("/biopathdata/pipelineuser/NGS/Programs/bwa-0.7.5a/bwa mem -t 2 $ngs_path/Databases/hg19/hg19.fa $ngs_path/Genetics/RUNS/$serie/fastQ/$read1_gunzip $ngs_path/Genetics/RUNS/$serie/fastQ/$read2_gunzip | /biopathdata/pipelineuser/NGS/Programs/samtools-0.1.19/samtools view -bT $ngs_path/Databases/hg19/hg19.fa -> $ngs_path/Genetics/RUNS/$serie/Analysis/Bams_RAW/$out.q0.mem.bam");	
		
		system("/biopathdata/pipelineuser/NGS/Programs/bwa-0.7.5a/bwa bwasw -t 2 -q 1 -r 1 $ngs_path/Databases/hg19/hg19.fa $ngs_path/Genetics/RUNS/$serie/fastQ/$read1_gunzip $ngs_path/Genetics/RUNS/$serie/fastQ/$read2_gunzip | /biopathdata/pipelineuser/NGS/Programs/samtools-0.1.19/samtools view -bT $ngs_path/Databases/hg19/hg19.fa -> $ngs_path/Genetics/RUNS/$serie/Analysis/Bams_RAW/$out.q0.bam");	
		
	}
	
	system("/biopathdata/pipelineuser/NGS/Programs/samtools-0.1.19/samtools view -bq 10 $ngs_path/Genetics/RUNS/$serie/Analysis/Bams_RAW/$out.q0.bam > $ngs_path/Genetics/RUNS/$serie/Analysis/Bams_RAW/$out.nrg.bam");
	system("java -Djava.io.tmpdir=$ngs_path/tmp -Xmx8g -jar $ngs_path/Programs/picard-tools/picard.jar AddOrReplaceReadGroups RGLB=read_id RGPL=illumina RGPU=run RGSM=rgsm I=$ngs_path/Genetics/RUNS/$serie/Analysis/Bams_RAW/$out.nrg.bam O=$ngs_path/Genetics/RUNS/$serie/Analysis/Bams/$out.bam SORT_ORDER=coordinate CREATE_INDEX=TRUE VALIDATION_STRINGENCY=LENIENT");

	system("/biopathdata/pipelineuser/NGS/Programs/samtools-0.1.19/samtools view -bq 10 $ngs_path/Genetics/RUNS/$serie/Analysis/Bams_RAW/$out.q0.mem.bam > $ngs_path/Genetics/RUNS/$serie/Analysis/Bams_RAW/$out.nrg.mem.bam");
	system("java -Djava.io.tmpdir=$ngs_path/tmp -Xmx8g -jar $ngs_path/Programs/picard-tools/picard.jar AddOrReplaceReadGroups RGLB=read_id RGPL=illumina RGPU=run RGSM=rgsm I=$ngs_path/Genetics/RUNS/$serie/Analysis/Bams_RAW/$out.nrg.mem.bam O=$ngs_path/Genetics/RUNS/$serie/Analysis/Bams_MEM/$out.bam SORT_ORDER=coordinate CREATE_INDEX=TRUE VALIDATION_STRINGENCY=LENIENT");

	#system("java -Djava.io.tmpdir=$ngs_path/tmp -Xmx8g -jar $ngs_path/Programs/GATK/GenomeAnalysisTK.jar -T HaplotypeCaller -R $ngs_path/Databases/hg19/hg19.fa -I $ngs_path/Genetics/RUNS/$serie/Analysis/Bams_MEM/$out.bam --genotyping_mode DISCOVERY -stand_emit_conf 0 -stand_call_conf 0 -L $ngs_path/Genetics/$bed -o $ngs_path/Genetics/RUNS/$serie/Analysis/Resultats_deva/$out.vcf -rf BadCigar");
	system("java -Djava.io.tmpdir=$ngs_path/tmp -Xmx8g -jar $ngs_path/Programs/GATK/v-3.4-46/GenomeAnalysisTK.jar -T HaplotypeCaller -R $ngs_path/Databases/hg19/hg19.fa -I $ngs_path/Genetics/RUNS/$serie/Analysis/Bams_MEM/$out.bam --genotyping_mode DISCOVERY -stand_emit_conf 0 -stand_call_conf 0 -L $ngs_path/Genetics/$bed -o $ngs_path/Genetics/RUNS/$serie/Analysis/Resultats_deva/$out.vcf -rf BadCigar");
	unlink("$ngs_path/Genetics/RUNS/$serie/Analysis/Resultats_deva/$out.vcf.idx");
	
	system("java -Djava.io.tmpdir=$ngs_path/tmp -Xmx8g -jar $ngs_path/Programs/MutaCaller-1.6/MutaCaller-1.6.jar -LIBS_PATH=$ngs_path/Programs/MutaCaller-1.6 -MATE_TYPE=PAIRED -DPMIN=$dp_threshold -AFMIN=$af_threshold -PQUALMIN=10 -CLUSTER_WINDOW=20 -CLUSTER_NUMBER=4 -REFERENCE=$ngs_path/Databases/hg19/hg19.fa -BEDFILE=$ngs_path/Genetics/$bed -BIG_INDELS=YES -BIG_INDELS_OPTIONS=$depth_variation_threshold,10,10,0.15,YES -INPUT=$ngs_path/Genetics/RUNS/$serie/Analysis/Bams/$out.bam -OUTPUT=$ngs_path/Genetics/RUNS/$serie/Analysis/Resultats_mutacaller/$out.ns.vcf 2>>$ngs_path/Genetics/RUNS/$serie/Analysis/Logs/$out.log");
	system("cat $ngs_path/Genetics/RUNS/$serie/Analysis/Resultats_mutacaller/$out.ns.vcf | /biopathdata/pipelineuser/NGS/Programs/vcftools_0.1.12b/bin/vcf-sort > $ngs_path/Genetics/RUNS/$serie/Analysis/Resultats_mutacaller/$out.vcf");
	unlink("$ngs_path/Genetics/RUNS/$serie/Analysis/Resultats_mutacaller/$out.ns.vcf");
	
	mkdir("$ngs_path/Genetics/RUNS/$serie/Analysis/Synchro/$out");

	$f++;
}

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

?>
