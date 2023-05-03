<?php
  require 'vendor/autoload.php';

  // jpg: https://books.toscrape.com/
  // pdf: https://ocw.mit.edu/courses/1-00-introduction-to-computers-and-engineering-problem-solving-spring-2012/download/

  // retrieve url and file extension from GET request
  $url = $_GET["url"];
  $ext = $_GET["ext"];
  $max_url = intval($_GET["max_url"]);

  // $url = 'https://books.toscrape.com/';
  // $ext = 'jpg';
  // $max_url = 50;

  // prefix of the url
  $parse = parse_url($url);
  $prefix = $parse['scheme'] . "://" . $parse["host"];

  // create httpclient and get the html page
  $httpClient = new \simplehtmldom\HtmlWeb();
  $html = $httpClient->load($url);

  // global variables for crawling
  $urls = [];
  $max_level = 2;
  $patience = 10;

  // start crawling
  crawl($html, $ext, $max_level, $max_url);

  // remove duplicate urls
  $urls = array_unique($urls);

  function crawl($html, $ext, $level, $max_url) {
    global $httpClient;
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
          // avoid adding duplicate
          $url = ($src[0] === '/')? ($prefix . $src) : ($prefix . '/' . $src);
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
      // if href contains html, store the sub url for later retrieval
      else if (substr_compare($href, ".html", -5) === 0 and $href != '/index.html') {
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
      $sub_html = $httpClient -> load($sub_url);
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
?>

<!-- Return UI result -->
<div class="d-flex justify-content-center mt-2 mb-3">
  <div class="d-flex flex-column w-75">
    <div class="fst-italic">
      Found <?php echo count($urls); ?> urls with <?php echo $ext; ?> extension.
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
              <td class="url"> <a href="<?php echo $url;?>" class="link-secondary"> <?php echo "Link {$idx}";?> </a> </td>
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

<script type='text/javascript'>
  function download($url) {
    const splitUrl = $url.split("/");
    const filename = splitUrl[splitUrl.length - 1];
    $.post("index.php?page=download", {url: $url}) //prepare and execute post
        .done(function(response) { //Once we receive response from PHP script
            //Do something with the response:
            console.log(response)

            // create blob
              const url = window.URL.createObjectURL(new Blob([response]));
              const link = document.createElement("a");
              link.href = url;
              link.setAttribute("download", filename); //or any other extension
              document.body.appendChild(link);
              link.click();
              document.body.removeChild(link);


          });
  }
</script>


<script>
  function downloadAll() {      
    let elements = document.querySelectorAll(".url")
    for (let i = 0; i < elements.length; i++) {
      let url = elements[i].innerText;
      console.log(url)
      downloadImage(url);
    }
  }
  function downloadImage(url) {
    const splitUrl = url.split("/");
    const filename = splitUrl[splitUrl.length - 1];
    fetch(url,
      {mode : 'no-cors',
      // headers: {'Access-Control-Allow-Origin':'*'}
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


<!-- Problem left -->
<!-- Javascript case: like in nhaccuatui.com -> maybe skip this case -->
<!-- 2-level href: need to click on the link and navigate to that html to continue parsing -> use recursion and set the link click level --> 
