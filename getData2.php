<?php

$source_name = 'EZFX';
$ezfxTb = 'ezfxrates';
// Get data
$target_name = $_GET['target_name'];
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
$query = "SELECT * FROM rates WHERE url = '$target_ID' AND (date_p = '$from_date' OR date_p = '$to_date')";
$target_rate = mysqli_query($conn, $query);
// get rates from source rates
$query = "SELECT * FROM $ezfxTb WHERE date_p = '$from_date' OR date_p = '$to_date'";
$source_rate = mysqli_query($conn, $query);
$o_row = mysqli_fetch_array($source_rate);
$o_num = mysqli_num_rows($source_rate);

//trim the target name
if(strlen($target_name) > 10) {
    $target_name = $target_ID;
}

//Build Result String
$display_string = "<table>";
$display_string .= "<tr>";
$display_string .= "<th>CCY Pair</th>";
$display_string .= "<th>Population (&omega;)</th>";
$display_string .= "<th style=\"text-align: center\">Mean (<font style='text-transform: lowercase'>&micro;</font>)</th>";
$display_string .= "<th>Std. Dev. (<font style='text-transform: lowercase'>&#963;</font>)</th>";
$display_string .= "</tr>";

$display_bid = "BID<br>".$display_string;
$display_offer = "OFFER<br>".$display_string;

while($row = mysqli_fetch_array($target_rate))
{
    $ccyCode = substr($row['ID'],-6);
    $t_bid = $row['Bid']; //Case sensitive
    $t_offer = $row['Offer'];

    $s_bid = 0;
    $s_offer = 0;
    $i = 0;
    for($i = 0; $i < $o_num; $i++) {
    	if($ccyCode == substr($o_row['ID'],-6) ){
    		if($o_row['Bid'] > $s_bid){
    			$s_bid = $o_row['Bid'];
    		}
    		if($s_offer == 0 || $o_row['Offer'] < $s_offer){
    			$s_offer = $o_row['Offer'];
    		}
    	}
    }
    if($s_bid != 0 || $s_offer != 0){
    	if($t_bid != 0) {
	    	$bid_dif = round(($t_bid - $s_bid)*100/$s_bid,2);
	    	$display_bid .= "<tr><td>".$ccyCode."</td>
	    									<td>".$t_bid."</td>
	    									<td style=\"text-align: center\">".$bid_dif."</td>
	    									<td>".$s_bid."</td></tr>";
	    }
	    if($t_offer != 0) {
	    	$offer_dif = round(($t_offer - $s_offer)*100/$s_offer,2);
	    	$display_offer .= "<tr><td>".$ccyCode."</td>
	    									<td>".$t_offer."</td>
	    									<td style=\"text-align: center\">".$offer_dif."</td>
	    									<td>".$s_offer."</td></tr>";
	    }

	}
}
$display_bid .= "</table>";
$display_offer .= "</table>";
echo $display_bid;
echo $display_offer;
mysqli_close($conn);

?>