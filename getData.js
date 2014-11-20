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
            var ajaxDisplay = document.getElementById('rate_container');
            ajaxDisplay.innerHTML = ajaxRequest.responseText;
         }
       }
       // Now get the value from user and pass it to
       // server script.
       var target_name = document.getElementById('target_name').value;
       var source_name = document.getElementById('source_name').value;
       var chosen_date = document.getElementById('chosen_date').value;
       var view_as = document.getElementsByClassName('view_as');

       for (var i = 0; i < view_as.length; i++) {
          if (view_as[i].type === "radio" && view_as[i].checked) {
            view_as = view_as[i].value;
            break;
          };
       }
       var queryString = "?target_name=" + target_name ;
       queryString +=  "&source_name=" + source_name ;
       queryString +=  "&chosen_date=" + chosen_date ;
       queryString +=  "&view_as=" + view_as ;
       ajaxRequest.open("GET", "js/getData.php" + 
                                    queryString, true);
       ajaxRequest.send(null);
    });
});