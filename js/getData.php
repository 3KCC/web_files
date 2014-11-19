<?php
$source_name = $_GET['source_name'];
$ezfxTb = 'ezfxrates';
// Get data
$target_name = $_GET['target_name'];
$chosen_date = $_GET['chosen_date'];
// Change the formate
$chosen_date = date("d-m-Y", strtotime($chosen_date));
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
$query = "SELECT * FROM rates WHERE url = '$target_ID' AND date_p = '$chosen_date' ";
$target_rate = mysqli_query($conn, $query);
// get rates from source rates
if($source_name == 'EZFX'){
    $query = "SELECT * FROM $ezfxTb WHERE date_p = '$chosen_date' ";
}else {
    $query = "SELECT * FROM rates WHERE date_p = '$chosen_date' "; 
}
$source_rate = mysqli_query($conn, $query);
$o_num = mysqli_num_rows($source_rate);

//trim the target name
if(strlen($target_name) > 10) {
    $target_name = $target_ID;
}

//Build Result String
$display_string = "<table>";
$display_string .= "<tr>";
$display_string .= "<th>CCY Pair</th>";
$display_string .= "<th>".str_replace(' ','',$target_name)."</th>";
$display_string .= "<th style=\"text-align: center\">".str_replace(' ','',$target_name)." - ".$source_name."<br><font style=\"text-transform: lowercase;\">(pips)</font></th>";
$display_string .= "<th>".$source_name."</th>";
$display_string .= "</tr>";

$display_bid = "BID<br>".$display_string;
$display_offer = "OFFER<br>".$display_string;

while($row = mysqli_fetch_array($target_rate))
{
    $ccyCode = substr($row['ID'],-6);
    if(trim($row['Unit']) == '1M'){
        $row['Unit'] = 1000000;
    }
    $t_bid = $row['Bid']/$row['Unit']; //Case sensitive
    $t_offer = $row['Offer']/$row['Unit'];
    if($row['Inverse']=='Y'){
        if($t_bid != 0){ $t_bid = 1/$t_bid;}
        if($t_offer != 0){ $t_offer = 1/$t_offer;}
    }
    $s_bid = 0;
    $s_offer = 0;
    $i = 0;
    while ($o_row = mysqli_fetch_array($source_rate)) {
    	if($ccyCode == substr($o_row['ID'],-6) ){
    		if($o_row['Bid'] > $s_bid){
    			$s_bid = $o_row['Bid'];
    		}
    		if($s_offer == 0 || $o_row['Offer'] < $s_offer){
    			$s_offer = $o_row['Offer'];
    		}
    	}
    }
    if($s_bid != 0 && $t_bid != 0) {
    	$bid_dif = round(($t_bid - $s_bid)*10000,2);
    	$display_bid .= "<tr><td>".$ccyCode."</td>
    									<td>".number_format($t_bid,4)."</td>
    									<td style=\"text-align: center\">".$bid_dif."</td>
    									<td>".number_format($s_bid,4)."</td></tr>";
	}
    if($s_offer != 0 && $t_offer != 0) {
    	$offer_dif = round(($t_offer - $s_offer)*10000,2);
    	$display_offer .= "<tr><td>".$ccyCode."</td>
    									<td>".number_format($t_offer,4)."</td>
    									<td style=\"text-align: center\">".$offer_dif."</td>
    									<td>".number_format($s_offer,4)."</td></tr>";
    }

    #reset the pointer of mysqli_data_fetch
    mysqli_data_seek($source_rate,0);
}

$display_bid .= "</table>";
$display_offer .= "</table>";
echo $display_bid;
echo $display_offer;
mysqli_close($conn);

?>