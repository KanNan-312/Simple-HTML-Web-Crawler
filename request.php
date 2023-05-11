<?php
  // require 'vendor/autoload.php';
  include 'simple_html_dom.php';
  require 'utils.php';

  // jpg: https://books.toscrape.com/
  // pdf: https://ocw.mit.edu/courses/1-00-introduction-to-computers-and-engineering-problem-solving-spring-2012/download/
  // png: http://localhost/BKWebProgramming/Lab/PHP-Ecommerce-Website/src/index.php

  // retrieve url and file extension from GET request
  $url = $_GET["url"];
  $ext = $_GET["ext"];
  $max_url = intval($_GET["max_url"]);

  // $url = 'http://localhost/BKWebProgramming/Lab/PHP-Ecommerce-Website/src/index.php?page=login';
  // $ext = 'png';
  // $max_url = 50;
  // global array containing the results of crawling
  $urls = [];

  // prefix of the url
  $parse = parse_url($url);
  $prefix = $parse['scheme'] . "://" . $parse["host"];

  // load the url
  $page = loadURL($url);
  if($page === false) {
    $message = "URL not found!";
    exit();
  }

  // check if the url leads to a log in page
  $login_suffixes =  array('login.php', 'login.html', '?page=login');
  $login_found = False;
  $login_pass = False;
  foreach ($login_suffixes as $suffix) {
    if (substr_compare($url, $suffix, -strlen($suffix)) === 0) {
      $login_found = True;
    }
  }

  // if url to login found, try to bypass authentication by sql injection
  if ($login_found) {
    $response = bypassAuthentication($prefix, $page);
    // bypass unsuccessful
    if($response == false) {
      $message = 'Failed to bypass authentication';
      exit();
    }
    else {
      $login_pass = True;
      $page = $response;
    }
  }

  // global curl for crawling
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

  // create httpclient and get the html page
  $html = new simple_html_dom();
  // $httpClient = new \simplehtmldom\HtmlWeb();
  $html->load($page);

  // start crawling
  $max_level = 2;
  $patience = 10;
  crawl($html, $ext, $max_level, $max_url);

  // remove duplicate urls
  $urls = array_values(array_unique($urls));
  $n_urls = count($urls);
  $message = "Found $n_urls urls with $ext extension.";
  if ($login_pass) {
    $message = "Successfully bypass the authentication system! <br>" . $message;
  }

  // function for crawling
  function crawl($html, $ext, $level, $max_url) {
    global $prefix;
    global $urls;
    global $patience;
    
    if ($level <= 0 or $max_url <= 0) {
      return array();
    }
  
    $num_urls_left = $max_url;
    // check img extension in urls and add to result
    if (in_array($ext, array("jpg", "jpeg", "png"))) {
      foreach($html -> find("img") as $img) {
        $src = $img -> src;
        if ($num_urls_left <= 0) {
          break;
        }
        else if (!empty($src) && strpos($src, $ext) !== false) {
          // in case img src contains http or https, it it hosted on another domain.
          $url = $src;
          // if (strpos($url, "https") !== false) {
          $url = ($src[0] === '/')? ($prefix . $src) : ($prefix . '/' . $src);
          // }
          // avoid adding duplicate
          if (!in_array($url, $urls)) {
            $urls[] = $url;
            $num_urls_left -= 1;
          }
        }
      }
    }
    // check audio extension in urls and add to result
    else if (in_array($ext, array("mp3"))) {
      
    }

    // check for all <a> tags
    $sub_urls = array();
    foreach($html -> find('a') as $a) {
      $href = $a -> href;
      $href = ($href[0] == '/') ? $href : ('/' . $href);
      // if maximum url reached
      if ($num_urls_left <= 0) {
        break;
      }
      // if href contains html or php, store the sub url for later retrieval
      else if ((strpos($href, ".html") !== false || strpos($href, ".php")) !== false
      && !in_array($href, array('/index.html', '/index.php'))) {
        $sub_urls[] = $prefix . $href;
      }
      
      // Look for <a> tags ending with specified extension
      else if (substr_compare($href, ".$ext", -strlen($ext)-1) === 0 && $level > 0) {
        $url = $prefix . $href;
        // avoid adding duplicate
        if (!in_array($url, $urls)) {
          $urls[] = $url;
          $num_urls_left -= 1;   
        }
      }
    }
    
    // recursively navigate to the sub urls if the alt href point to html path
    $prev_num_url_left = $num_urls_left;
    foreach ($sub_urls as $sub_url) {
      // create sub html
      $response = loadURL($sub_url);
      $sub_html = new simple_html_dom();
      $sub_html -> load($response);
      // start crawling the sub-html
      $urls_recursive = crawl($sub_html, $ext, $level-1, $num_urls_left);
      $urls = array_merge($urls, $urls_recursive);
      $num_urls_left -= count($urls_recursive);
      // if the number of unique url does not increase for a specific time, stop the crawler
      if ($num_urls_left == $prev_num_url_left) {
        $patience -= 1;
        if ($patience == 0) {
          break;
        }
      }
      if ($num_urls_left <= 0) {
        break;
      }
      $prev_num_url_left = $num_urls_left;
    }
    return $urls;
  }
  // exit();
?>


<!-- UI result -->
<div class="d-flex justify-content-center mt-2 mb-3">
  <div class="d-flex flex-column w-75">
    <div class="fst-italic">
      <?php echo $message; ?>
    </div>
    <div class="mt-1">
      <table class="table">
        <thread>
          <tr>
            <th scope="col">#</th>
            <th scope="col">URL</th>
            <th scope="col">Download</th>
          </tr>
        </thread>

        <tbody>
          <?php foreach ($urls as $idx=>$url):?>
            <tr>
              <th scope="row"> <?php echo $idx;?> </th>
              <td> <a class="url" href="<?php echo $url;?>" class="link-secondary"> <?php echo "Link {$idx}";?> </a> </td>
              <td><button class="download_button btn btn-success" onclick="download('<?php echo $url;?>')"> Download</button></td>
            </tr>
          <?php endforeach;?>
        </tbody>
      </table>
    </div>

    <div class="align-self-center d-flex justify-content-center">
      <a class="me-1" href="index.php?page=home"><button class="btn btn-danger">Back to home</button></a>
      <button class="btn btn-success ms-1" onclick="downloadAll();">Download all</button>
    </div>
  </div>
</div>
    
<!-- Javascript functions for downloading -->
<script>
  function downloadAll() {      
    let elements = document.querySelectorAll(".url")
    for (let i = 0; i < elements.length; i++) {
      let url = elements[i].getAttribute("href");
      // console.log(url);
      download(url);
    }
  }
  function download(url) {
    const splitUrl = url.split("/");
    const filename = splitUrl[splitUrl.length - 1];
    fetch(url,
      {
      headers: {
        'Access-Control-Allow-Origin':'*'
        },

      })
      .then((response) => {
        response.arrayBuffer().then(function (buffer) {
          const url = window.URL.createObjectURL(new Blob([buffer]));
          const link = document.createElement("a");
          link.href = url;
          link.setAttribute("download", filename); //or any other extension
          document.body.appendChild(link);
          link.click();
          document.body.removeChild(link);
        });
      })
      .catch((err) => {
        console.log(err);
      });
  }
</script>