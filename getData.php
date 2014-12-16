<?php
$source_name = $_GET['source_name'];
$ezfxTb = 'ezfxrates';
// Get data
$target_name = $_GET['target_name'];
$chosen_date = $_GET['chosen_date'];
$view_as = $_GET['view_as'];

// Change the formate
$chosen_date = date("d-m-Y", strtotime($chosen_date));
// Database connection
$conn = mysqli_connect("localhost","root","ezfx0109","crawlerdb");
if(!$conn) {
die('Problem in database connection: ' . mysqli_error());
}

// get ID from database table url
$query = "SELECT * FROM url WHERE name = '$target_name' or name = '$source_name' ";
$criteria = mysqli_query($conn, $query);

while($row = mysqli_fetch_array($criteria))
{
    if($row['Name'] == $target_name){ $target_ID = $row['ID'];}
    if($row['Name'] == $source_name){ $source_ID = $row['ID'];}
}

//determine which table to get data
if($target_ID == 'TRA' or $target_ID == 'MMM' or $target_ID == 'MUS'){
    $nameOfTb = 'RATES';
}else{
    $nameOfTb = $target_ID.'rates';
}

// get rates from target rates table
$query = "SELECT * FROM $nameOfTb WHERE url = '$target_ID' AND date_p = '$chosen_date' ";
$target_rate = mysqli_query($conn, $query);
// get rates from source rates
if($source_name == 'EZFX'){
    $query = "SELECT * FROM $ezfxTb WHERE date_p = '$chosen_date' ";
}else {
    $query = "SELECT * FROM $nameOfTb WHERE date_p = '$chosen_date' and url = '$source_ID'"; 
}

$source_rate = mysqli_query($conn, $query);

//change the target name, source name to their ID respectively if they are too long
if(strlen($target_name) > 10) { $target_name = $target_ID;}
if(strlen($source_name) > 10) { $source_name = $source_ID;}

//Build Result String
$display_string = "<table>";
$display_string .= "<tr>";
$display_string .= "<th>CCY Pair</th>";
$display_string .= "<th>".str_replace(' ','',$target_name)."</th>";
$display_string .= "<th style=\"text-align: center\">".str_replace(' ','',$target_name)." - ".$source_name."<br><font style=\"text-transform: lowercase;\">(".$view_as.")</font></th>";
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
            if($source_name != 'EZFX'){
                //handling Inverse and unit is diffrent from 1
                if(trim($o_row['Unit']) == '1M'){
                    $o_row['Unit'] = 1000000;
                }
                $o_row['Bid'] /= $o_row['Unit']; //Case sensitive
                $o_row['Offer'] /= $o_row['Unit'];
                if($o_row['Inverse']=='Y'){
                    if($o_row['Bid'] != 0){ $o_row['Bid'] = 1/$o_row['Bid'];}
                    if($o_row['Offer'] != 0){ $o_row['Offer'] = 1/$o_row['Offer'];}
                }
            }
            //update if necessary
    		if($o_row['Bid'] > $s_bid){
    			$s_bid = $o_row['Bid'];
    		}
    		if($s_offer == 0 || $o_row['Offer'] < $s_offer){
    			$s_offer = $o_row['Offer'];
    		}
    	}
    }
    
    if($s_bid != 0 && $t_bid != 0) {
        if($view_as == 'pip'){
    	   $bid_dif = number_format(( round($t_bid,4) - round($s_bid,4) )*10000,0);
        } else{
           $bid_dif = number_format(($t_bid - $s_bid) * 100 / $s_bid, 2);
        }

        $display_bid .= "<tr><td>".$ccyCode."</td>";
        if($bid_dif > 0){
    	   $display_bid .= "<td><font color='#24890d'><b>".number_format($t_bid,4)."</b></font></td>
    									<td style=\"text-align: center\">".$bid_dif."</td>
    									<td>".number_format($s_bid,4)."</td></tr>";
        }elseif($bid_dif < 0){
            $display_bid .= "<td>".number_format($t_bid,4)."</td>
                                        <td style=\"text-align: center\">".$bid_dif."</td>
                                        <td><font color='#24890d'><b>".number_format($s_bid,4)."</b></font></td></tr>";
        }else{
            $display_bid .= "<td>".number_format($t_bid,4)."</td>
                                        <td style=\"text-align: center\">".$bid_dif."</td>
                                        <td>".number_format($s_bid,4)."</td></tr>";
        }
	}
    if($s_offer != 0 && $t_offer != 0) {
        if($view_as == 'pip'){
    	   $offer_dif = number_format(( round($t_offer,4) - round($s_offer, 4) )*10000,0);
        } else{
           $offer_dif = number_format(($t_offer - $s_offer) * 100 / $s_offer,2);
        }
        if($offer_dif > 0){
    	   $display_offer .= "<tr><td>".$ccyCode."</td>
    									<td>".number_format($t_offer,4)."</td>
    									<td style=\"text-align: center\">".$offer_dif."</td>
    									<td><font color='#24890d'><b>".number_format($s_offer,4)."</b></font></td></tr>";
        }elseif($offer_dif < 0){
            $display_offer .= "<tr><td>".$ccyCode."</td>
                                        <td><font color='#24890d'><b>".number_format($t_offer,4)."</b></font></td>
                                        <td style=\"text-align: center\">".$offer_dif."</td>
                                        <td>".number_format($s_offer,4)."</td></tr>";
        }else{
            $display_offer .= "<tr><td>".$ccyCode."</td>
                                        <td>".number_format($t_offer,4)."</td>
                                        <td style=\"text-align: center\">".$offer_dif."</td>
                                        <td>".number_format($s_offer,4)."</td></tr>";
        }
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