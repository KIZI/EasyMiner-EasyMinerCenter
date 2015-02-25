<?php                 

define('INPUT_ENCODING','utf-8');
define('OUTPUT_ENCODING','iso-8859-2');

$title = iconv(INPUT_ENCODING,OUTPUT_ENCODING,$_GET['title']);
$xaxis = iconv(INPUT_ENCODING,OUTPUT_ENCODING,$_GET['xaxis']);
$yaxis = iconv(INPUT_ENCODING,OUTPUT_ENCODING,$_GET['yaxis']);
$val = $_GET['val'];
$lab = iconv(INPUT_ENCODING,OUTPUT_ENCODING,$_GET['lab']);
$other = (isset($_GET['other']) && $_GET['other']) ? 
	iconv(INPUT_ENCODING,OUTPUT_ENCODING,$_GET['other']) : NULL;
                          
$barWidth = 50;
$maxWidth = 640;
//offset of "sliding" subset to display
$from = $_GET['from'];
//number of items to display
$num = $other ? $_GET['num'] - 1 : $_GET['num'];
$labels = explode("@@",$lab);
$values = explode ("@@", $val);
//total number of items
$totalNum = count($labels);

//pair val with lab (i.e pair values with column names)
$XAxisLength = 0;
$YAxisLength = 0;
//fill data with "sliding" subset of all labels and values
for($i=$from; $i<$from+$num && $i<$totalNum; $i++) {
	//ensure well display of short label
	$labels[$i] = ' '.trim($labels[$i]).' ';
	$data[$labels[$i]]=$values[$i];
	$xLen = strlen($labels[$i]);
	$yLen = strlen($values[$i]);
	
	if($xLen>$XAxisLength) {
			$XAxisLength=$xLen;
	}
	
	if($yLen>$YAxisLength) {
			$YAxisLength=$yLen;
	}
}

if($totalNum > $num && $other) {
	/*** calculate "other" bar ***/
	$data[$other] = 0;
	//"other" label length check
	$xLen = strlen($other);
	if($xLen>$XAxisLength) {
			$XAxisLength=$xLen;
	}

	for($i=0; $i<$totalNum; $i++) {
		if($i>=$from && $i<$from+$num) {
			continue;
		}
		$data[$other] += $values[$i];
	}
	//"other" value lenght check
	$yLen = strlen($data[$other]);
	if($yLen>$YAxisLength) {
			$YAxisLength=$yLen;
	}
	/*** end of "other" caluculation ***/
}
//widht/heigh of graph
$wl = ($YAxisLength+2) * 6;
$w =  $wl + (min($num,$totalNum) * $barWidth);
if($w > $maxWidth) $w = $maxWidth;
$hl = $XAxisLength * 6; 
$h =  $hl + 200;
//entered as % of graph width/height
$YAxisLength = (100 * $wl / $w);
$XAxisLength = (100 * $hl / $h);

/*
echo '<pre>';
print_r(array(
	'GET' => $_GET,
	'from' => $from,
	'num' => $num,
	'totalNum' => $totalNum,
	'labels' => $labels,
	'values' => $values,
	'data' => $data,
	'w' => $w,
	'h' => $h,
	'YAxisLength' => $YAxisLength,
	'XAxisLength' => $XAxisLength,	
	));
echo '</pre>';
exit;
*/
                   
require("phpgraphlib.php");  
$graph=new PHPGraphLib($w,$h);  
$graph->addData($data);
$graph->setTitle($title);

$graph->setupXAxis($XAxisLength, "black");
$graph->setupYAxis($YAxisLength, "black");

$graph->setGradient("156,189,225", "19,92,184");
$graph->createGraph();
?>