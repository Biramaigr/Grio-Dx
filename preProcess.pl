#!/usr/bin/perl
use strict;

my $read1 = $ARGV[0];
my $read1_filter = $ARGV[1];
my $quality_filter = $ARGV[2];
my $length_filter_min = $ARGV[3];
my $length_filter_max = $ARGV[4];
my $gc_filter = $ARGV[5];

open(FILE1, $read1);

open(OUT1, ">$read1_filter");

my @lines1 = <FILE1> ;
my $size1 = @lines1;

for(my $l = 0; $l < $size1; $l++){
	my $id1 = $lines1[$l];
	my $sq1 = $lines1[$l+1];
	chomp($sq1);
	my $qual1 = $lines1[$l+3];
	chomp($qual1);
	
	my $sq_len1 = length $sq1;

	my $qual_len1 = length $qual1;
	if($qual_len1 == 0){
		$qual_len1 = 1;
	}
	
	my $qual_tot1 = 0;		

	for(my $q = 0; $q < $qual_len1; $q++){
		my $curr1 = substr($qual1, $q, 1);
		my $curr_qual1 = ord($curr1)-33;
		$qual_tot1 = int($qual_tot1 + $curr_qual1);
	}
	
	my $qual_mean1 = int($qual_tot1/$qual_len1);

	
	if($qual_mean1 >= $quality_filter && $sq_len1 >= $length_filter_min && $sq_len1 <= $length_filter_max){
		print OUT1 "$id1$sq1\n+\n$qual1\n";
	}
	
	$l = $l + 3;
}

close(OUT1);
close(FILE1);
