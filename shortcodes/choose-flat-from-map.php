<?php 

// Register shortcode - choose flat from flat image map
add_shortcode('choose-flat-from-map', function ($atts) {
    $floor_number = isset($_GET['pietro']) ? intval($_GET['pietro']) : null;
    $floor_data = get_taxonomy_children_by_floor($floor_number);
    $mieszkaniaData = $floor_data ? get_flats_on_floor($floor_number, $floor_data['parent'], $floor_data['taxonomy']) : [];


    $background_image = rwmb_meta('rzut_pietra', ['object_type' => 'term'], $floor_data['id']) ?? '';
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

            var elementsData = <?php echo json_encode($mieszkaniaData, JSON_PRETTY_PRINT); ?>;
            var backgroundImage = <?php echo json_encode($background_image) ?>;

            var stageMain, layerMain, imageObjMain, imageMainRatio, imageMainOriginalWidth, imageMainOriginalHeight;

            function initKonva() {
                // Szerookość kontenera
                var imageMainWidth = document.querySelector('.polygon-map').offsetWidth;
                var scaleRatio = imageMainWidth / imageMainOriginalWidth;
                var imageMainHeight = imageMainOriginalHeight * scaleRatio;

                stageMain.width(imageMainWidth);
                stageMain.height(imageMainHeight);

                imageObjMain.width = imageMainWidth * scaleRatio;
                imageObjMain.height = imageMainHeight * scaleRatio;

                var lines = stageMain.find('Line');
                for (var i = 0; i < lines.length; i++) {
                    var line = lines[i];
                    var points = line.points().map(function (point, index) {
                        return point * ((index % 2 === 0) ? scaleX : scaleY);
                    });
                    line.points(points);
                }

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

                var konvaImageMain = new Konva.Image({
                    x: 0,
                    y: 0,
                    image: imageObjMain,
                    width: imageMainWidth,
                    height: imageMainHeight,
                });

                layerMain.add(konvaImageMain);
                stageMain.add(layerMain);
                initKonva();

                var isAnimationStarted = false;

                var polygonPrototype = new Konva.Line({
                    opacity: 0.3,
                    stroke: 'black',
                    strokeWidth: 1,
                    closed: true, // Opcja ta zamyka kształt, tworząc wielobok
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

            };

            console.log(backgroundImage);
            imageObjMain.src = backgroundImage.full_url;
            var resizeTimeout;
            window.addEventListener('resize', function () {
                clearTimeout(resizeTimeout);
                resizeTimeout = setTimeout(function () {
                    initKonva();
                }, 250);
            });
        });

    </script>
    <?php
});
