<?php

// Register shortcode
add_shortcode('new-map', function ($atts) {
    // Pobierz atrybuty
    $a = shortcode_atts(
        array(
            'elements' => '',
            // floors, apartments
            'post_type' => '',
            // eg. inwestycja
            'taxonomy' => '',
            // eg. budynek
            'views' => '1',
        ),
        $atts
    );

    $elements = sanitize_text_field($a['elements']);
    $post_type = sanitize_text_field($a['post_type']);
    $taxonomy = sanitize_text_field($a['taxonomy']);
    $views = sanitize_text_field($a['views']);

    if ($post_type == 'inwestycja') {

        $inwestycja_id = get_the_ID(); // ID inwestycji
        $args = array(
            'parent' => 0, // Wybieramy tylko elementy najwyższego poziomu (bez rodzica)
        );

        // Pobieranie wszystkich terminów 'budynek' najwyższego poziomu przypisanych do posta 'inwestycja'
        $budynki = wp_get_post_terms($inwestycja_id, 'budynek', $args);

        // 1. Sprawdzenie, czy pobrano przynajmniej jeden budynek
        if (!empty($budynki) && !is_wp_error($budynki)) {
            // 2. Tworzenie pustej tablicy do przechowywania danych pięter
            $elementsData = array();

            // Inicjalizuj pustą tablicę elementsData
            $elementsData = [];

            foreach ($budynki as $budynek) {
                // Pobranie pięter dla danego budynku
                $pietra_terms = get_terms([
                    'taxonomy' => 'budynek',
                    // Załóżmy, że 'pietro' to twoja taksonomia dla pięter
                    'parent' => $budynek->term_id,
                    'hide_empty' => false,
                ]);

                if ($elements == 'floors') {
                    foreach ($pietra_terms as $pietro) {
                        $nazwa_pietra = $pietro->name;
                        $url_widoku_taxonomii_pietra = get_term_link($pietro);

                        // Dodanie danych o piętrach do tablicy
                        $elementsData[] = array(
                            'koordynaty' => rwmb_meta('koordynaty_na_budynku', ['object_type' => 'term'], $pietro->term_id),
                            'url' => $url_widoku_taxonomii_pietra,
                            'kolor' => 'green',
                            'params' => array(
                                'nazwa_pietra' => $nazwa_pietra,
                            ),
                        );
                    }
                } else if ($elements == 'apartments') {

                } else if ($elements == 'buildings') {
                    // Pobieranie danych dla każdego budynku
                    $koordynaty = rwmb_meta('coordinates', ['object_type' => 'term'], $budynek->term_id);
                    $status = rwmb_meta('status', ['object_type' => 'term'], $budynek->term_id);
                    $url_widoku_taxonomii = get_term_link($budynek);
                    $nazwa_budynku = $budynek->name;

                    // Pobranie liczby mieszkań dla danego budynku
                    $args = array(
                        'post_type' => 'mieszkanie',
                        'posts_per_page' => -1,
                        'meta_query' => array(
                            array(
                                'key' => 'status',
                                'compare' => '=',
                                'value' => 'Wolne'
                            )
                        ),
                        'tax_query' => array(
                            array(
                                'taxonomy' => 'budynek',
                                'field' => 'term_id',
                                'terms' => $budynek->term_id,
                            ),
                        ),
                    );
                    $mieszkania_query = new WP_Query($args);
                    $liczba_mieszkan = $mieszkania_query->post_count;

                    // Dodanie danych do tablicy
                    $elementsData[] = array(
                        'koordynaty' => $koordynaty,
                        'url' => $url_widoku_taxonomii,
                        'kolor' => $status == 'Dostępny' ? 'green' : 'red',
                        'params' => array(
                            'Nazwa budynku: ' => $nazwa_budynku,
                            'Status: ' => $status,
                            'Liczba mieszkań: ' => $liczba_mieszkan
                        ),
                    );

                }
            }

        }

        $background_image = rwmb_get_value('rzut_inwestycji') ?? '';
    }

    if ($taxonomy == 'budynek') {
        if ($elements == 'apartments') {
            // 1. Pobranie wszystkich postów typu "mieszkanie"
            $args = array(
                'post_type' => 'mieszkanie',
                'posts_per_page' => -1,
                'tax_query' => array(
                    array(
                        'taxonomy' => 'budynek',
                        // Zamień to na taksonomię, której używasz
                        'field' => 'term_id',
                        // Może być 'term_id', 'name', 'slug' lub 'term_taxonomy_id'
                        'terms' => get_queried_object_id(),
                        // Pobiera ID aktualnego obiektu zapytania, co powinno działać, jeśli jesteś na stronie terminu taksonomii
                    ),
                ),
            );

            $mieszkania = new WP_Query($args);
            // Get current taxonomy permalink
            $url_budynku = get_term_link(get_queried_object_id());
            $mieszkaniaData = array();

            if ($mieszkania->have_posts()) {
                while ($mieszkania->have_posts()) {
                    $mieszkania->the_post();

                    $status = rwmb_get_value('status');
                    if ($status != "Sprzedane") {
                        // Pobranie metadanych
                        $coordinates = rwmb_get_value('coordinates');
                        $koordynaty_na_budynku_przod = rwmb_get_value('koordynaty_na_budynku_przod');
                        $koordynaty_na_budynku_tyl = rwmb_get_value('koordynaty_na_budynku_tyl');
                        $nazwa = get_the_title();

                        $metraz = rwmb_get_value('metraz');
                        $url_mieszkania = get_the_permalink();
                        $pokoje = rwmb_get_value('liczba_pokoi');

                        // Po kliknięciu w mieszkanie na rzucie budynku przenoś na widok piętra
                        $numer_pietra = rwmb_get_value('pietro');
                        $url;
                        if ($numer_pietra) {
                            $url = $url_budynku . '?pietro=' . $numer_pietra;
                        } else {
                            $url = $url_mieszkania;
                        }


                        $elementsData[] = array(
                            'id' => get_the_ID(),
                            'koordynaty_1' => $koordynaty_na_budynku_tyl,
                            'koordynaty_2' => $koordynaty_na_budynku_przod,
                            'url' => $url,
                            'kolor' => $status == "Wolne" ? 'green' : 'yellow',
                            'params' => array(
                                'Nazwa: ' => $nazwa,
                                'Status: ' => $status,
                                'Metraż: ' => $metraz,
                                'Liczba pokoi: ' => $pokoje
                            ),
                        );
                    }

                }
            }
            wp_reset_query();
            $image_1 = rwmb_meta('przod', ['object_type' => 'term', 'size' => 'full'], get_queried_object_id()) ?? '';
            $image_2 = rwmb_meta('tyl', ['object_type' => 'term', 'size' => 'full'], get_queried_object_id()) ?? '';
        }
        if ($elements == 'floors') {

        }
    }
    ?>
    <script src="https://unpkg.com/konva@9.2.0/konva.min.js"></script>
    <script type="text/javascript">
        var elementsData = <?php echo json_encode($elementsData, JSON_PRETTY_PRINT); ?>;
        var currentId = <?php echo json_encode(get_the_ID()) ?>;
        var backgroundImage = <?php echo json_encode($background_image) ?>;
        var image_1 = <?php echo json_encode($image_1) ?>;
        var image_2 = <?php echo json_encode($image_2) ?>;
        var views = <?php echo json_encode($views) ?>;
    </script>
    <style>
        .inter-map {
            width: 100%;
            height: auto !important;
        }
    </style>
    <?php
    // foreach $views echo '123'
    for ($i = 0; $i < $views; $i++) {
        ?>
        <div class="inter-map" id="interactive-map-<?= $i + 1 ?>"></div>
        <?php
    }
    if ($views > 0) {
        ?>
        <div class="switch-button-wrapper">
            <button class="rotate-building-button" id="switchButton">
                < Obróć budynek>
            </button>
        </div>
        <?php
    }

    ?>
    <div id="tooltip"
        style="pointer-events: none; position: absolute; display: none; background: white; border: 1px solid black; padding: 5px;">
    </div>
    <script>
        var sceneWidth = 1000;
        var sceneHeight = 1000;
        var containerId = 'interactive-map';

        let stages = {}; // create an object to hold the stages
        for (let index = 0; index < views; index++) {
            stages[index] = new Konva.Stage({
                container: containerId + '-' + (index + 1),
                width: sceneWidth,
                height: sceneHeight,
            });
        }
        

        var stage = new Konva.Stage({
            container: containerId,
            width: sceneWidth,
            height: sceneHeight,
        });

        var layer = new Konva.Layer();
        stage.add(layer);

        var imageObjMain = new Image();
        imageObjMain.onload = function () {
            var imageWidth = this.naturalWidth;
            var imageHeight = this.naturalHeight;

            var konvaImageMain = new Konva.Image({
                x: 0,
                y: 0,
                image: imageObjMain,
                width: sceneWidth,
                height: sceneHeight * (imageHeight / imageWidth),
            });

            layer.add(konvaImageMain);

            if (currentId == 2650) {
                elementsData.push(
                    {
                        "koordynaty": "[876,559,856,731,872,731,870,748,1016,773,1019,755,1033,757,1072,583,1093,420,1097,401,1118,296,1005,282,952,377,951,387]",
                        "url": "",
                        "kolor": "red",
                        "params": {
                            "Status: ": "Inwestycja sprzedana",
                            'Dostępnych mieszkań: ': 0
                        }
                    },
                    {
                        "koordynaty": "[867,262,854,391,768,510,737,720,725,722,724,732,605,718,605,707,594,707,589,589,628,493,633,482,624,481,664,384,682,368,682,334,721,334,738,319,739,285,783,253]",
                        "url": "https://rafin-developer.pro-pages.com/inwestycja/wybrzeze-reymonta-ii/",
                        "kolor": "green",
                        "params": {
                            "Status: ": "Dostępny",
                            'Dostępnych mieszkań: ': 3
                        }

                    },
                    {
                        "koordynaty": "[1,542,71,485,67,464,229,339,386,273,384,244,393,241,392,231,460,207,515,213,517,237,526,240,529,308,460,346,402,346,403,398,371,431,331,476,147,712,1,704]",
                        "url": "",
                        "kolor": "black",
                        "params": {
                            "Status: ": "Budynek w trakcie projektowania",
                            'Dostępnych mieszkań: ': 0
                        }

                    }
                );
            }

            elementsData.forEach(function (element) {
                if (element.koordynaty) {
                    var pointsMain = element.koordynaty
                        .replace(/[\[\]]/g, '')
                        .split(',')
                        .map(Number);

                    // Przeskaluj punkty do nowych wymiarów obrazu
                    for (var i = 0; i < pointsMain.length; i += 2) {
                        pointsMain[i] = pointsMain[i] / imageWidth * sceneWidth;
                        pointsMain[i + 1] = pointsMain[i + 1] / imageHeight * (sceneHeight * (imageHeight / imageWidth));
                    }

                    var polygonMain = new Konva.Line({
                        points: pointsMain,
                        fill: element.kolor,
                        opacity: 0.3,
                        stroke: 'black',
                        strokeWidth: 1,
                        closed: true
                    });

                    // Tworzenie referencji do elementów tooltip
                    var tooltip = document.getElementById('tooltip');



                    // Dodaj tooltip do zdarzenia 'mouseover'
                    polygonMain.on('mouseover', function () {
                        document.body.style.cursor = 'pointer';
                        this.fill(element.kolor);
                        this.opacity(0.5)
                        layer.draw();

                        // Append to tooltip element.params
                        if (element.params) {
                            // Usuń wszystkie dzieci tooltip
                            while (tooltip.firstChild) {
                                tooltip.removeChild(tooltip.firstChild);
                            }
                            for (const [key, value] of Object.entries(element.params)) {
                                var p = document.createElement('p');
                                p.innerText = key + value;
                                tooltip.appendChild(p);
                            }
                        }

                        // Pokaż tooltip
                        tooltip.style.display = 'block';
                    });

                    polygonMain.on('mouseout', function () {
                        document.body.style.cursor = 'default';
                        this.fill(element.kolor);
                        this.opacity(0.3)
                        layer.draw();

                        // Ukryj tooltip
                        tooltip.style.display = 'none';
                    });

                    // Aktualizuj pozycję tooltip na zdarzenie 'mousemove'
                    stage.on('mousemove', function (event) {
                        var mousePos = stage.getPointerPosition();
                        tooltip.style.left = (mousePos.x + 50) + 'px';
                        tooltip.style.top = (mousePos.y + 50) + 'px';
                    });

                    polygonMain.on('click', function () {
                        window.location.href = element.url;
                    });

                    layer.add(polygonMain);
                }
            });

            function fitStageIntoParentContainer() {
                var container = document.getElementById(containerId);
                var containerWidth = container.offsetWidth;
                var scale = containerWidth / sceneWidth;
                console.log(containerWidth);
                stage.width(sceneWidth * scale);
                stage.height((sceneHeight * (imageHeight / imageWidth)) * scale);
                stage.scale({ x: scale, y: scale });
            }

            fitStageIntoParentContainer();
            window.addEventListener('resize', fitStageIntoParentContainer);
        }

        var imageMainUrl = backgroundImage['full_url'];
        imageObjMain.src = imageMainUrl;

    </script>
    <?php

});