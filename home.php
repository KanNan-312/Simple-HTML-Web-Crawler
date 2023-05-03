
<div class="d-flex justify-content-center mt-3">
  <form class="d-flex flex-column justify-content-center" method="GET" action="./index.php">
    <input type="hidden" value="request" name="page">
    <div class="mb-3">
      <label for="url" class="form-label">Enter website url</label>
      <input type="text" class="form-control" id="url" name="url">
    </div>
    <div class="mb-3">
      <label for="ext" class="form-label">Specify the content extension you want</label>
      <select name="ext" id="ext" class="form-select">
        <option selected value="jpg">jpg</option>
        <option value="png">png</option>
        <option value="mp3">mp3</option>
        <option value="pdf">pdf</option>
      </select>
    </div>
    <div class="mb-3">
      <label for="max_url" class="form-label">Maximum numbers of urls to crawl</label>
      <select name="max_url" id="max_url" class="form-select">
        <option selected value="10">10</option>
        <option value="20">20</option>
        <option value="50">50</option>
        <option value="100">100</option>
      </select>
    </div>
    <button type="submit" class="btn btn-primary">Start crawling</button>
  </form>
</div>