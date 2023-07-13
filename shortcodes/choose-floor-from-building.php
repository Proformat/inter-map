<?php 

// Register shortcode
add_shortcode('choose-floor-from-building', function ($atts) {
    // Pobierz ID obiektu aktualnego zapytania
    $current_id = get_queried_object_id();

    // Pobierz taksonomie dzieci (pod-taksonomie) dla aktualnej taksonomii
    $pietra = get_terms(
        array(
            'taxonomy' => 'budynek',
            // Zamień to na taksonomię, której używasz
            'parent' => $current_id,
            // Użyj ID obiektu aktualnego zapytania
            'hide_empty' => false,
        )
    );

    // 1. Sprawdzenie, czy pobrano przynajmniej jeden budynek
    if (!empty($pietra) && !is_wp_error($pietra)) {
        // 2. Tworzenie pustej tablicy do przechowywania danych pięter
        $elementsData = array();

        // Inicjalizuj pustą tablicę elementsData
        $elementsData = [];

        foreach ($pietra as $pietro) {
            $nazwa_pietra = $pietro->name;
            $url_widoku_taxonomii_pietra = get_term_link($pietro);

            // Dodanie danych o piętrach do tablicy
            $elementsData[] = array(
                'koordynaty' => rwmb_meta('koordynaty_na_budynku', ['object_type' => 'term'], $pietro->term_id),
                'nazwa_pietra' => $nazwa_pietra,
                'url' => $url_widoku_taxonomii_pietra,
                'kolor' => 'green',
                'status' => 'Dostępne',
                'liczba_mieszkan' => '123'
            );
        }
    }

    $background_image = rwmb_meta('przod', ['object_type' => 'term'], get_queried_object_id()) ?? '';

    ?>

    <script src="https://unpkg.com/konva@8.3.1/konva.min.js"></script>

    <div id="interactive-map"></div>
    <div id="tooltip"
        style="pointer-events: none; position: absolute; display: none; background: white; border: 1px solid black; padding: 5px;">
        <div id="tooltip-name"></div>
        <div id="tooltip-status"></div>
        <div id="tooltip-metraz"></div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {

            var elementsData = <?php echo json_encode($elementsData, JSON_PRETTY_PRINT); ?>;
            var backgroundImage = <?php echo json_encode($background_image) ?>;

            var stageMain, layerMain, imageObjMain, imageMainRatio, imageMainOriginalWidth, imageMainOriginalHeight, imageMainWidth, imageMainHeight, konvaImageMain;


            function createLine(points) {
                var scaleX = imageMainWidth / imageMainOriginalWidth;
                var scaleY = imageMainHeight / imageMainOriginalHeight;
                var scaledPoints = points.map(function (point, index) {
                    return point * ((index % 2 === 0) ? scaleX : scaleY);
                });

                var line = new Konva.Line({
                    points: scaledPoints,
                    // reszta konfiguracji linii
                });

                return line;
            }

            function initKonva() {
                // Szerokość kontenera
                var imageMainWidth = document.querySelector('.polygon-map').offsetWidth;
                var imageMainHeight = imageMainWidth / imageMainRatio;
                var scaleX = imageMainWidth / imageMainOriginalWidth; // obliczamy skale dla osi x
                var scaleY = imageMainHeight / imageMainOriginalHeight; // obliczamy skale dla osi y

                stageMain.width(imageMainWidth);
                stageMain.height(imageMainHeight);

                // Update the main image dimensions
                konvaImageMain.width(imageMainWidth);
                konvaImageMain.height(imageMainHeight);

                // Create a new layer for the lines
                var lineLayer = new Konva.Layer();

                // Add the existing lines to the new layer
                var lines = stageMain.find('Line');
                lines.each(function (line) {
                    line.remove();
                    lineLayer.add(line);
                });

                // Scale the new layer
                lineLayer.scale({ x: scaleX, y: scaleY });

                // Add the new layer to the stage
                stageMain.add(lineLayer);

                layerMain.draw();
            }





            stageMain = new Konva.Stage({
                container: 'interactive-map',
                width: window.innerWidth,
                height: window.innerHeight,
            });

            layerMain = new Konva.Layer();

            imageObjMain = new Image();
            imageObjMain.onload = function () {
                imageMainRatio = this.width / this.height;
                imageMainOriginalWidth = this.width;
                imageMainOriginalHeight = this.height;

                var imageMainWidth = document.querySelector('.polygon-map').offsetWidth;
                var imageMainHeight = imageMainWidth / imageMainRatio;

                konvaImageMain = new Konva.Image({
                    x: 0,
                    y: 0,
                    image: imageObjMain,
                    width: imageMainWidth,
                    height: imageMainHeight,
                });

                layerMain.add(konvaImageMain);
                stageMain.add(layerMain);


                var isAnimationStarted = false;

                var polygonPrototype = new Konva.Line({
                    opacity: 0.3,
                    stroke: 'black',
                    strokeWidth: 1,
                    closed: true, // Opcja ta zamyka kształt, tworząc wielobok
                    name: 'prototype',
                });
                layerMain.add(polygonPrototype);
                polygonPrototype.cache();
                var polygonMain;
                elementsData.forEach(function (element, index) {
                    if (element.koordynaty) {
                        // Przekształć łańcuch znaków w tablicę liczb
                        var pointsMain = element.koordynaty
                            .replace(/[\[\]]/g, '')  // Usuń nawiasy
                            .split(',')              // Podziel na wartości
                            .map(Number);            // Przekształć każdą wartość w liczbę

                        polygonMain = polygonPrototype.clone({
                            points: pointsMain,
                            fill: element.kolor,
                            name: 'flat-' + element.id,
                        });

                        // Jeśli jest to pierwszy element, zastosuj animację mrugania

                        // Tworzenie referencji do elementów tooltip
                        var tooltip = document.getElementById('tooltip');
                        var tooltipName = document.getElementById('tooltip-name');
                        var tooltipStatus = document.getElementById('tooltip-status');
                        var tooltipMetraz = document.getElementById('tooltip-metraz');

                        // Dodaj tooltip do zdarzenia 'mouseover'
                        polygonMain.on('mouseover', function () {
                            document.body.style.cursor = 'pointer';
                            console.log(element.kolor);
                            this.fill(element.kolor);
                            this.opacity(0.5)
                            layerMain.draw();

                            // Ustaw wartości tooltip
                            tooltipName.innerText = element.nazwa;
                            tooltipStatus.innerText = 'Status: ' + element.status;
                            tooltipMetraz.innerText = element.status == 'Dostępny' ? 'Dostępnych mieszkań: ' + element.liczba_mieszkan : '';

                            // Pokaż tooltip
                            tooltip.style.display = 'block';
                        });

                        polygonMain.on('mouseout', function () {
                            document.body.style.cursor = 'default';
                            this.fill(element.kolor);

                            this.opacity(0.3)
                            layerMain.draw();

                            // Ukryj tooltip
                            tooltip.style.display = 'none';
                        });

                        // Aktualizuj pozycję tooltip na zdarzenie 'mousemove'
                        stageMain.on('mousemove', function (event) {
                            var mousePos = stageMain.getPointerPosition();
                            tooltip.style.left = (mousePos.x + 50) + 'px';
                            tooltip.style.top = (mousePos.y + 50) + 'px';
                        });

                        polygonMain.on('click', function () {
                            window.location.href = element.url;
                        });


                        polygonMain.cache();
                        layerMain.add(polygonMain);
                    }
                });

                if (typeof mieszkanieIndex !== 'undefined') {
                    const selector = '#flat-' + mieszkanieIndex;
                    const elementToAnimate = stageMain.find('.flat-' + mieszkanieIndex)[0];

                    if (elementToAnimate && !isAnimationStarted) {

                        isAnimationStarted = true;
                        // Tworzymy instancję animacji
                        var animation = new Konva.Animation(function (frame) {
                            var period = 1000;
                            var opacity = 0.2 * Math.abs(Math.sin(frame.time * 2 * Math.PI / period)) + 0.5;
                            elementToAnimate.opacity(opacity);
                        }, layerMain);

                        // Uruchamiamy animację na 6 sekund
                        animation.start();

                        // Zatrzymujemy animację po 6 sekundach
                        setTimeout(function () {
                            animation.stop();
                        }, 8000);
                    }

                    if (elementToAnimate) {
                        const rect = elementToAnimate.getClientRect();
                        const centerX = rect.x + rect.width / 2;
                        const centerY = rect.y + rect.height / 2;

                        // Pinezka jako Konva.Image
                        // Załaduj SVG jako obraz, a następnie stwórz Konva.Image
                        const imageObj = new Image();
                        imageObj.onload = function () {
                            const pinezka = new Konva.Image({
                                x: centerX,
                                y: centerY,
                                image: imageObj,
                                width: 50, // ustaw własną szerokość
                                height: 50, // ustaw własną wysokość
                                offsetX: 25, // połowa szerokości, aby środek był umieszczony poprawnie
                                offsetY: 50, // połowa wysokości, aby środek był umieszczony poprawnie
                            });

                            // Dodajemy pinezkę do warstwy i odświeżamy scenę
                            layerMain.add(pinezka);
                            layerMain.draw();
                        };
                        imageObj.src = 'https://rafin-developer.pro-pages.com/wp-content/uploads/pin2.svg'; // tutaj podaj ścieżkę do pliku SVG
                    }
                }


                layerMain.draw();
                initKonva(imageMainHeight);
            };

            console.log(backgroundImage);
            imageObjMain.src = backgroundImage.full_url;
            var resizeTimeout;
            window.addEventListener('resize', function () {
                clearTimeout(resizeTimeout);
                resizeTimeout = setTimeout(function () {
                    // Przeskalowanie obrazu
                    imageMainWidth = document.querySelector('#interactive-map').offsetWidth; // zmieniłem selektor z .polygon-map na #interactive-map
                    imageMainHeight = imageMainWidth / imageMainRatio;

                    initKonva(imageMainHeight);
                }, 250);
            });
        });
    </script>
    <?php
});

