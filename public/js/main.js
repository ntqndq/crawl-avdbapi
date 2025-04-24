(function ($) {
    "use strict";

    $(function () {
        // DOM elements
        const buttonCheckApi = $("#api-check");
        const buttonRollCrawl = $("#roll-crawl");
        const buttonUpdateCrawl = $("#update-crawl");
        const buttonFullCrawl = $("#full-crawl");
        const buttonPageFromTo = $("#page-from-to");
        const buttonSelectedCrawl = $("#selected-crawl");
        const buttonPauseCrawl = $("#pause-crawl");
        const buttonResumeCrawl = $("#resume-crawl");
        const buttonOneCrawl = $("#onemovie-crawl");
        const alertBox = $("#alert-box");
        const moviesListDiv = $("#movies-list");
        const divCurrentPage = $("#current-page-crawl");
        const inputPageFrom = $("input[name=page-from]");
        const inputPageTo = $("input[name=page-to]");

        // Variable
        let latestPageList = [];
        let fullPageList = [];
        let pageFromToList = [];
        let tempPageList = [];
        let tempMoviesId = [];
        let tempMovies = [];
        let tempHour = "";
        let apiUrl = "";
        let isStopByUser = false;
        let maxPageTo = 0;

        // Disable crawl function if api url is not verify
        buttonRollCrawl.prop("disabled", true);
        buttonUpdateCrawl.prop("disabled", true);
        buttonFullCrawl.prop("disabled", true);
        buttonPageFromTo.prop("disabled", true);
        buttonSelectedCrawl.prop("disabled", true);

        // Check input api first
        buttonCheckApi.click(function (e) {
            e.preventDefault();
            apiUrl = $("#jsonapi-url").val();
            if (!apiUrl) {
                alertBox.show();
                alertBox.removeClass().addClass("alert alert-danger");
                alertBox.html("JSON API cannot leave empty");
                return false;
            }
            $("#movies-table tbody").html("");
            moviesListDiv.hide();
            $(this).html(
                `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>Loading...`
            );
            $.ajax({
                type: "POST",
                url: ajaxurl,
                data: {
                    action: "avdbapi_crawler_api",
                    api: apiUrl,
                },
                success: function (response) {
                    buttonCheckApi.html(`Check`);
                    let data = JSON.parse(response);
                    if (data.code > 1) {
                        alertBox.show();
                        alertBox.removeClass().addClass("alert alert-danger");
                        alertBox.html(data.message);
                    } else {
                        alertBox.hide();
                        buttonRollCrawl.prop("disabled", false);
                        buttonRollCrawl.html("Mix links");
                        buttonUpdateCrawl.prop("disabled", false);
                        buttonFullCrawl.prop("disabled", false);
                        buttonPageFromTo.prop("disabled", false);
                        buttonSelectedCrawl.prop("disabled", false);
                        latestPageList = data.latest_list_page;
                        fullPageList = data.full_list_page;
                        maxPageTo = data.last_page;
                        $("#movies-total").html(data.total);
                        $("#last-page").html(data.last_page);
                        $("#per-page").html(data.per_page);
                    }
                },
            });
        });

        // Crawl one movie
        buttonOneCrawl.click(function (e) {
            e.preventDefault();
            let oneLink = $("#onemovie-link").val();
            if (!oneLink) {
                alertBox.show();
                alertBox.removeClass().addClass("alert alert-danger");
                alertBox.html("Movie link can not leave empty");
                return false;
            }
            $(this).html(
                `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>Loading...`
            );
            $.ajax({
                type: "POST",
                url: ajaxurl,
                data: {
                    action: "avdbapi_crawler_link_api",
                    api: oneLink,
                },
                success: function (response) {
                    let data = JSON.parse(response);
                    if (data.code > 1) {
                        alertBox.show();
                        alertBox.removeClass().addClass("alert alert-danger");
                        alertBox.html(data.message);
                    } else {
                        alertBox.show();
                        alertBox.removeClass().addClass("alert alert-success");
                        alertBox.html(data.message);
                    }
                    buttonOneCrawl.html("Collect immediately");
                },
                error: function (xhr, ajaxOptions, thrownError) {
                    alert("Something went wrong");
                    buttonOneCrawl.html("Collect immediately");
                },
            });
        });

        // Set page from to
        buttonPageFromTo.click(function (e) {
            e.preventDefault();
            alertBox.html("");
            alertBox.hide();
            let pageFrom = parseInt(inputPageFrom.val());
            let pageTo = parseInt(inputPageTo.val());
            if (
                pageTo > maxPageTo ||
                pageFrom > pageTo ||
                pageFrom <= 0 ||
                pageTo <= 0 ||
                pageFrom == null ||
                pageTo == null
            ) {
                console.log(pageFrom, pageTo, maxPageTo);
                alertBox.show();
                alertBox.removeClass().addClass("alert alert-danger");
                alertBox.html(`Error occurs when crawl by page number.`);
                return;
            }
            let pages = [];
            for (let i = parseInt(pageFrom); i <= pageTo; i++) {
                pages.push(i);
            }
            pageFromToList = pages;
            alertBox.show();
            alertBox.removeClass().addClass("alert alert-success");
            alertBox.html(
                `Update the page number successfully: ${pageFrom} to ${pageTo}`
            );
        });

        // Crawl from pageFrom to pageTo
        buttonSelectedCrawl.click(function (e) {
            e.preventDefault();
            $("#movies-table").show();
            $(this).html(
                `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>Loading...`
            );
            crawl_movies_page(pageFromToList, "");
        });

        // Update today's movies
        buttonUpdateCrawl.click(function (e) {
            e.preventDefault();
            $("#movies-table").show();
            $(this).html(
                `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>Loading...`
            );
            crawl_movies_page(latestPageList, 24);
        });

        // Crawl full movies
        buttonFullCrawl.click(function (e) {
            e.preventDefault();
            $("#movies-table").show();
            $(this).html(
                `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>Loading...`
            );
            crawl_movies_page(fullPageList, "");
        });

        // Random crawl page
        buttonRollCrawl.click(function (e) {
            e.preventDefault();
            $(this).html(
                `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>Loading...`
            );
            fullPageList.sort((a, b) => 0.5 - Math.random());
            latestPageList.sort((a, b) => 0.5 - Math.random());
            pageFromToList.sort((a, b) => 0.5 - Math.random());
            $(this).html("Mix link ok");
        });

        // Pause crawl
        buttonPauseCrawl.click(function (e) {
            e.preventDefault();
            isStopByUser = true;
            buttonResumeCrawl.prop("disabled", false);
            buttonPauseCrawl.prop("disabled", true);
        });

        // Resume crawl
        buttonResumeCrawl.click(function (e) {
            e.preventDefault();
            isStopByUser = false;
            buttonPauseCrawl.prop("disabled", false);
            buttonResumeCrawl.prop("disabled", true);
            crawl_movie_by_id(tempMoviesId, tempMovies);
        });

        // Crawl movies page
        const crawl_movies_page = (pagesList) => {
            if (pagesList.length == 0) {
                alertBox.show();
                alertBox.removeClass().addClass("alert alert-success");
                alertBox.html("Complete the film collection!");
                moviesListDiv.hide();
                buttonRollCrawl.prop("disabled", false);
                buttonUpdateCrawl.prop("disabled", false);
                buttonFullCrawl.prop("disabled", false);
                buttonSelectedCrawl.html("Collect");
                buttonUpdateCrawl.html("Collect Today");
                buttonFullCrawl.html("Collect All");
                tempPageList = [];
                pageFromToList = [];
                tempHour = "";
                return;
            }
            let currentPage = pagesList.shift();
            tempPageList = pagesList;
            $.ajax({
                type: "POST",
                url: ajaxurl,
                data: {
                    action: "avdbapi_get_movies_page",
                    api: apiUrl,
                    param: `pg=${currentPage}`,
                },
                beforeSend: function () {
                    divCurrentPage.show();
                    $("#current-page-crawl h4").html(`Page ${currentPage}`);
                    buttonRollCrawl.prop("disabled", true);
                    buttonSelectedCrawl.prop("disabled", true);
                    buttonUpdateCrawl.prop("disabled", true);
                    buttonFullCrawl.prop("disabled", true);
                    buttonResumeCrawl.prop("disabled", true);
                    moviesListDiv.show();
                },
                success: function (response) {
                    let data = JSON.parse(response);
                    if (data.code > 1) {
                        alertBox.show();
                        alertBox.removeClass().addClass("alert alert-danger");
                        alertBox.html(data.message);
                    } else {
                        let avList = data.movies;
                        crawl_movie_by_id(avList, data.movies);
                    }
                },
            });
        };

        // Crawl movie by Id
        const crawl_movie_by_id = (avList, movies) => {
            if (isStopByUser) {
                return;
            }
            display_movies(movies);
            let av = avList.shift();
            tempMoviesId = avList;
            tempMovies = movies;
            if (av == null) {
                $("#movies-table tbody").html("");
                crawl_movies_page(tempPageList, tempHour);
                return;
            }
            $.ajax({
                type: "POST",
                url: ajaxurl,
                data: {
                    action: "avdbapi_crawl_by_id",
                    crawl_image: $("#crawlImage").is(":checked") ? 1 : 0,
                    overide_update: $("#overideUpdate").is(":checked") ? 1 : 0,
                    av: JSON.stringify(av)
                },
                success: function (response) {
                    let data = JSON.parse(response);
                    if (data.code > 1) {
                        alertBox.show();
                        alertBox.removeClass().addClass("alert alert-danger");
                        alertBox.html(data.message);
                        update_movies("movie-" + av.id, " No need to update");
                    } else {
                        alertBox.show();
                        alertBox.removeClass().addClass("alert alert-success");
                        alertBox.html(data.message);
                        update_movies("movie-" + av.id, " Success");
                    }
                    crawl_movie_by_id(avList);
                },
            });
        };

        // Display movies list
        const display_movies = (movies) => {
            let trHTML = "";
            $.each(movies, function (idx, movie) {
                trHTML += `<tr id="movie-${movie.id}">
                    <td>${idx}</td>
                    <td>${movie.name}</td>
                    <td><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...</td></tr>`;
            });
            $("#movies-table tbody").append(trHTML);
        };

        // Update movie crawling status
        const update_movies = (id, message = "100%") => {
            let doneIcon = `<svg style="stroke-with:2px;stroke:seagreen;" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="seagreen" class="bi bi-check-lg" viewBox="0 0 16 16">
                <path d="M12.736 3.97a.733.733 0 0 1 1.047 0c.286.289.29.756.01 1.05L7.88 12.01a.733.733 0 0 1-1.065.02L3.217 8.384a.757.757 0 0 1 0-1.06.733.733 0 0 1 1.047 0l3.052 3.093 5.4-6.425a.247.247 0 0 1 .02-.022Z"/>
                </svg>`;
            $("#" + id + " td:last-child").html(doneIcon + message);
        };
    });
})(jQuery);
