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
        if(typeof myLineChart !== 'undefined'){
          myLineChart.destroy();
        }
          var data1 = [], data2 = [];
          var row = [];
         if(ajaxRequest.readyState == 4){
            js_obj_data = ajaxRequest.responseText;
            alert(js_obj_data);
            if(js_obj_data.length > 0){
              var js_obj_data = JSON.parse(ajaxRequest.responseText);
              //prepare data
              data1 = js_obj_data[0];
              data2 = js_obj_data[1];
              row = js_obj_data[2];

            var buyerData = {
              labels : row,
              datasets : [
                {
                  label: target_name,
                  fillColor : "rgba(172,194,132,0.4)",
                  strokeColor : "#ACC26D",
                  pointColor : "#fff",
                  pointStrokeColor : "#9DB86D",
                  data : data1
                },
                {
                  label: 'EZFX',
                  fillColor: "rgba(151,187,205,0.2)",
                  strokeColor: "rgba(151,187,205,1)",
                  pointColor: "rgba(151,187,205,1)",
                  pointStrokeColor: "#fff",
                  data : data2
                }
              ]
            };
       var canvas = document.getElementById('buyers');
       var buyers = canvas.getContext('2d');
       myLineChart = new Chart(buyers).Line(buyerData,{
        //String - A legend template
        legendTemplate : 
          "<% for (var i=0; i<datasets.length; i++){%><span class=\"legend\" style=\"border-color: <%=datasets[i].strokeColor%>\"><%if(datasets[i].label){%><%=datasets[i].label%><%}%></span><%}%>"
       });
       //generate the legend
        var legend = myLineChart.generateLegend();
        //and append it to the page
        $('#legend_container').empty().append(legend);

     } else {
        var w = $('#buyers').width();
        var h = $('#buyers').height();
       $('#buyers').remove(); // this is my <canvas> element
       $('#graph-containers').prepend('<canvas id="buyers"></canvas>');
       
        var buyers = document.getElementById('buyers').getContext('2d');
        buyers.canvas.width = w; // resize to parent width
        buyers.canvas.height = h; // resize to parent height
       buyers.font="30px Verdana";
       buyers.fillText("No data to display!",50,50);

     }
   }
   };

       // Now get the value from user and pass it to
       // server script.
       var target_name = document.getElementById('target_name').value;
       var CCY_pair = document.getElementById('CCY_pair').value;
       var from_date = document.getElementById('from_date').value;
       var to_date = document.getElementById('to_date').value;
       var queryString = "?target_name=" + target_name + "&CCY_pair=" + CCY_pair ;
       queryString +=  "&from_date=" + from_date + "&to_date=" + to_date ;
       ajaxRequest.open("GET", "js/lineChart.php" + 
                                    queryString, true);
       ajaxRequest.send(null);
    });
});