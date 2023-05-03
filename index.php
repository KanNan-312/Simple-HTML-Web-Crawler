<html lang="en">
  <?php include "header.php";?>
<body>
  <div class="mx-3 my-2">
    <div class="d-flex justify-content-center">
      <h1 class="fs-3">PHP Simple Web Crawler</h1>
    </div>
    <?php
      if(!isset($_GET["page"])) {
        include "home.php";
      }
      else {
        $page = $_GET["page"];
        include $page . ".php";
      }
    ?>
  </div>
</body>
</html>
