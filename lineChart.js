//on ready function
$(function (){
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
            var row = [];
            //data from PHP is ready
            if(ajaxRequest.readyState == 4){
                js_obj_data = ajaxRequest.responseText;
                //alert(js_obj_data);

                //colors for different line
                var color_arr = [
                    ["rgba(172,194,132,0.4)", "#ACC26D", "rgba(172,194,132,1)", "#fff", "#fff", "#9DB86D"],
                    ["rgba(151,187,205,0.2)", "rgba(151,187,205,1)", "rgba(151,187,205,1)", "#fff", "#fff", "rgba(151,187,205,1)"],
                    ["rgba(205,157,157,0.2)", "rgba(205,157,157,1)", "rgba(205,157,157,1)", "#fff", "#fff", "rgba(205,157,157,1)"],
                    ["rgba(232,223,102,0.2)", "rgba(232,223,102,1)", "rgba(232,223,102,1)", "#fff", "#fff", "rgba(232,223,102,1)"]
                ];

                //if there is enough data to draw
                if(js_obj_data.length > 0){

                    //destroy old graph to prevent hover problems caused display old chart
                    if(typeof myLineChart !== 'undefined'){
                        myLineChart.destroy();
                    }

                    var js_obj_data = JSON.parse(ajaxRequest.responseText);
                    //prepare data
                    row = js_obj_data[1];
                    var format_array = [];

                    //assgin data and format
                    for(var i = 0; i < js_obj_data[0].length; i++){

                        //reduce data points if over 20 points
                        var data = js_obj_data[0][i];
                        var len = data.length;
                        var max = $('#pt_quantity').val(); //fix maximum points
                        if(len > max){
                            var temp_data = [data[0]];
                            var temp_row = [row[0]];
                            var index;
                            for(var j = 1; j < max - 1; j++){
                                index =  Math.floor( j*(len/max) );
                                temp_data.push(data[ index ]);
                                temp_row.push(row[ index ]);
                            }
                            temp_data.push(data[ len - 1 ]);
                            temp_row.push(row[ len - 1 ]);
                            data = temp_data;
                            row = temp_row;
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
                    }

                    //declare data obj for drawing chart
                    var chartData = {
                        labels: row,
                        datasets: format_array
                    };

                    $('#offer_bid').empty().prepend('<p>OFFER</p>');
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
                    $('#offer_bid').after('<canvas id="buyers"></canvas>');

                    var buyers = document.getElementById('buyers').getContext('2d');
                    buyers.canvas.width = w; // resize to parent width
                    buyers.canvas.height = h; // resize to parent height
                    buyers.font="30px Verdana";
                    buyers.fillText("No data to display!",50,50);
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
        var queryString = "?targets=" + targets + "&CCY_pair=" + CCY_pair ;
        queryString +=  "&from_date=" + from_date + "&to_date=" + to_date ;
        ajaxRequest.open("GET", "js/lineChart.php" + 
                        queryString, true);
        ajaxRequest.send(null);
    });//end of Submit Button
});