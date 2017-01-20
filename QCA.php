<?php 

$bamfile = $argv[1];
$bedfile = $argv[2];
$serie = $argv[3];
$ngs_path = $argv[4];
$dp_reseq = $argv[5];

include("$ngs_path/Programs/pChart/class/pData.class.php");
include("$ngs_path/Programs/pChart/class/pDraw.class.php");
include("$ngs_path/Programs/pChart/class/pImage.class.php");
include("$ngs_path/Programs/pChart/class/pPie.class.php");
include("$ngs_path/Programs/pChart/class/pIndicator.class.php");

$patient_id = str_replace(".bam", "", basename($bamfile));

$lines = file($bedfile);
$count = count($lines);

$fpn = fopen("$ngs_path/Genetics/RUNS/$serie/Analysis/QCAs/".$patient_id.".QCA.csv", 'w');

fwrite($fpn, "patient_id;amplicon;chr;start;stop;dp_mean;status;exon;dm_min;dp_max\n");

$array_x = array();
$array_y = array();

for($l = 0; $l < $count; $l++){
	$results = null;
	$line = $lines[$l];
	#echo "$line";
	$line = trim($line);
	$elements = explode ("\t", $line);

	$amplicon = $elements[0];
	$chr = $elements[1];
	$start = $elements[2];
	$stop = $elements[3];
	$exon = $elements[4];
	
	exec("/biopathdata/pipelineuser/NGS/Programs/samtools-0.1.19_no_cap/samtools depth -r $chr:$start-$stop $bamfile", $results);
	
	$array_dp = array();
	$summ = 0;
	for($m = 0; $m < count($results); $m++){
		$summ += explode("\t", $results[$m])[2];
		array_push($array_dp, explode("\t", $results[$m])[2]);
	}
	
	$results_size = (count($results) == 0) ? 1 : count($results);

	$dp_mean = round($summ/$results_size);
	
	if($dp_mean >= $dp_reseq){
		$status = "PASS";
	}
	else{
		$status = "FAIL";
	}
	
	fwrite($fpn, "$patient_id;$amplicon;$chr;$start;$stop;$dp_mean;$status;$exon;".min($array_dp).";".max($array_dp)."\n");
	
	array_push($array_x, $amplicon);
	array_push($array_y, $dp_mean);
}

fclose($fpn);

drawGraph($array_x, $array_y, $patient_id, $serie, $ngs_path);

function drawGraph($array_x, $array_y, $patient_id, $serie, $ngs_path){
	
	$MyData = new pData();

	$MyData->addPoints($array_y, "Depth");
	
	$MyData->setSerieWeight("QC_Amplicons",2);
	
	$MyData->setAxisName(0,"Depth");
	
	
	$MyData->addPoints($array_x, "Labels");
	$MyData->setSerieDescription("Labels","My labels");
	$MyData->setAbscissa("Labels");
	
	$MyData->setAxisName(0,"Depth");
	
	$myPicture = new pImage(1900,650,$MyData);
	
	
	$myPicture->drawRectangle(0,0,1900,500,array("R"=>0,"G"=>0,"B"=>0));
	 
	
	$myPicture->setFontProperties(array("FontName"=>"$ngs_path/Programs/pChart/fonts/Forgotte.ttf","FontSize"=>12));
	
	$myPicture->drawText(800,30,"$patient_id => Amplicon Depth Distribution",array("FontSize"=>20));
	
	$myPicture->setGraphArea(60,40,1800,500);
	
	$scaleSettings = array("LabelRotation"=>90,"XMargin"=>10,"YMargin"=>10,"Floating"=>TRUE,"GridR"=>200,"GridG"=>200,"GridB"=>200,"DrawSubTicks"=>TRUE,"CycleBackground"=>TRUE);
	
	$myPicture->drawScale($scaleSettings);
	
	$myPicture->setFontProperties(array("FontName"=>"$ngs_path/Programs/pChart/fonts/Bedizen.ttf","FontSize"=>5));
	
	$myPicture->drawBarChart(array("DisplayValues"=>TRUE,"PlotBorder"=>TRUE,"BorderSize"=>2,"Surrounding"=>-60,"BorderAlpha"=>80)); 
		
	$myPicture->drawThreshold(200, array("Alpha"=>70,"Ticks"=>0,"R"=>0,"G"=>0,"B"=>255));
	
	$myPicture->Render("$ngs_path/Genetics/RUNS/$serie/Analysis/QCAs/$patient_id.QCA.png");
	
}
?>
