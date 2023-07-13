<?php
function get_taxonomy_children_by_floor($floor)
{
    if (is_tax()) {
        $queried_object = get_queried_object();
        $taxonomy_id = $queried_object->term_id;
        $taxonomy_name = $queried_object->taxonomy;

        $termchildren = get_term_children($taxonomy_id, $taxonomy_name);

        if (!empty($termchildren) && !is_wp_error($termchildren)) {
            $sorted_termchildren = array();
            foreach ($termchildren as $child) {
                $term = get_term_by('id', $child, $taxonomy_name);
                if ($term instanceof WP_Term) {
                    $sorted_termchildren[$child] = array(
                        'id' => $term->term_id,
                        'name' => $term->name,
                        'slug' => $term->slug,
                        'term_group' => $term->term_group,
                        'term_taxonomy_id' => $term->term_taxonomy_id,
                        'taxonomy' => $term->taxonomy,
                        'description' => $term->description,
                        'parent' => $term->parent,
                        'count' => $term->count
                    );
                }
            }

            // Sort by name
            uasort($sorted_termchildren, function ($a, $b) {
                return strcmp($a['name'], $b['name']);
            });

            // Reset keys
            $sorted_termchildren = array_values($sorted_termchildren);

            if ($floor !== null && isset($sorted_termchildren[$floor])) {
                return $sorted_termchildren[$floor];
            } else {
                return $sorted_termchildren;
            }
        } else {
            return false;
        }
    } else {
        return false;
    }
}

function get_flats_on_floor($floor_number, $floor_id, $floor_taxonomy)
{
    if ($floor_id && $floor_taxonomy) {
        $args = array(
            'post_type' => 'mieszkanie',
            'posts_per_page' => -1,
            'tax_query' => array(
                array(
                    'taxonomy' => $floor_taxonomy,
                    'field' => 'term_id',
                    'terms' => $floor_id,
                ),
            ),
            'meta_query' => array(
                array(
                    'key' => 'pietro',
                    'value' => $floor_number,
                    'compare' => '=',
                ),
            ),
        );
        $query = new WP_Query($args);

        if ($query->have_posts()) {
            $mieszkaniaData = array();
            while ($query->have_posts()) {
                $query->the_post();

                $status = rwmb_get_value('status');
                // Pobranie metadanych
                $koordynaty_na_pietrze = rwmb_meta('koordynaty_na_rzucie_pietra');
                $nazwa = get_the_title();

                $metraz = rwmb_get_value('metraz');
                $url = get_the_permalink();
                $kolor = '';
                switch ($status) {
                    case 'Wolne':
                        $kolor = 'green';
                        break;
                    case 'Zarezerwowane':
                        $kolor = 'yellow';
                        break;
                    case 'Sprzedane':
                        $kolor = 'red';
                        break;
                    default:
                        $kolor = 'green';
                        break;
                }
                $mieszkaniaData[] = array(
                    'id' => get_the_ID(),
                    'nazwa' => $nazwa,
                    'koordynaty' => $koordynaty_na_pietrze,
                    'status' => $status,
                    'metraz' => $metraz,
                    'url' => $url,
                    'kolor' => $kolor
                );

            }
            return $mieszkaniaData;
        }
        wp_reset_postdata();
    }
}
?>