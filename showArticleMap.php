<?php
/*
Plugin Name: Show Article Map
Plugin URI: https://www.naenote.net/entry/show-article-map
Description: Visualize internal link between posts
Author: NAE
Version: 0.9
Author URI: https://www.naenote.net/entry/show-article-map
License: GPL2
*/

/*  Copyright 2017 NAE (email : @naenotenet)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
     published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if (!function_exists('nae_insert_str')):
function nae_insert_str($text, $insert, $num)
{
    $returnText = $text;
    $text_len = mb_strlen($text, 'utf-8');
    $insert_len = mb_strlen($insert, 'utf-8');
    for ($i = 0; ($i + 1) * $num < $text_len; ++$i) {
        $current_num = $num + $i * ($insert_len + $num);
        $returnText = preg_replace("/^.{0,$current_num}+\K/us", $insert, $returnText);
    }

    return $returnText;
}
endif;

if (!function_exists('nae_get_dataset')):
function nae_get_dataset()
{
    $args_post = [
        'posts_per_page' => -1,
        'post_type' => 'post',
        'post_status' => 'publish',
    ];
    $args_page = [
        'posts_per_page' => -1,
        'post_type' => 'page',
        'post_status' => 'publish',
    ];
    $post_array = get_posts($args_post);
    $page_array = get_pages($args_page);
    $articles = array_merge($post_array, $page_array);
    $nodes = [];
    $edges = [];

    foreach ($articles as $post) {
        $category = get_the_category($post->ID);
        $ancestors_cat_IDs = get_ancestors($category[0]->cat_ID, 'category');
        if (empty($ancestors_cat_IDs)) {
            $root_category_ID = $category[0]->cat_ID;
        } else {
            $root_category_ID = array_pop($ancestors_cat_IDs);
        }
        $root_category = get_category($root_category_ID);
        $group_name = !empty($category) ? $root_category->slug : '固定ページ';

        $nodes[] = [
            'id' => $post->ID,
            'label' => nae_insert_str(urldecode($post->ID.':'.$post->post_name), "\n", 20),
            'group' => urldecode($group_name),
            'title' => '<a href="'.get_permalink($post).'" target="_blank">'.$post->post_title.'</a>',
        ];
        $post_content = str_replace('[show_article_map]', '', $post->post_content);
        $html = apply_filters('the_content', $post_content);

        $dom = new DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new DOMXPath($dom);

        $query = "//a[
            @href != ''
            and not(starts-with(@href, '#'))
            and normalize-space() != ''
        ]";

        foreach ($xpath->query($query) as $node) {
            $href = $xpath->evaluate('string(@href)', $node);
            $linked_post_id = url_to_postid($href);
            if (0 != $linked_post_id && !in_array(['from' => $post->ID, 'to' => $linked_post_id], $edges)) {
                $edges[] = [
                    'from' => $post->ID,
                    'to' => $linked_post_id,
                ];
            }
        }
    }

    return '['.json_encode($nodes).','.json_encode($edges).']';
}
endif;

if (!function_exists('nae_render_article_map')):
function nae_render_article_map($attributes, $content)
{
    $dataset = nae_get_dataset();
    $dataset = htmlspecialchars($dataset);
    $js_path = plugins_url('showArticleMap.js', __FILE__);

    wp_enqueue_script('vis-js', 'https://cdnjs.cloudflare.com/ajax/libs/vis/4.20.0/vis.min.js', [], '4.20.0', true);
    wp_enqueue_style('vis-css', 'https://cdnjs.cloudflare.com/ajax/libs/vis/4.20.0/vis.min.css', [], '4.20.0');
    wp_enqueue_script('show-article-map-front', $js_path, ['jquery', 'vis-js'], '0.9', true);

    ob_start();
    ?>
    <div>
        <div id="manipulationspace">
            <div>
                <label for="searchnodequery">Search by node name : </label>
                <input id="searchnodequery" name="searchnodequery" size="30" style="display:inline;width:50% !important;" type="text">
                <button id="searchnodebutton" type="submit">Search</button>
            </div>
            <div>
                <label for="groupList">Toggle category / pages : </label>
                <span id="groupList"></span>
            </div>
            <div>
                <label for="toggleBlur">Toggle Blur : </label>
                <button id="togglepBlur" type="submit">Stop</button>
            </div>
        </div>
        <div id="mynetwork" style="width: 100%; height: 800px; border: 1px solid lightgray;"></div>
        <div><a id='downloadCSV' href='#' download="ShowArticleMap.csv">Download CSV</a></div>
        <div id="show-article-map-dataset" style="display:none;"><?php echo $dataset; ?></div>
    </div>
    <?php
    return ob_get_clean();
}
endif;

if (!function_exists('nae_register_block')):
function nae_register_block()
{
    register_block_type(
        __DIR__ . '/block.json',
        ['render_callback' => 'nae_render_article_map']
    );
}
endif;
add_action('init', 'nae_register_block');

add_shortcode('show_article_map', function () {
    return nae_render_article_map([], '');
});
