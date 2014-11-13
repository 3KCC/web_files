 //Browser Support Code
$(function (){
   $('#submit_button').on('click', function(e){
       e.preventDefault();
       var ajaxRequest;  // The variable that makes Ajax possible!

       try{
         // Opera 8.0+, Firefox, Safari, Chrome
         ajaxRequest = new XMLHttpRequest();
       }catch (e){
         // Internet Explorer Browsers
         try{
            ajaxRequest = new ActiveXObject("Msxml2.XMLHTTP");
         }catch (e) {
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
         if(ajaxRequest.readyState == 4){
            var js_obj_data = JSON.parse(ajaxRequest.responseText);
            var pp = js_obj_data.length;
         }

         // Load the Visualization API and the piechart package.
          google.load('visualization', '1.0', {'packages':['corechart']});

          // Set a callback to run when the Google Visualization API is loaded.
          google.setOnLoadCallback(drawChart());

          // Callback that creates and populates a data table,
          // instantiates the pie chart, passes in the data and
          // draws it.
          function drawChart(){
            // Create the data table.
            var data = new google.visualization.DataTable();
            data.addColumn('number', 'spread');
            data.addColumn('number', 'percentage');
            var index = 0; num = 0; per = 0; count = 0;
            alert(js_obj_data);
            for(var i = 0; i < 4; i++){

              index += Math.round(pp/4-1);
              num = js_obj_data[index];
              per = (js_obj_data.slice(0,index+1).length - count ) / pp;
              count += js_obj_data.slice(0,index+1).length;
              data.addRows([num, per]);
            }
            alert('a');
            // Set chart options
            var options = {'title':'How Much Pizza I Ate Last Night',
                           'width':400,
                           'height':300};

            // Instantiate and draw our chart, passing in some options.
            var chart = new google.visualization.LineChart(document.getElementById('chart_div'));
            chart.draw(data, options);
          }


       }
       // Now get the value from user and pass it to
       // server script.
       var target_name = document.getElementById('target_name').value;
       var CCY_pair = document.getElementById('CCY_pair').value;
       var from_date = document.getElementById('from_date').value;
       var to_date = document.getElementById('to_date').value;
       var queryString = "?target_name=" + target_name + "&CCY_pair=" + CCY_pair ;
       queryString +=  "&from_date=" + from_date + "&to_date=" + to_date ;
       ajaxRequest.open("GET", "js/getData3.php" + 
                                    queryString, true);
       ajaxRequest.send(null);
    });
});