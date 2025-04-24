<?php

/**
 * The public-facing functionality of the plugin.
 * 
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 */
class Nguon_avdbapi_crawler
{
    private $plugin_name;
    private $version;

    private $CRAWL_IMAGE = 1;
    private $OVERIDE_UPDATE = 0;

    /**
     * Initialize the class and set its properties.
     *
     * @param      string    $plugin_name       The name of the plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     */
    public function enqueue_scripts()
    {
        wp_enqueue_script($this->plugin_name . 'mainjs', plugin_dir_url(__FILE__) . 'js/main.js', array('jquery'), $this->version, false);
        wp_enqueue_script($this->plugin_name . 'bootstrapjs', plugin_dir_url(__FILE__) . 'js/bootstrap.bundle.min.js', array(), $this->version, false);
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     */
    public function enqueue_styles()
    {
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/nguonc.css', array(), $this->version, 'all');
    }

    /**
     * Make CURL
     *
     * @param  string      $url       Url string
     * @return string|bool $response  Response
     */
    private function curl($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }

    /**
     * wp_ajax_avdbapi_crawler_api action Callback function
     *
     * @param  string $api url
     * @return json $page_array
     */
    public function avdbapi_crawler_api()
    {
        $url = $_POST['api'];
        $url = strpos($url, '?') === false ? $url .= '?' : $url .= '&';
        $latest_url = $url . http_build_query(['pg' => 1]);

        $latest_response = $this->curl($latest_url);

        $latest_data = json_decode($latest_response);

        $page_array = array(
            'code' => 1,
            'last_page' => $latest_data->pagecount,
            'per_page' => $latest_data->limit,
            'total' => $latest_data->pagecount,
            'full_list_page' => range(1, $latest_data->pagecount),
            'latest_list_page' => range(1, $latest_data->pagecount),
        );
        echo json_encode($page_array);

        wp_die();
    }

    /**
     * wp_ajax_avdbapi_get_movies_page action Callback function
     *
     * @param  string $api        url
     * @param  string $param      query params
     * @return json   $page_array List movies in page
     */
    public function avdbapi_get_movies_page()
    {
        try {
            $url = $_POST['api'];
            $params = $_POST['param'];
            $url = strpos($url, '?') === false ? $url .= '?' : $url .= '&';
            $response = $this->curl($url . $params);

            $data = json_decode($response);
            if (!$data) {
                echo json_encode(['code' => 999, 'message' => 'The JSON model is not right, does not support collection']);
                die();
            }
            $page_array = array(
                'code' => 1,
                'movies' => $data->list,
            );
            echo json_encode($page_array);

            wp_die();
        } catch (\Throwable $th) {
            //throw $th;
            echo json_encode(['code' => 999, 'message' => $th]);
            wp_die();
        }
    }

    /**
     * wp_ajax_avdbapi_crawl_by_id action Callback function
     *
     * @param  string $api        url
     * @param  string $param      movie id
     */
    public function avdbapi_crawl_by_id()
    {
        try {
            wp_cache_flush();
            $av = $_POST['av'];
            $this->CRAWL_IMAGE = $_POST['crawl_image'];
            $this->OVERIDE_UPDATE = $_POST['overide_update'];

            $data = json_decode(str_replace('\"', '"', $av), true);
            if (!$data || !isset($data['id']) || !isset($data['type_name'])) {
                echo json_encode(['code' => 999, 'message' => 'The JSON model is not right, does not support collection', 'data' => $data, 'av' => $_POST['av']]);
                die();
            }
            $movie_data = $data;

            $args = array(
                'name' => $data["slug"],
                'post_type' => 'post',
                'post_status' => 'publish',
                'posts_per_page' => 1
            );
            $my_posts = get_posts($args);

            if ($my_posts) { // Coincide the movie name
                $post_id = $my_posts[0]->ID; // Get the ID of the first post found
                if(!$this->OVERIDE_UPDATE){
                    $result = array(
                        'code' => 999,
                        'message' => $movie_data['slug'] . ' : No need to update',
                        'data' => $movie_data
                    );
                    echo json_encode($result);
                    wp_die();
                } else {
                    // Delete the post
                    wp_delete_post($post_id, true); // Use true to force delete (bypass trash)
                }
            }

            foreach ($movie_data["episodes"]["server_data"] as $key => $val) {
                $this->insert_movie($movie_data, $key, $val);
            }

            $result = array(
                'code' => 1,
                'message' => $movie_data['slug'] . ' : Successful collection.',
                'data' => $movie_data,
            );

            echo json_encode($result);
            wp_die();
        } catch (\Throwable $th) {
            echo json_encode([
                'code' => 999,
                'message' => $th->getMessage(),
                'data' => $movie_data
            ]);
            wp_die();
        }

    }

    /**
     * Insert movie to WP posts, save images
     *
     * @param  array  $data   movie data
     */
    private function insert_movie($data, $name, $episode)
    {
        $addition_title = "";
        $addition_slug = "";
        if ($name != "Full"){
            $addition_title = " - EP " . $name;
            $addition_slug = "-" . $name;
        }
        $args = array(
            'name' => $data['slug'].$addition_slug,
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => 1
        );
        $my_posts = get_posts($args);
        if($my_posts){
            $post_id = $my_posts[0]->ID; // Get the ID of the first post found
            if(!$this->OVERIDE_UPDATE){
                return $my_posts[0]->ID;
            } else {
                // Delete the post
                wp_delete_post($post_id, true); // Use true to force delete (bypass trash)
            }
        }

        $post_data = array(
            'post_title' => $data['name'] . $addition_title,
            'post_content' => $data['description'],
            'post_name' => $data['slug'].$addition_slug,
            'post_status' => 'publish',
            'comment_status' => 'open',
            'ping_status' => 'open',
            'post_author' => get_current_user_id(),
            'post_type' => "post",
        );
        $post_id = wp_insert_post($post_data);

        // Set the post format to video
        set_post_format($post_id, 'video');

        //wp_set_object_terms($post_id, $data['status'], 'status', false);

        // Insert Category
        if ($data['category']) {
            wp_set_object_terms($post_id, $data['category'], 'category', false);
        }

        $categories_id = [];

        if (!category_exists($data['type_name']) && $data['type_name'] !== '') {
            wp_create_category($data['type_name']);
        }
        $categories_id[] = get_cat_ID($data['type_name']);

        foreach ($data['category'] as $category) {
            if (!category_exists($category) && $category !== '') {
                wp_create_category($category);
            }
            $categories_id[] = get_cat_ID($category);
        }
        
        wp_set_post_categories($post_id, $categories_id);
        foreach ($data['actor'] as $actor) {
            if (!term_exists($actor) && $actor != '') {
                wp_insert_term($actor, 'actors');
            }
            wp_set_post_terms($post_id, $actor, 'actors', true);
        }

        if (!term_exists($data['tag']) && $data['tag'] != '') {
            wp_insert_term($data['tag'], 'post_tag');
        }
        wp_set_post_terms($post_id, $data['tag']);

        wp_set_post_terms($post_id, "post-format-video", "post-format-video", true);

        // Explode the time string into hours, minutes, and seconds
        list($hours, $minutes, $seconds) = explode(':', $data['time']);

        // Calculate the total duration in seconds
        $duration = ($hours * 3600) + ($minutes * 60) + $seconds;

        if (isset($data['poster_url']) && $this->CRAWL_IMAGE != 0) {
            $results = $this->save_images($data['poster_url']);
            if ($results !== false) {
                $attachment = array(
                    'guid' => $results['url'],
                    'post_mime_type' => $results['type'],
                    'post_title' => preg_replace('/\.[^.]+$/', '', basename($results['file'])),
                    'post_content' => '',
                    'post_status' => 'inherit'
                );
                $attach_id = wp_insert_attachment($attachment, $results['file'], $post_id);

                set_post_thumbnail($post_id, $attach_id);
                $data['poster_url'] = $results['url'];
            }
            $post_meta_movies = array(
                'featured_video' => 'off',
                'hd_video' => 'on',
                'embed' => '<iframe src="' . $episode['link_embed'] . '" frameborder="0" scrolling="no" width="960" height="720" allowfullscreen></iframe>',
                'duration' => $duration,
                'thumb_id' => $data['poster_url'],
            );
        } else {
            
        $post_meta_movies = array(
            'featured_video' => 'off',
            'hd_video' => 'on',
            'embed' => '<iframe src="' . $episode['link_embed'] . '" frameborder="0" scrolling="no" width="960" height="720" allowfullscreen></iframe>',
            'duration' => $duration,
            'thumb' => $data['poster_url'],
        );
        }


        if (isset($post_meta_movies)) {
            foreach ($post_meta_movies as $key => $value) {
                $new_meta_value = (isset($value) ? ($value) : '');
                $meta_value = is_array(get_post_meta($post_id, $key, true)) ? array_map('stripslashes', get_post_meta($post_id, $key, true)) : stripslashes(get_post_meta($post_id, $key, true));

                if ($new_meta_value && '' == $meta_value) {
                    add_post_meta($post_id, $key, $new_meta_value, true);
                } elseif ($new_meta_value && $new_meta_value != $meta_value) {
                    update_post_meta($post_id, $key, $new_meta_value);
                } elseif ('' == $new_meta_value && $meta_value) {
                    delete_post_meta($post_id, $key, $meta_value);
                }
            }
        }


        return $post_id;
    }

    /**
     * Save movie thumbail to WP
     *
     * @param  string   $image_url   thumbail url
     */
    public function save_images($image_url)
    {
        require_once (ABSPATH . "wp-admin/includes/file.php");

        $temp_file = download_url($image_url, 300);
        if (!is_wp_error($temp_file)) {

            $mime_extensions = array(
                'jpg' => 'image/jpg',
                'jpeg' => 'image/jpeg',
                'gif' => 'image/gif',
                'png' => 'image/png',
                'webp' => 'image/webp',
            );

            // Array based on $_FILE as seen in PHP file uploads.
            $file = array(
                'name' => basename($image_url), // ex: wp-header-logo.png
                'type' => $mime_extensions[pathinfo($image_url, PATHINFO_EXTENSION)],
                'tmp_name' => $temp_file,
                'error' => 0,
                'size' => filesize($temp_file),
            );

            $overrides = array(
                'test_form' => false,
                'test_size' => true,
            );

            // Move the temporary file into the uploads directory.
            $results = wp_handle_sideload($file, $overrides);
            unlink($temp_file);

            if (!empty($results['error'])) {
                return false;
            } else {
                return $results;
            }
        }
    }

    /**
     * Uppercase the first character of each word in a string
     *
     * @param  string   $string     format string
     * @param  array    $arr        string array
     */
    private function format_text($string)
    {
        $string = str_replace(array('/', '，', '|', '、', ',,,'), ',', $string);
        $arr = explode(',', sanitize_text_field($string));
        foreach ($arr as &$item) {
            $item = ucwords(trim($item));
        }
        return $arr;
    }

    /**
     * Filter html tags in api response
     *
     * @param  string   $rs     response
     * @param  array    $rs     response
     */
    private function filter_tags($rs)
    {
        $rex = array('{:', '<script', '<iframe', '<frameset', '<object', 'onerror');
        if (is_array($rs)) {
            foreach ($rs as $k2 => $v2) {
                if (!is_numeric($v2)) {
                    $rs[$k2] = str_ireplace($rex, '*', $rs[$k2]);
                }
            }
        } else {
            if (!is_numeric($rs)) {
                $rs = str_ireplace($rex, '*', $rs);
            }
        }
        return $rs;
    }
}
