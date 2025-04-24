<?php
foreach (get_plugins() as $key => $value) {
    if ($value['Name'] == 'AVDBAPI Crawler') {
        $thisversion = $value['Version'];
    }
}

$plugin_path = plugin_dir_url(__DIR__);
?>

<div class="container-lg mt-4">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col">
                <div class="card-md">
                    <div class="card-header text-center h4">AVDBAPI Crawler
                        <?php echo $thisversion ?>
                    </div>
                    <div class="card-body">
                        <div class="input-group mb-3">
                            <input type="hidden" id="plugin_path" name="plugin_path" value="<?php echo $plugin_path ?>">
                        </div>
                        <div class="input-group">
                            <span class="input-group-text">Enter Json API</span>
                            <input type="text" class="form-control" id="jsonapi-url"
                                value="https://avdbapi.com/api.php/provide/vod/?ac=detail"
                                placeholder="https://avdbapi.com/api.php/provide/vod/?ac=detail">
                            <button class="btn btn-primary" type="button" id="api-check">Check</button>
                        </div>
                        <div id="alert-box" class="alert" style="display: none;" role="alert"></div>
                    </div>
                    <div id="content" class="card-body">
                        <div class="input-group mb-3">
                            <span class="input-group-text">Collect from page</span>
                            <input type="number" class="form-control" name="page-from" placeholder="Number of pages">
                            <span class="input-group-text">To the page</span>
                            <input type="number" class="form-control" name="page-to" placeholder="Number of pages">
                            <button class="btn btn-primary" type="button" id="page-from-to">Check</button>
                        </div>
                        <div class="card-title">Movie source information: </div>
                        <ul id="server-info" class="list-group">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Total number of pages
                                <span id="last-page" class="badge bg-primary rounded-pill"></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Number of movies each page
                                <span id="per-page" class="badge bg-primary rounded-pill"></span>
                            </li>
                        </ul>

                    </div>
                    <div id="movies-list" class="card-body" style="display: none;">
                        <div class="card-title" id="current-page-crawl">
                            <h4 id="h4-current-page" class="position-absolute">Page 1</h4>
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end me-5">
                                <button id="pause-crawl" type="button" class="btn btn-warning">Stop</button>
                                <button id="resume-crawl" type="button" class="btn btn-warning">Continue</button>
                            </div>
                        </div>
                        <table class="table" id="movies-table">
                            <thead>
                                <tr>
                                    <th scope="col">ID</th>
                                    <th scope="col">Movie's name</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    <div class="card-footer">
                        <button id="roll-crawl" type="button" class="btn btn-success position-absolute">Mix
                            Link</button>
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <div class="form-check" style="padding-top: 10px;">
                                <input class="form-check-input" type="checkbox" value="" id="crawlImage"
                                    style="margin: unset; margin-left: -1.5em;" checked>
                                <label class="form-check-label" for="crawlImage" style="vertical-align: unset;">
                                    Crawl image
                                </label>
                            </div>
                            <div class="form-check" style="padding-top: 10px;">
                                <input class="form-check-input" type="checkbox" value="" id="overideUpdate"
                                    style="margin: unset; margin-left: -1.5em;">
                                <label class="form-check-label" for="crawlImage" style="vertical-align: unset;">
                                    Overide Update
                                </label>
                            </div>
                            <button id="selected-crawl" type="button" class="btn btn-warning">Collect</button>
                            <button id="update-crawl" type="button" class="btn btn-warning">Collect Today</button>
                            <button id="full-crawl" type="button" class="btn btn-primary">Collect All</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>