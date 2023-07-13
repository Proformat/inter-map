<?php

add_shortcode('title-of-current-building', function () {
    $term = get_term(get_queried_object_id());

    // Pobierz parametr GET 'pietro' z adresu URL
    $pietro = $_GET['pietro'];

    // Jeśli parametr GET 'pietro' istnieje
    if ($pietro) {
        // Zwróć link do budynku jako h1 i a jednocześnie
        return '<a class="building-title" href="' . get_term_link($term) . '"><h1>' . $term->name . '</h1></a>';
    } else {
        // Zwróć link do budynku jako h1 i a jednocześnie
        return '<h1 class="building-title">' . $term->name . '</h1>';
    }
});