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
          var data = [0];
          var row = [0];
         if(ajaxRequest.readyState == 4){
            js_obj_data = ajaxRequest.responseText;
            if(js_obj_data){
              var js_obj_data = JSON.parse(ajaxRequest.responseText);
              //prepare data
              var pp = js_obj_data.length;
               
              var index = 0; per = 0; count = 0;
              for(var i = 0; i < 4; i++) {
                index = Math.round(pp/4 * (i+1) - 1);
                num = js_obj_data[index];
                per = Math.round((js_obj_data.slice(0,index+1).length - count )*100 / pp,4);
                count = js_obj_data.slice(0,index+1).length;
                data.push(per);
                row.push(num);
              }


            var buyerData = {
              labels : row,
              datasets : [
                {
                  fillColor : "rgba(172,194,132,0.4)",
                  strokeColor : "#ACC26D",
                  pointColor : "#fff",
                  pointStrokeColor : "#9DB86D",
                  data : data
                }
              ]
            };
       var buyers = document.getElementById('buyers').getContext('2d');
       myLineChart = new Chart(buyers).Line(buyerData);
     } else {
        var w = $('#buyers').width();
        var h = $('#buyers').height();
       $('#buyers').remove(); // this is my <canvas> element
       $('#graph-containers').append('<canvas id="buyers"></canvas>');
       
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
       ajaxRequest.open("GET", "js/getData3.php" + 
                                    queryString, true);
       ajaxRequest.send(null);
    });
});