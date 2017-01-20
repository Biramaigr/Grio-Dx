<?php

$bamdir = $argv[1];

chdir($bamdir);
if ($handle = opendir(".")) {
    while (false !== ($entry = readdir($handle))) {
		if (preg_match("/.bai$/", $entry)) {

        		$filename_renamed = str_replace(".bai", ".bam.bai", $entry);
        	
			system("mv $entry $filename_renamed");
        }
    }
    closedir($handle);
}

?>
