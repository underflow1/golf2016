#!/usr/bin/perl

use Switch;
use HTML::Restrict;

$rankmen = "www.owgr.com/en/Ranking.aspx?pageNo=1&pageSize=800&country=All";
$currentdate = `date +%Y-%m-%d`;
$currentfile = "./current";
$archivefile = "$currentdate";
$stringbegin = "table_container";
$stringend = "</div>";
$outputtable = "table.html";
$outputtableparse = "table.csv";

# СКАЧАТЬ ФАЙЛ
if (-e $currentfile) {`rm $currentfile`;}
`wget -O $currentfile "$rankmen"`;
unless (-e $archivefile) {
`cp $currentfile $archivefile`;
}

#вырезать таблицу из файла
$i = 0;
$line=1;
open(OUTFILE, '>', $outputtable) or die "Не могу открыть '$outputtable' $!";
open(INFILE,"$currentfile") or die $!;
    while(<INFILE>){
        if (/$stringbegin/) {
            print "found at : ".$line.":".$-[0].":".$+[0]."\n $_";
            $i=1;
        }

        if ($i > 0 && /$stringend/) {
            last;
            print "$_\n";
        }

        if ($i > 1) {
            print OUTFILE $_;
        }

        if ($i > 0) {$i++}

    $line++;
    }
close (INFILE);
close (OUTFILE);

#парсим таблицу в csv
$i = 0;
$a = 0;
$row = "<tr>"; #начало строки
$drop = "<thead>";
open(SRC,$outputtable)||die("Can't open file $outputtable");
open(DST, '>', $outputtableparse)||die("Can't open file $outputtableparse");
my $hr = HTML::Restrict->new();
while(<SRC>){
    if (/$row/){$i=1;$a=0;}
    if (/$drop/) {next}
    switch ($a){
        case 1 {my $processed = $hr->process($_); print DST "$processed\;"}
        case 4 {
            my $len=index($_,"alt");
            my $str2=substr($_,$len);
            @personal = split(/\"/, $str2);
            print DST "$personal[1]\;";
        }
        case 5 {$processed = $hr->process($_);print DST "$processed"}
        case 6 {print DST "\n"}
    }
    if ($i = 1) {$a++}
}
close(DST);
close(SRC);

# Приводим страны Великобритании к единому названию
open(SRC,$outputtableparse)||die("Can't open file $outputtableparse");
while(<SRC>){
    @line = split(";", $_);
    if ($line[1] eq "ENG" || $line[1] eq "WAL" || $line[1] eq "SCO" || $line[1] eq "IRL" || $line[1] eq "UK"){
        $line[1] = "GBR";
    }
    $line2 = join(";",@line);
    push (@aray, $line2);
}

# заполняем номер гольфиста в рейтинге его страны

$numgolfers = @aray;
foreach $n(1..$numgolfers){
    chomp($line1 = @aray[$n]);
    @lineN = split(";", $line1);
    if ($lineN[3] eq ""){
        $country = $line[1];
        $counter = 1;
        $lineN[3] = $counter;
#        push (@lineN, "$counter\n");
        $aray[$n] = join(";",@lineN);
#       print "$lineN[0];$lineN[1];$lineN[2];$lineN[3]\n";
        foreach $m($n+1..$numgolfers){
            chomp($line2 = @aray[$m]);
            @lineM = split(";", $line2);
            if ($lineM[1] eq $country){
                $counter++;
                $lineM[3] = $counter;
#                push (@lineM, "$counter\n");
                $aray[$m] = join(";",@lineM);
            }
        }
    }
}

close (SRC);
open(DST, '>', $outputtableparse)||die("Can't open file $outputtableparse");
foreach (@aray){
    print DST "$_\n";
}
close (DST);


