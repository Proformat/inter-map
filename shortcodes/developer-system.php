<?php

// Register shortcode
add_shortcode('developer-system', function ($atts) {

    // Pobierz atrybuty
    $a = shortcode_atts(array(
        'name' => 'name',
        'value' => 'value',
    ), $atts);

    $a['name'] = sanitize_text_field($a['name']);
    $a['value'] = sanitize_text_field($a['value']);
    
    // loadFrontEndScriptsAndStyles();
    //echo 'foo<div class="developer-system" data-name="' . $a['name'] . '" data-value="' . $a['value'] . '"></div>';

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


                $mieszkaniaData[] = array(
                    'id' => get_the_ID(),
                    'nazwa' => $nazwa,
                    'coordinates' => $coordinates,
                    'koordynaty_na_budynku_przod' => $koordynaty_na_budynku_przod,
                    'koordynaty_na_budynku_tyl' => $koordynaty_na_budynku_tyl,
                    'status' => $status,
                    'metraz' => $metraz,
                    'url' => $url,
                    'kolor' => $status == "Wolne" ? 'green' : 'yellow',
                    'pokoje' => $pokoje,
                );
            }

        }
    }
    wp_reset_query();
    $image_front = rwmb_meta('przod', ['object_type' => 'term', 'size' => 'full'], get_queried_object_id()) ?? '';
    $image_back = rwmb_meta('tyl', ['object_type' => 'term', 'size' => 'full'], get_queried_object_id()) ?? '';
    ?>
    <script type="text/javascript">
        var mieszkaniaData = <?php echo json_encode($mieszkaniaData, JSON_PRETTY_PRINT); ?>;
        var imageFront = <?php echo json_encode($image_front) ?>;
        var imageBack = <?php echo json_encode($image_back) ?>;
    </script>
    <script src="https://unpkg.com/konva@8.3.1/konva.min.js"></script>
    <div class="switch-button-wrapper">
        <button class="rotate-building-button" id="switchButton">
            < Obróć budynek>
        </button>
    </div>
    <div id="interactive-map-front"></div>
    <div id="interactive-map-back"></div>
    <div id="tooltip"
        style="pointer-events: none; position: absolute; display: none; background: white; border: 1px solid black; padding: 5px;">
        <div id="tooltip-name"></div>
        <div id="tooltip-status"></div>
        <div id="tooltip-pokoje"></div>
        <div id="tooltip-metraz"></div>
    </div>
    <script>
        // Tworzenie instancji konva
        var stageFront = new Konva.Stage({
            container: 'interactive-map-front',
            width: document.querySelector('.polygon-map').offsetWidth,
            height: window.innerHeight,
        });
        var stageBack = new Konva.Stage({
            container: 'interactive-map-back',
            width: document.querySelector('.polygon-map').offsetWidth,
            height: window.innerHeight,
        });

        // Ukrycie strony tylnej
        document.getElementById('interactive-map-back').style.display = "none";

        // Tworzenie warstw
        var layerFront = new Konva.Layer();
        var layerBack = new Konva.Layer();

        // Tworzenie referencji do elementów tooltip poza obsługą zdarzeń
        var tooltip = document.getElementById('tooltip');
        var tooltipName = document.getElementById('tooltip-name');
        var tooltipStatus = document.getElementById('tooltip-status');
        var tooltipMetraz = document.getElementById('tooltip-metraz');
        var tooltipPokoje = document.getElementById('tooltip-pokoje');

        // Utwórz obrazy na tle
        var imageFrontUrl = imageFront['full_url'];
        var imageBackUrl = imageBack['full_url'];

        var polygonPrototype = new Konva.Line({
            opacity: 0.3,
            stroke: 'black',
            strokeWidth: 2,
            closed: true // Opcja ta zamyka kształt, tworząc wielobok
        });
        polygonPrototype.cache();

        var imageObjFront = new Image();
        imageObjFront.onload = function () {
            var konvaImageFront = new Konva.Image({
                x: 0,
                y: 0,
                image: imageObjFront,
            });

            // Ustaw wysokość sceny na tę samą, co obrazu
            stageFront.height(imageObjFront.height);

            // Dodaj obraz do warstwy i narysuj warstwę
            layerFront.add(konvaImageFront);

            var polygonFront;
            // Dodaj mieszkania do warstwy
            mieszkaniaData.forEach(function (mieszkanie) {
                if (mieszkanie.koordynaty_na_budynku_przod) {
                    // Przekształć łańcuch znaków w tablicę liczb
                    var pointsFront = mieszkanie.koordynaty_na_budynku_przod
                        .replace(/[\[\]]/g, '')  // Usuń nawiasy
                        .split(',')              // Podziel na wartości
                        .map(Number);            // Przekształć każdą wartość w liczbę

                    polygonFront = polygonPrototype.clone({
                        points: pointsFront,
                        fill: mieszkanie.kolor,
                    });

                    polygonFront.cache();

                    // Dodaj tooltip do zdarzenia 'mouseover'
                    polygonFront.on('mouseover', function () {
                        document.body.style.cursor = 'pointer';
                        this.fill(mieszkanie.kolor);
                        this.opacity(0.5);
                        layerFront.batchDraw();

                        // Ustaw wartości tooltip
                        tooltipName.innerText = mieszkanie.nazwa;
                        tooltipStatus.innerText = 'Status: ' + mieszkanie.status;
                        tooltipPokoje.innerText = 'Liczba pokoi: ' + mieszkanie.pokoje;
                        tooltipMetraz.innerText = 'Metraż: ' + mieszkanie.metraz + ' m²';

                        // Pokaż tooltip
                        tooltip.style.display = 'block';
                    });

                    polygonFront.on('mouseout', function () {
                        document.body.style.cursor = 'default';
                        this.fill(mieszkanie.kolor);
                        this.opacity(0.3);
                        layerFront.batchDraw();

                        // Ukryj tooltip
                        tooltip.style.display = 'none';
                    });
                    polygonFront.on('click', function () {
                        window.location.href = mieszkanie.url + '&flat_id=' + mieszkanie.id;
                    });

                    // Dodaj mieszkanie do warstwy
                    layerFront.add(polygonFront);
                }
            });

            layerFront.batchDraw();

            // Dodaj warstwę do sceny
            stageFront.add(layerFront);
        };

        imageObjFront.src = imageFrontUrl;

        var imageObjBack = new Image();
        imageObjBack.onload = function () {
            var konvaImageBack = new Konva.Image({
                x: 0,
                y: 0,
                image: imageObjBack,
            });

            // Ustaw wysokość sceny na tę samą, co obrazu
            stageBack.height(imageObjBack.height);

            // Dodaj obraz do warstwy i narysuj warstwę
            layerBack.add(konvaImageBack);
            var polygonBack;
            // Dodaj mieszkania do warstwy
            mieszkaniaData.forEach(function (mieszkanie) {
                if (mieszkanie.koordynaty_na_budynku_tyl) {
                    // Przekształć łańcuch znaków w tablicę liczb
                    var pointsBack = mieszkanie.koordynaty_na_budynku_tyl
                        .replace(/[\[\]]/g, '')  // Usuń nawiasy
                        .split(',')              // Podziel na wartości
                        .map(Number);            // Przekształć każdą wartość w liczbę

                    polygonBack = polygonPrototype.clone({
                        points: pointsBack,
                        fill: mieszkanie.kolor,
                    });

                    polygonBack.cache();

                    polygonBack.on('mouseover', function () {
                        document.body.style.cursor = 'pointer';
                        this.fill(mieszkanie.kolor);
                        this.opacity(0.5);
                        layerBack.batchDraw();

                        // Ustaw wartości tooltip
                        tooltipName.innerText = mieszkanie.nazwa;
                        tooltipStatus.innerText = 'Status: ' + mieszkanie.status;
                        tooltipPokoje.innerText = 'Liczba pokoi: ' + mieszkanie.pokoje;
                        tooltipMetraz.innerText = 'Metraż: ' + mieszkanie.metraz + ' m²';

                        // Pokaż tooltip
                        tooltip.style.display = 'block';
                    });

                    polygonBack.on('mouseout', function () {
                        document.body.style.cursor = 'default';
                        this.fill(mieszkanie.kolor);
                        this.opacity(0.3);
                        layerBack.batchDraw();

                        // Ukryj tooltip
                        tooltip.style.display = 'none';
                    });

                    polygonBack.on('click', function () {
                        window.location.href = mieszkanie.url + '&flat_id=' + mieszkanie.id;;
                    });

                    // Dodaj mieszkanie do warstwy
                    layerBack.add(polygonBack);
                }
            });

            layerBack.batchDraw();

            // Dodaj warstwę do sceny
            stageBack.add(layerBack);
        };

        imageObjBack.src = imageBackUrl;

        stageFront.on('mousemove', function (event) {
            var mousePos = stageFront.getPointerPosition();
            tooltip.style.left = (mousePos.x + 50) + 'px';
            tooltip.style.top = (mousePos.y + 50) + 'px';
        });

        stageBack.on('mousemove', function (event) {
            var mousePos = stageBack.getPointerPosition();
            tooltip.style.left = (mousePos.x + 50) + 'px';
            tooltip.style.top = (mousePos.y + 50) + 'px';
        });


        // przyciski do zmiany widoku
        var button = document.getElementById('switchButton');
        button.addEventListener('click', function () {
            if (document.getElementById('interactive-map-front').style.display !== "none") {
                document.getElementById('interactive-map-front').style.display = "none";
                document.getElementById('interactive-map-back').style.display = "block";
            } else {
                document.getElementById('interactive-map-front').style.display = "block";
                document.getElementById('interactive-map-back').style.display = "none";
            }
        });


    </script>
    <?php
});
