<?php

$source_name = 'EZFX';
$ezfxTb = 'ezfxrates';
// Get data
$targets = $_GET['targets'];
$targets = json_decode($targets);
$target_ccy = $_GET['CCY_pair'];
$from_date = $_GET['from_date'];
$to_date = $_GET['to_date'];
$bid_offer = $_GET['bid_offer'];
// Change the formate
$from_date = date("Y-m-d", strtotime($from_date));
$to_date = date("Y-m-d", strtotime($to_date));
// Database connection
$conn = mysqli_connect("localhost","root","ezfx0109","crawlerdb");
if(!$conn) {
    die('Problem in database connection: ' . mysqli_error());
}

$rates_array = [];
$time_array = [];
$reduced_time_array = [];
foreach($targets AS $target_name){
    // get ID from database table url
    $query = "SELECT * FROM url WHERE name = '$target_name' ";
    $criteria = mysqli_query($conn, $query);

    while($row = mysqli_fetch_array($criteria))
    {
        $target_ID = $row['ID'];
    }
    //determine which table to get data
    if($target_ID == 'TRA' or $target_ID == 'MMM' or $target_ID == 'MUS'){
        $nameOfTb = 'RATES';
    }else{
        $nameOfTb = $target_ID.'rates';
    }

    // get rates from target rates table
    $target_query = genQuery($nameOfTb, $target_name, $target_ccy);
    $target_rate = mysqli_query($conn, $target_query);

    //trim the target name
    if(strlen($target_name) > 10) {
        $target_name = $target_ID;
    }

    $t_array = [];
    $t_time = [];

    #fetch the target data
    #check if fetch result is empty
    if(mysqli_num_rows($target_rate) == 0){
        $reduced_time_array = [];
        break;
    }else{
        while($row = mysqli_fetch_array($target_rate)){

            if(in_array($row['Date_p'], $reduced_time_array) || empty($reduced_time_array)){
                $t_bid = $row['Bid']/$row['Unit']; //Case sensitive
                $t_offer = $row['Offer']/$row['Unit'];
                if(array_key_exists('Inverse', $row) && $row['Inverse'] == 'Y'){
                    if($t_bid != 0){
                        $t_bid = 1/$t_bid; //Case sensitive
                    }
                    if($t_offer != 0){
                        $t_offer = 1/$t_offer;
                    }
                }

                if($target_name != 'EZFX'){
                    array_push($t_time, $row['Date_p']); //record time to match later
                    if($bid_offer == 'bid'){
                        array_push($t_array,number_format($t_bid,4));
                    }
                    else{
                        array_push($t_array,number_format($t_offer,4));
                    }
                }else{
                    #if there is a rate for this particular date
                    if(in_array($row['Date_p'], $t_time)){

                        #find the index of date_p in t_time array
                        $index_date = array_search($row['Date_p'], $t_time);
                        if($bid_offer == 'bid'){
                            #get the best rates for EZFX_BID
                            if($t_bid > $t_array[$index_date]){
                                $t_array[$index_date] = $t_bid;
                            }
                        }else{
                            #OFFER
                            if($t_offer < $t_array[$index_date]){
                                $t_array[$index_date] = $t_offer;
                            }
                        }
                    }else{
                        array_push($t_time, $row['Date_p']); //record time to match later
                        if($bid_offer == 'bid'){
                            array_push($t_array,number_format($t_bid,4));
                        }else{
                            array_push($t_array,number_format($t_offer,4));
                        }
                    }
                }
            }//end if date_p is in reduce_time_array
        }//end fetching data

        //push a set of data into the main array: [ [data1], [data2], ... ]
        array_push($rates_array, $t_array);
        //record time array to reduce later: [ [time1], [time2], ... ]
        array_push($time_array, $t_time);
        //set reduced time array = current target time array
        $reduced_time_array = $t_time;
    }//end check empty fetch data

}// end for loop through all targets specified


#remove unmatch data from target
for($i = 0; $i < sizeof($time_array); $i++){
    foreach($time_array[$i] as $value){
        if(!in_array($value, $reduced_time_array)){
            $index_val = array_search($value, $time_array[$i]);
            unset($rates_array[$i][$index_val]); //unset leaves all of the index values the same after an element is deleted
        }
    }
    #reset index
    $rates_array[$i] = array_values($rates_array[$i]);
}

#not empty source array means target array also no empty and so does the time array
if(!empty($reduced_time_array)) {
    echo json_encode([$rates_array,$reduced_time_array]);
}else{
    echo '[]';
}

mysqli_close($conn);

//generating queries according to different options
//input: options for target and ccy
//output: queries string
function genQuery($nameOfTb, $target, $ccy){
    global $target_ID, $from_date, $to_date, $ezfxTb, $bid_offer;

    if($target != 'EZFX'){
        $target_query = "SELECT * FROM $nameOfTb WHERE url = '$target_ID' AND RIGHT(ID,6) = '$ccy' AND STR_TO_DATE(date_p,'%d-%m-%Y') >= '$from_date' AND STR_TO_DATE(date_p,'%d-%m-%Y') <= '$to_date' AND CAST($bid_offer AS DECIMAL(9,4)) > 0 ORDER BY STR_TO_DATE(date_p,'%d-%m-%Y')";
    }else{
        $target_query = "SELECT * FROM $ezfxTb WHERE RIGHT(ID,6) = '$ccy' AND STR_TO_DATE(date_p, '%d-%m-%Y') >= '$from_date' AND STR_TO_DATE(date_p, '%d-%m-%Y') <= '$to_date' AND CAST($bid_offer AS DECIMAL(9,4)) > 0 ORDER BY STR_TO_DATE(date_p,'%d-%m-%Y')";
    }

    return $target_query;
}

?>