//on ready function
$(function(){
    //when check All, all boxes are checked
    $('#checkAll').change(function(){
        if($('#checkAll').is(':checked')){
            $('form input:checkbox').prop('checked', true);
        }else{
            $('form input:checkbox').prop('checked', false);
        }
    });

    //after check All, all boxes are checked. Then uncheck one box, uncheck All also
    $('.target_name').change(function(){
        if(this.id != 'All'){
            if(!$(this).is(':checked')){
                if($('#checkAll').is(':checked')){
                    $('#checkAll').prop('checked', false);
                }
            }
        }
    });

    var df_value = 35; //constant
    var min_pts = 2;
    //increase or decrease max points
    $('.adj_bt').on('click', function(e){
        e.preventDefault();
        var currentVal = parseInt($('#pt_quantity').val());
        //check if it is a valid number
        if(isNaN(currentVal)){
            currentVal = df_value;
        }

        if(this.id == 'de_bt'){
            if(currentVal <= min_pts){
                $('#pt_quantity').val(currentVal);
            }else{
                $('#pt_quantity').val(currentVal - 1);
            }
        }else{
            if(currentVal < min_pts){
                $('#pt_quantity').val(min_pts);
            }else{
                $('#pt_quantity').val(currentVal + 1);
            }
        }
    });

    //validate value for manual input
    $('#pt_quantity').change(function(){
        var currentVal = parseInt($('#pt_quantity').val());
        //check if it is a valid number
        if(isNaN(currentVal)){
            currentVal = df_value;
        }else if(currentVal < 2){
            currentVal = min_pts;
        }
        $('#pt_quantity').val(currentVal);
    });

    //trigger when click Submit button
    $('#submit_button').on('click', function(e){
        //prevent go back to the front page
        e.preventDefault();

        var ajaxRequest;  // The variable that makes Ajax possible!
        //Browser Support Code
        try{
            // Opera 8.0+, Firefox, Safari, Chrome
            ajaxRequest = new XMLHttpRequest();
        }catch (e){

            // Internet Explorer Browsers
            try{
                ajaxRequest = new ActiveXObject("Msxml2.XMLHTTP");
            }catch (e){

                try{
                    ajaxRequest = new ActiveXObject("Microsoft.XMLHTTP");
                }catch (e){
                    // Something went wrong
                    alert("Your browser broke!");
                    return false;
                }
            }
        }

        // Create a function that will receive data 
        // sent from the server and will update
        // div section in the same page.
        ajaxRequest.onreadystatechange = function(){
            //data from PHP is ready
            if(ajaxRequest.readyState == 4){
                var js_obj_data = JSON.parse(ajaxRequest.responseText);//canot be empty. Cause unexpected error
                //alert(js_obj_data);

                //colors for different line
                var color_arr = [
                    ["rgba(172,194,132,0.4)", "#ACC26D", "rgba(172,194,132,1)", "#fff", "#fff", "#9DB86D"],
                    ["rgba(151,187,205,0.2)", "rgba(151,187,205,1)", "rgba(151,187,205,1)", "#fff", "#fff", "rgba(151,187,205,1)"],
                    ["rgba(205,157,157,0.2)", "rgba(205,157,157,1)", "rgba(205,157,157,1)", "#fff", "#fff", "rgba(205,157,157,1)"],
                    ["rgba(232,223,102,0.2)", "rgba(232,223,102,1)", "rgba(232,223,102,1)", "#fff", "#fff", "rgba(232,223,102,1)"],
                    ["rgba(179,124,104,0.2)", "rgba(179,124,104,1)", "rgba(179,124,104,1)", "#fff", "#fff", "rgba(179,124,104,1)"],
                    ["rgba(116,125,145,0.2)", "rgba(116,125,145,1)", "rgba(116,125,145,1)", "#fff", "#fff", "rgba(116,125,145,1)"]
                ];

                //if there esists and is enoguh data to draw
                if(js_obj_data.length > 0 && js_obj_data[1].length > 1){
                    //destroy old graph to prevent hover problems caused display old chart
                    if(typeof myLineChart !== 'undefined'){
                        myLineChart.destroy();
                    }

                    //prepare data
                    var format_array = [];
                    var row = js_obj_data[1];
                    
                    var len = row.length;
                    var max = $('#pt_quantity').val(); //fix maximum points
                    if(len > max){
                        var keep_points = [0]; //keep the first data points
                        for(var j = 1; j < max - 1; j++){
                            var index =  Math.floor( j*((len-1)/(max-1)) );
                            keep_points.push(index);
                        }
                        keep_points.push(len - 1); //keep the last data points
                    }

                    if(typeof keep_points !== 'undefined'){
                        var temp_row = [];
                        keep_points.forEach(function(entry) {
                            temp_row.push(row[entry]);
                        });
                        row = temp_row;
                    }
                    //assgin data and format
                    for(var i = 0; i < js_obj_data[0].length; i++){
                        var data = js_obj_data[0][i];
                        if(typeof keep_points !== 'undefined'){
                            //reduce data points if over 20 points
                            var temp_data = [];
                            keep_points.forEach(function(entry) {
                                temp_data.push(data[entry]);
                            });
                            data = temp_data;
                        }

                        format_array.push(
                            {
                                label: target_name[i],
                                fillColor : color_arr[i][0],
                                strokeColor : color_arr[i][1],
                                pointColor : color_arr[i][2],
                                pointStrokeColor : color_arr[i][3],
                                pointHighlightFill : color_arr[i][4],
                                pointHighlightStroke : color_arr[i][5],
                                data : data
                            }
                        );
                    }//end for

                    //declare data obj for drawing chart
                    var chartData = {
                        labels: row,
                        datasets: format_array
                    };

                    $('#title_bo').empty().prepend('<p>' + bid_offer.toUpperCase() + '</p>');
                    //canvas for drawing chart                   
                    var canvas = document.getElementById('buyers');
                    var buyers = canvas.getContext('2d');

                    //DO NOT ADD VAR here, it caused chart re-appear when hover
                    myLineChart = new Chart(buyers).Line(chartData,
                        {
                            //String - A legend template
                            legendTemplate : "<% for (var i=0; i<datasets.length; i++){%><span class=\"legend\" style=\"border-color: <%=datasets[i].strokeColor%>\"><%if(datasets[i].label){%><%=datasets[i].label%><%}%></span><%}%>",
                            pointHitDetectionRadius : 1
                        });

                    //generate the legend
                    var legend = myLineChart.generateLegend();
                    //and append it to the page
                    $('#legend_container').empty().append(legend);
                
                //if there is not enough data to draw    
                }else{
                    var w = $('#buyers').width();
                    var h = $('#buyers').height();
                    $('#buyers').remove(); // this is my <canvas> element
                    $('#title_bo').empty().prepend('<p>' + bid_offer.toUpperCase() + '</p>');
                    $('#title_bo').after('<canvas id="buyers"></canvas>');

                    var buyers = document.getElementById('buyers').getContext('2d');
                    buyers.canvas.width = w; // resize to parent width
                    buyers.canvas.height = h; // resize to parent height
                    buyers.font="20px Verdana";
                    if(js_obj_data.length == 0){
                        buyers.fillText("No data to display!",150,50);
                    }else{
                        buyers.fillText("It requires at least 2 data points to draw!",30,50);
                    }
                }
            }//end if: PHP data is returned
        };//end onreadystagechange

        var targets = document.getElementsByClassName('target_name');
        var target_name = [];
        
        //condition for checkk All
        if($('#checkAll').is(':checked')){ 
            for (var i = 1; i < targets.length; i++) {
                target_name.push(targets[i].value);
            }
        }else{
            for (var i = 1; i < targets.length; i++) {
                if (targets[i].type === "checkbox" && targets[i].checked) {
                    target_name.push(targets[i].value);
                }
            }
        }

        // Now get the value from user and pass it to
        // server script.
        targets = JSON.stringify(target_name);
        var CCY_pair = document.getElementById('CCY_pair').value;
        var from_date = document.getElementById('from_date').value;
        var to_date = document.getElementById('to_date').value;
        var bid_offer = document.getElementsByClassName('bid_offer');
        for (var i = 0; i < bid_offer.length; i++) {
          if (bid_offer[i].type === "radio" && bid_offer[i].checked) {
            bid_offer = bid_offer[i].value;
            break;
          };
        }
        
        var queryString = "?targets=" + targets + "&CCY_pair=" + CCY_pair ;
        queryString +=  "&from_date=" + from_date + "&to_date=" + to_date ;
        queryString +=  "&bid_offer=" + bid_offer;
        ajaxRequest.open("GET", "js/lineChart.php" + 
                        queryString, true);
        ajaxRequest.send(null);
    });//end of Submit Button
});