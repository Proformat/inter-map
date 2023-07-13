<?php

// Register shortcode
add_shortcode('developer-system', function () {

    add_action('wp_enqueue_scripts', function () {
        wp_enqueue_script('konva', DEVELOPER_SYSTEM_PLUGIN_URL . 'assets/konva.min.js', array(), '8.4.3', true);
        wp_enqueue_script('developer-system', DEVELOPER_SYSTEM_PLUGIN_URL . 'classes/InterMap.js', array('konva'), '1.0.0', true);
    });

    ?>
<script>
    console.log(InterMap);
</script>    
    <?php
});