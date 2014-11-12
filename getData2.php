<?php

$source_name = 'EZFX';
$ezfxTb = 'ezfxrates';
// Get data
$target_name = $_GET['target_name'];
$target_ccy = $_GET['CCY_pair'];
$from_date = $_GET['from_date'];
$to_date = $_GET['to_date'];
// Change the formate
$from_date = date("d-m-Y", strtotime($from_date));
$to_date = date("d-m-Y", strtotime($to_date));
// Database connection
$conn = mysqli_connect("localhost","root","ezfx0109","crawlerdb");
if(!$conn) {
die('Problem in database connection: ' . mysqli_error());
}

// get ID from database table url
$query = "SELECT * FROM url WHERE name = '$target_name' ";
$criteria = mysqli_query($conn, $query);

while($row = mysqli_fetch_array($criteria))
{
    $target_ID = $row['ID'];
}
// get rates from target rates table
if(substr($target_ccy,-3) != 'All') {
    $query = "SELECT * FROM rates WHERE url = '$target_ID' AND RIGHT(ID,6) = '$target_ccy' AND date_p >= '$from_date' AND date_p <= '$to_date'";
    $target_rate = mysqli_query($conn, $query);
    // get rates from source rates
    $query = "SELECT * FROM $ezfxTb WHERE RIGHT(ID,6) = '$target_ccy' AND date_p >= '$from_date' AND date_p <= '$to_date'";
    $source_rate = mysqli_query($conn, $query);
    //$o_row = mysqli_fetch_array($source_rate);
    //$o_num = mysqli_num_rows($source_rate);
}else {
    $query = "SELECT * FROM rates WHERE url = '$target_ID' AND date_p >= '$from_date' AND date_p <= '$to_date'";
    $target_rate = mysqli_query($conn, $query);
    // get rates from source rates
    $query = "SELECT * FROM $ezfxTb WHERE date_p >= '$from_date' AND date_p <= '$to_date'";
    $source_rate = mysqli_query($conn, $query);
}

//trim the target name
if(strlen($target_name) > 10) {
    $target_name = $target_ID;
}

//Build Result String
$display_string = "<table>";
$display_string .= "<tr>";
$display_string .= "<th>CCY Pair</th>";
$display_string .= "<th style=\"text-align: center\">Population (&omega;)</th>";
$display_string .= "<th style=\"text-align: center\">Mean (<font style='text-transform: lowercase'>&micro;</font>)</th>";
$display_string .= "<th style=\"text-align: center\">Std. Dev. (<font style='text-transform: lowercase'>&#963;</font>)</th>";
$display_string .= "<th><font style='text-transform: lowercase'>25th</font></th>";
$display_string .= "<th><font style='text-transform: lowercase'>75th</font</th>";
$display_string .= "</tr>";

$display_bid = "<p style='text-align: left'>BID</p>".$display_string;
$display_offer = "<p style='text-align: left'>OFFER</p>".$display_string;

$pp_bid = 0; $pp_offer = 0;
$sum_bid = 0; $sum_offer = 0;
$bid_array = [];
$offer_array = [];
$_25th = 0.25; $_75th = 0.75;
while($row = mysqli_fetch_array($target_rate))
{
    $ccyCode = substr($row['ID'],-6);
    $t_bid = $row['Bid']/$row['Unit']; //Case sensitive
    $t_offer = $row['Offer']/$row['Unit'];

    $s_bid = 0;
    $s_offer = 0;
    $i = 0;
    while($o_row = mysqli_fetch_array($source_rate)) {
    	if($ccyCode == substr($o_row['ID'],-6) && $o_row['Date_p'] == $row['Date_p']){
    		if($o_row['Bid'] > $s_bid){
    			$s_bid = $o_row['Bid'];
    		}
    		if($s_offer == 0 || $o_row['Offer'] < $s_offer){
    			$s_offer = $o_row['Offer'];
    		}
    	}
    }
    if($s_bid != 0 && $t_bid != 0){
    	$bid_dif = round(($t_bid - $s_bid)*100/$s_bid,2);
        $pp_bid++;
        $sum_bid += $bid_dif;
        array_push($bid_array,$bid_dif);
        $mean_bid = round($sum_bid/$pp_bid,2);
        $std_dev_bid = round(stats_standard_deviation($bid_array),2); 
	}
    if($t_offer != 0 && $s_offer != 0) {
    	$offer_dif = round(($t_offer - $s_offer)*100/$s_offer,2);
        $pp_offer++;
        $sum_offer += $offer_dif;
        array_push($offer_array,$offer_dif);
    }

    #reset the pointer of mysqli_data_fetch
    mysqli_data_seek($source_rate,0);
}
#if it has mean value(at least one data point), display it
if(isset($mean_bid)){
    $mean_bid = round($sum_bid/$pp_bid,2);
    $std_dev_bid = round(stats_standard_deviation($bid_array),2);
    sort($bid_array);
    $_25th_bid = '- '; $_75th_bid = '- ';
    if($pp_bid > 1) {
        #Microsoft Exccel Algorithm
        #find d,k : (N-1)*P + 1 = k + d (k: int, d: decimal)
        #P_th = v_(k) + d*(v_(k+1) - v_(k))
        $dk = ($pp_bid - 1) * $_25th + 1;
        $k = floor($dk);
        $d = $dk - $k;
        $_25th_bid = round($bid_array[$k-1] + $d * ($bid_array[$k] - $bid_array[$k-1]),2);
        $dk = ($pp_bid - 1) * $_75th + 1;
        $k = floor($dk);
        $d = $dk - $k;
        $_75th_bid = round($bid_array[$k-1] + $d * ($bid_array[$k] - $bid_array[$k-1]),2);
    }
    $display_bid .= "<tr><td>".$ccyCode."</td>
                                        <td style=\"text-align: center\">".$pp_bid."</td>
                                        <td style=\"text-align: center\">".$mean_bid."%</td>
                                        <td>".$std_dev_bid."%</td>
                                        <td>".$_25th_bid."%</td>
                                        <td>".$_75th_bid."%</td></tr>";
}
if($pp_offer != 0){
    $mean_offer = round($sum_offer/$pp_offer,2);
    $std_dev_offer = round(stats_standard_deviation($offer_array),2);
    sort($offer_array);
    $_25th_offer = '- '; $_75th_offer = '- ';
    if($pp_offer > 1) {
        #Microsoft Exccel Algorithm
        #find d,k : (N-1)*P + 1 = k + d (k: int, d: decimal)
        #P_th = v_(k) + d*(v_(k+1) - v_(k))
        $dk = ($pp_offer - 1) * $_25th + 1;
        $k = floor($dk);
        $d = $dk - $k;
        $_25th_offer = round($offer_array[$k-1] + $d * ($offer_array[$k] - $offer_array[$k-1]),2);
        $dk = ($pp_offer - 1) * $_75th + 1;
        $k = floor($dk);
        $d = $dk - $k;
        $_75th_offer = round($offer_array[$k-1] + $d * ($offer_array[$k] - $offer_array[$k-1]),2);
    }
    $display_offer .= "<tr><td>".$ccyCode."</td>
                                        <td style=\"text-align: center\">".$pp_offer."</td>
                                        <td style=\"text-align: center\">".$mean_offer."%</td>
                                        <td style=\"text-align: center\">".$std_dev_offer."%</td>
                                        <td>".$_25th_offer."%</td>
                                        <td>".$_75th_offer."%</td></tr>";
}
$display_bid .= "</table>";
$display_offer .= "</table>";
echo $display_bid;
echo $display_offer;

mysqli_close($conn);

?>