<?php 

// Register shortcode - show title of inwestycja for specific budynek
add_shortcode('investment-title-of-current-building', function () {
    // Pobierz ID aktualnego obiektu zapytania, co powinno działać, jeśli jesteś na stronie terminu taksonomii
    $term_id = get_queried_object_id();
    // WP_Query "inwestycja" ma taxonomię'budynek' taką jak ta na której stronie sie znajdujemy 
    $args = array(
        'post_type' => 'inwestycja',
        'tax_query' => array(
            array(
                'taxonomy' => 'budynek',
                'field' => 'term_id',
                'terms' => $term_id,
            ),
        ),
    );

    $inwestycja = new WP_Query($args);
    $inwestycja_title = $inwestycja->posts[0]->post_title;
    wp_reset_query();
    // Zróć link do inwestycji
    return '<a class="investment-title" href="' . get_permalink($inwestycja->posts[0]->ID) . '">' . $inwestycja_title . '</a>';

});