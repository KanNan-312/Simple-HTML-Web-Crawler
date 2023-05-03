<?php
  $url = $_POST["url"];

  
  $response = file_get_contents($url);
  
  //** For purposes of this demo, we will manually assume the JSON response from the API:
  echo $response;

?>