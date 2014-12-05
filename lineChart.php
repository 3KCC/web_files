<?php

$source_name = 'EZFX';
$ezfxTb = 'ezfxrates';
// Get data
$target_name = $_GET['target_name'];
$target_ccy = $_GET['CCY_pair'];
$from_date = $_GET['from_date'];
$to_date = $_GET['to_date'];
// Change the formate
$from_date = date("Y-m-d", strtotime($from_date));
$to_date = date("Y-m-d", strtotime($to_date));
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

//trim the target name
if(strlen($target_name) > 10) {
    $target_name = $target_ID;
}

$array = [];
$t_array = [];
$s_array = [];
$t_time = [];
$s_time = [];
$_25th = 0.25; $_75th = 0.75;

#fetch the target data
while($row = mysqli_fetch_array($target_rate))
{
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
    array_push($t_time, $row['Date_p']); //record time to match later
    array_push($t_array,$t_offer);
}

#fetch the source data where the date match
while($row = mysqli_fetch_array($source_rate)){
    if(in_array($row['Date_p'], $t_time)){
        if(array_key_exists($row['Date_p'], $s_array)){
            #get the best rates for EZFX
            /*$row['Bid'] = $row['Bid']/$row['Unit'];
            if($row['Bid'] > $s_bid[$row['Date_p']]){
                $s_bid[$row['Date_p']] = $row['Bid'];
            }
            */
            $row['Offer'] = $row['Offer']/$row['Unit'];
            if($row['Offer'] < $s_array[$row['Date_p']]){
                $s_array[$row['Date_p']] = $row['Offer'];
            }
        }else{
            array_push($s_time, $row['Date_p']); //record only the matched dates
            $s_array[$row['Date_p']] = $row['Offer']/$row['Unit'];
        }
    }
}


#remove unmatch data from target
foreach($t_time as $value){
    if(!in_array($value, $s_time)){
        unset($t_array[array_search($value, $t_time)]); //unset leaves all of the index values the same after an element is deleted
    }
}
#reset index
$t_array = array_values($t_array);
array_push($array, $t_array, $s_array, $s_time);

#not empty source array means target array also no empty and so does the time array
if(!empty($s_array)) {
    echo json_encode($array);
}

mysqli_close($conn);

//generating queries according to different options
//input: options for target and ccy
//output: queries string
function genQuery($target, $ccy){
    global $target_ID, $from_date, $to_date,
            $ezfxTb;
    //Case1: both target and ccy are specific. Eg: Travelex, AUDMYR
    if(substr($ccy,-3) != 'All' and substr($target,-3) != 'All') {
        $target_query = "SELECT * FROM rates WHERE url = '$target_ID' AND RIGHT(ID,6) = '$ccy' AND STR_TO_DATE(date_p,'%d-%m-%Y') >= '$from_date' AND STR_TO_DATE(date_p,'%d-%m-%Y') <= '$to_date'";
        // get rates from source rates
        $source_query = "SELECT * FROM $ezfxTb WHERE RIGHT(ID,6) = '$ccy' AND STR_TO_DATE(date_p,'%d-%m-%Y') >= '$from_date' AND STR_TO_DATE(date_p,'%d-%m-%Y') <= '$to_date'";
        
    //Case2: only target is specific. Eg: Travelex, All
    }elseif(substr($ccy,-3) == 'All' and substr($target,-3) != 'All') {
        $target_query = "SELECT * FROM rates WHERE url = '$target_ID' AND STR_TO_DATE(date_p, '%d-%m-%Y') >= '$from_date' AND STR_TO_DATE(date_p, '%d-%m-%Y') <= '$to_date'";
        // get rates from source rates
        $source_query = "SELECT * FROM $ezfxTb WHERE STR_TO_DATE(date_p, '%d-%m-%Y') >= '$from_date' AND STR_TO_DATE(date_p, '%d-%m-%Y') <= '$to_date'";

    //Case3: only ccy is specific. Eg: All, AUDMYR
    }elseif(substr($ccy,-3) != 'All' and substr($target,-3) == 'All') {
        $target_query = "SELECT * FROM rates WHERE RIGHT(ID,6) = '$ccy' AND STR_TO_DATE(date_p, '%d-%m-%Y') >= '$from_date' AND STR_TO_DATE(date_p, '%d-%m-%Y') <= '$to_date'";
        // get rates from source rates
        $source_query = "SELECT * FROM $ezfxTb WHERE RIGHT(ID,6) = '$ccy' AND STR_TO_DATE(date_p, '%d-%m-%Y') >= '$from_date' AND STR_TO_DATE(date_p, '%d-%m-%Y') <= '$to_date'";

    //Case4: All-All
    }else {
        $target_query = "SELECT * FROM rates WHERE STR_TO_DATE(date_p, '%d-%m-%Y') >= '$from_date' AND STR_TO_DATE(date_p, '%d-%m-%Y') <= '$to_date'";
        // get rates from source rates
        $source_query = "SELECT * FROM $ezfxTb WHERE STR_TO_DATE(date_p, '%d-%m-%Y') >= '$from_date' AND STR_TO_DATE(date_p, '%d-%m-%Y') <= '$to_date'";
    }

    return array($target_query, $source_query);
}

?>