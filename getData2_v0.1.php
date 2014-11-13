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
$target_query = genQuery($target_name, $target_ccy)[0];
$source_query = genQuery($target_name, $target_ccy)[1];
$target_rate = mysqli_query($conn, $target_query);
$source_rate = mysqli_query($conn, $source_query);
/*
//Case1: both target and ccy are specific. Eg: Travelex, AUDMYR
if(substr($target_ccy,-3) != 'All' and substr($target_name,-3) != 'All') {
    $query = "SELECT * FROM rates WHERE url = '$target_ID' AND RIGHT(ID,6) = '$target_ccy' AND date_p >= '$from_date' AND date_p <= '$to_date'";
    $target_rate = mysqli_query($conn, $query);
    // get rates from source rates
    $query = "SELECT * FROM $ezfxTb WHERE RIGHT(ID,6) = '$target_ccy' AND date_p >= '$from_date' AND date_p <= '$to_date'";
    $source_rate = mysqli_query($conn, $query);
    //$o_row = mysqli_fetch_array($source_rate);
    //$o_num = mysqli_num_rows($source_rate);

//Case2: only target is specific. Eg: Travelex, All
}elseif(substr($target_ccy,-3) == 'All' and substr($target_name,-3) != 'All') {
    $query = "SELECT * FROM rates WHERE url = '$target_ID' AND date_p >= '$from_date' AND date_p <= '$to_date'";
    $target_rate = mysqli_query($conn, $query);
    // get rates from source rates
    $query = "SELECT * FROM $ezfxTb WHERE date_p >= '$from_date' AND date_p <= '$to_date'";
    $source_rate = mysqli_query($conn, $query);

//Case3: only ccy is specific. Eg: All, AUDMYR
}elseif(substr($target_ccy,-3) != 'All' and substr($target_name,-3) == 'All') {
    $query = "SELECT * FROM rates WHERE RIGHT(ID,6) = '$target_ccy' AND date_p >= '$from_date' AND date_p <= '$to_date'";
    $target_rate = mysqli_query($conn, $query);
    // get rates from source rates
    $query = "SELECT * FROM $ezfxTb WHERE RIGHT(ID,6) = '$target_ccy' AND date_p >= '$from_date' AND date_p <= '$to_date'";
    $source_rate = mysqli_query($conn, $query);

//Case4: All-All
}else {
    $query = "SELECT * FROM rates WHERE date_p >= '$from_date' AND date_p <= '$to_date'";
    $target_rate = mysqli_query($conn, $query);
    // get rates from source rates
    $query = "SELECT * FROM $ezfxTb WHERE date_p >= '$from_date' AND date_p <= '$to_date'";
    $source_rate = mysqli_query($conn, $query);
}
*/

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

$bid_array = [];
$offer_array = [];
$_25th = 0.25; $_75th = 0.75;

while($row = mysqli_fetch_array($target_rate))
{
    $ccyCode = substr($row['ID'],-6);
    $t_bid = $row['Bid']/$row['Unit']; //Case sensitive
    $t_offer = $row['Offer']/$row['Unit'];
    if($row['Inverse'] == 'Y'){
        if($t_bid != 0){
            $t_bid = 1/$t_bid; //Case sensitive
        }
        if($t_offer != 0){
            $t_offer = 1/$t_offer;
        }
    }

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
        #check and find the array with the current ccy to push new data found
        if (array_key_exists($ccyCode,$bid_array)){
            #key name is $ccyCode
            array_push($bid_array[$ccyCode],$bid_dif);
        }else{
            $bid_array[$ccyCode] = array($bid_dif);
        }
	}
    if($t_offer != 0 && $s_offer != 0) {
    	$offer_dif = round(($t_offer - $s_offer)*100/$s_offer,2);
        #check and find the array with the current ccy to push new data found
        if (array_key_exists($ccyCode,$offer_array)){
            #key name is $ccyCode
            array_push($offer_array[$ccyCode],$offer_dif);
        }else{
            $offer_array[$ccyCode] = array($offer_dif);
        }
    }

    #reset the pointer of mysqli_data_fetch
    mysqli_data_seek($source_rate,0);
}

#The bid_array and offer_array give the lists of [ccyA=>[dif1,dif2,dif3,...],ccyB=>[dif1,dif2,...],...]
#if it has mean value(at least one data point), display it
foreach ($bid_array as $key=>$value) {
    # $key = ccyA
    # $value = [dif1,dif2,....]
    $pp = count($value);
    if($pp != 0){
        $mean = round(array_sum($value)/$pp,2); 
        $std_dev = round(stats_standard_deviation($value),2);
        sort($value);
        $_25th_p = '- '; $_75th_p = '- ';
        if($pp > 1) {
            #Microsoft Exccel Algorithm
            #find d,k : (N-1)*P = k + d (k: int, d: decimal)
            #P_th = v_(k) + d*(v_(k+1) - v_(k))
            $dk = ($pp - 1) * $_25th ;
            $k = floor($dk);
            $d = $dk - $k;
            $_25th_p = round($value[$k] + $d * ($value[$k+1] - $value[$k]),2);
            $dk = ($pp - 1) * $_75th;
            $k = floor($dk);
            $d = $dk - $k;
            $_75th_p = round($value[$k] + $d * ($value[$k+1] - $value[$k]),2);
        }
        $display_bid .= "<tr><td>".$key."</td>
                                            <td style=\"text-align: center\">".$pp."</td>
                                            <td style=\"text-align: center\">".$mean."%</td>
                                            <td style=\"text-align: center\">".$std_dev."%</td>
                                            <td>".$_25th_p."%</td>
                                            <td>".$_75th_p."%</td></tr>";
    }
}

foreach ($offer_array as $key=>$value) {
    # $key = ccyA
    # $value = [dif1,dif2,....]
    $pp = count($value);
    if($pp != 0){
        $mean = round(array_sum($value)/$pp,2); 
        $std_dev = round(stats_standard_deviation($value),2);
        sort($value);
        $_25th_p = '- '; $_75th_p = '- ';
        if($pp > 1) {
            #Microsoft Exccel Algorithm
            #find d,k : (N-1)*P = k + d (k: int, d: decimal)
            #P_th = v_(k) + d*(v_(k+1) - v_(k))
            $dk = ($pp - 1) * $_25th;
            $k = floor($dk);
            $d = $dk - $k;
            $_25th_p = round($value[$k] + $d * ($value[$k+1] - $value[$k]),2);
            $dk = ($pp - 1) * $_75th;
            $k = floor($dk);
            $d = $dk - $k;
            $_75th_p = round($value[$k] + $d * ($value[$k+1] - $value[$k]),2);
        }
        $display_offer .= "<tr><td>".$key."</td>
                                            <td style=\"text-align: center\">".$pp."</td>
                                            <td style=\"text-align: center\">".$mean."%</td>
                                            <td style=\"text-align: center\">".$std_dev."%</td>
                                            <td>".$_25th_p."%</td>
                                            <td>".$_75th_p."%</td></tr>";
    }
}

$display_bid .= "</table>";
$display_offer .= "</table>";
echo $display_bid;
echo $display_offer;

mysqli_close($conn);

//generating queries according to different options
//input: options for target and ccy
//output: queries string
function genQuery($target, $ccy){
    global $target_ID, $from_date, $to_date,
            $ezfxTb;
    //Case1: both target and ccy are specific. Eg: Travelex, AUDMYR
    if(substr($ccy,-3) != 'All' and substr($target,-3) != 'All') {
        $target_query = "SELECT * FROM rates WHERE url = '$target_ID' AND RIGHT(ID,6) = '$ccy' AND date_p >= '$from_date' AND date_p <= '$to_date'";
        // get rates from source rates
        $source_query = "SELECT * FROM $ezfxTb WHERE RIGHT(ID,6) = '$ccy' AND date_p >= '$from_date' AND date_p <= '$to_date'";
        
    //Case2: only target is specific. Eg: Travelex, All
    }elseif(substr($ccy,-3) == 'All' and substr($target,-3) != 'All') {
        $target_query = "SELECT * FROM rates WHERE url = '$target_ID' AND date_p >= '$from_date' AND date_p <= '$to_date'";
        // get rates from source rates
        $source_query = "SELECT * FROM $ezfxTb WHERE date_p >= '$from_date' AND date_p <= '$to_date'";

    //Case3: only ccy is specific. Eg: All, AUDMYR
    }elseif(substr($ccy,-3) != 'All' and substr($target,-3) == 'All') {
        $target_query = "SELECT * FROM rates WHERE RIGHT(ID,6) = '$ccy' AND date_p >= '$from_date' AND date_p <= '$to_date'";
        // get rates from source rates
        $source_query = "SELECT * FROM $ezfxTb WHERE RIGHT(ID,6) = '$ccy' AND date_p >= '$from_date' AND date_p <= '$to_date'";

    //Case4: All-All
    }else {
        $target_query = "SELECT * FROM rates WHERE date_p >= '$from_date' AND date_p <= '$to_date'";
        // get rates from source rates
        $source_query = "SELECT * FROM $ezfxTb WHERE date_p >= '$from_date' AND date_p <= '$to_date'";
    }

    return array($target_query, $source_query);
}

?>