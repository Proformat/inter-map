class InterMap {
    constructor(containerId, initialImage, initialOutlines) {
        this.stage = new Konva.Stage({
            container: containerId,
            width: document.getElementById(containerId).offsetWidth,
            height: document.getElementById(containerId).offsetHeight
        });

        this.layer = new Konva.Layer();
        this.stage.add(this.layer);

        this.setImageAndOutlines(initialImage, initialOutlines);
    }

    setImageAndOutlines(imageUrl, outlines) {
        this.layer.destroyChildren();
        this.showLoading();
    
        Konva.Image.fromURL(imageUrl, (image) => {
            image.setAttrs({
                x: 0,
                y: 0,
                width: this.stage.width(),
                height: this.stage.height()
            });
    
            this.layer.add(image);
            this.layer.draw();
            
            this.addOutlines(outlines); // Dodajemy wielokąty po dodaniu obrazu
    
            this.hideLoading();
        });
    }
    

    addOutlines(outlines) {
        outlines.forEach(outline => {
            const polygon = new Konva.Line({
                points: outline.points,
                fill: 'rgba(0,0,255,0.5)',
                stroke: 'black',
                strokeWidth: 2,
                closed: true,
                draggable: false
            });

            polygon.on('mouseover', () => {
                document.body.style.cursor = 'pointer';
                polygon.fill('rgba(0,0,255,0.7)');
                this.layer.batchDraw();
            });

            polygon.on('mouseout', () => {
                document.body.style.cursor = 'default';
                polygon.fill('rgba(0,0,255,0.5)');
                this.layer.batchDraw();
            });

            polygon.on('mousemove', () => {
                const mousePos = this.stage.getPointerPosition();
                tooltip.position({
                    x: mousePos.x,
                    y: mousePos.y + 10
                });
                tooltip.getText().text(outline.details);
                tooltip.show();
                this.layer.batchDraw();
            });

            polygon.on('mouseleave', () => {
                tooltip.hide();
                this.layer.batchDraw();
            });

            this.layer.add(polygon);
        });
    }

    showLoading() {
        this.loadingAnimation = new Konva.Animation((frame) => {
            const scale = Math.sin((frame.time * 2 * Math.PI) / 1000) + 1;
            this.loadingCircle.scaleX(scale);
            this.loadingCircle.scaleY(scale);
        }, this.layer);

        this.loadingCircle = new Konva.Circle({
            x: this.stage.width() / 2,
            y: this.stage.height() / 2,
            radius: 70,
            fill: 'blue',
            opacity: 0.5
        });

        this.layer.add(this.loadingCircle);
        this.loadingAnimation.start();
    }

    hideLoading() {
        this.loadingAnimation.stop();
        this.loadingCircle.destroy();
    }
}

// const tooltip = new Konva.Label({
//     opacity: 0.75,
//     visible: false,
//     listening: false
// });

// tooltip.add(new Konva.Tag({
//     fill: 'black'
// }));

// tooltip.add(new Konva.Text({
//     text: '',
//     fontFamily: 'Calibri',
//     fontSize: 18,
//     padding: 5,
//     fill: 'white'
// }));

// // Przykładowe użycie
// let interMap = new InterMap('inter-map', 'https://rafin-developer.pro-pages.com/wp-content/uploads/0851_09_III_KONDYGNACJE-0851_09_KM_L0-100-skompresowany.jpg', [
//     { points: [10, 10, 20, 20, 15, 15], details: 'Detal 1' },
//     { points: [30, 30, 40, 40, 35, 35], details: 'Detal 2' }
// ]);

// document.getElementById('changeButton').addEventListener('click', () => {
//     interMap.setImageAndOutlines('https://rafin-developer.pro-pages.com/wp-content/uploads/0851_09_III_KONDYGNACJE-0851_09_KM_L5-105.jpg', [
//         { points: [500, 500, 600, 600, 550, 550], details: 'Nowy detal' }
//     ]);
// });
