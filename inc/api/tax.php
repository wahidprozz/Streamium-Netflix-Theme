<?php

/**
 * Resume video time ajax
 *
 * @return bool
 * @author  @sameast
 */
function tax_api_post() {

    // Run a nonce check
    if ( !wp_verify_nonce( $_REQUEST['nonce'], "tax_api_nonce")) {

        echo json_encode(
            array(
                'error' => true,
                'message' => 'No naughty business please' 
            )
        );     
        die(); 

    }

    // Get params
    $userId = get_current_user_id();
    $tax = isset($_REQUEST['query']['taxonomy']) ? $_REQUEST['query']['taxonomy'] : "";
    $term_id = isset($_REQUEST['query']['term_id']) ? $_REQUEST['query']['term_id'] : "";

    switch (isset($_REQUEST['search']) ? $_REQUEST['search'] : 'all') {
        case 'reviewed':

            remove_all_filters('posts_fields');
            remove_all_filters('posts_join');
            remove_all_filters('posts_groupby');
            remove_all_filters('posts_orderby');
            add_filter( 'posts_fields', 'streamium_search_distinct' );
            add_filter( 'posts_join','streamium_search_join');
            add_filter( 'posts_groupby', 'streamium_search_groupby' );
            add_filter( 'posts_orderby', 'streamium_search_orderby' );
            $loop = new WP_Query( 
                array(
                    'posts_per_page'   => -1,
                    //'ignore_sticky_posts' => true,
                    'tax_query' => array(
                        array(
                            'taxonomy'  => $tax,
                            'field'     => 'term_id',
                            'terms'     => $term_id,
                        )
                    ),
                    'orderby' => 'date',
                    'order'   => 'DESC', 
                ) 
            );

            break;

        case 'newest':
            
            remove_all_filters('posts_fields');
            remove_all_filters('posts_join');
            remove_all_filters('posts_groupby');
            remove_all_filters('posts_orderby');
           
            $loop = new WP_Query( 
                array(
                    'posts_per_page'   => -1,
                    //'ignore_sticky_posts' => true,
                    'tax_query' => array(
                        array(
                            'taxonomy'  => $tax,
                            'field'     => 'term_id',
                            'terms'     => $term_id,
                        )
                    ),
                    'orderby' => 'date',
                    'order'   => 'DESC', 
                ) 
            );
        
            break;

        case 'oldest':

            remove_all_filters('posts_fields');
            remove_all_filters('posts_join');
            remove_all_filters('posts_groupby');
            remove_all_filters('posts_orderby');
            
            $loop = new WP_Query( 
                array(
                    'posts_per_page'   => -1,
                    //'ignore_sticky_posts' => true,
                    'tax_query' => array(
                        array(
                            'taxonomy'  => $tax,
                            'field'     => 'term_id',
                            'terms'     => $term_id,
                        )
                    ),
                    'orderby' => 'date',
                    'order'   => 'ASC', 
                ) 
            );
        
            break;
        
        default:

            $loop = new WP_Query( 
                array(
                    'posts_per_page'   => -1,
                    //'ignore_sticky_posts' => true,
                    'tax_query' => array(
                        array(
                            'taxonomy'  => $tax,
                            'field'     => 'term_id',
                            'terms'     => $term_id,
                        )
                    )
                ) 
            );

            break;
    }

    // Setup empty array
    $data = [];

    if ( $loop->have_posts() ) : 

        $count = 0;
        $cat_count = 0; 
        $total_count = $loop->post_count;

        while ( $loop->have_posts() ) : $loop->the_post(); 

            $image  = wp_get_attachment_image_src( get_post_thumbnail_id(), 'streamium-video-tile' );
            $imageExpanded   = wp_get_attachment_image_src( get_post_thumbnail_id(), 'streamium-video-tile-expanded' );
            $nonce = wp_create_nonce( 'streamium_likes_nonce' );
            $trimexcerpt = !empty(get_the_excerpt()) ? get_the_excerpt() : get_the_content();

            $paidTileText = false;
            if($loop->post->premium){
                $paidTileText = str_replace(array("_"), " ", $loop->post->plans[0]);
            }
            if (function_exists('is_protected_by_s2member')) {
                $check = is_post_protected_by_s2member(get_the_ID());
                if($check) { 
                    $ccaps = get_post_meta(get_the_ID(), 's2member_ccaps_req', true);
                    if(!empty($ccaps)){
                        $paidTileText = implode(",", $ccaps);
                    }else{
                        $paidTileText = implode(",", $check);
                    }
                }
            }

            $progressBar = false;
            if(get_theme_mod( 'streamium_enable_premium' )) {
                $progressBar = get_post_meta( get_the_ID(), 'user_' . $userId, true );
            }
            $data[] = array(
                'id' => get_the_ID(),
                'post' => $loop->post,
                'tileUrl' => esc_url($image[0]),
                'tileUrlExpanded' => esc_url($imageExpanded[0]),
                'link' => get_the_permalink(),
                'title' => get_the_title(),
                'text' => wp_trim_words($trimexcerpt, $num_words = 18, $more = '...'),
                'paidTileText' => $paidTileText,
                'progressBar' => (int)$progressBar,
                'nonce' => $nonce
            );

        endwhile;
    endif;
    wp_reset_query();

    echo json_encode(
        array(
            'error' => false,
            'data' => $data,
            'count' => (int)$loop->post_count,
            'message' => 'User not logged in' 
        )
    );     

    die(); 

}

add_action( "wp_ajax_tax_api_post", "tax_api_post" );
add_action( "wp_ajax_nopriv_tax_api_post", "tax_api_post" );