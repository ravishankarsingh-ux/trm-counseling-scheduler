<?php
/**
 * TRM Color Customization
 * Injects dynamic CSS based on color settings
 */

class TRM_Colors {

    public function __construct() {
        add_action('wp_head', array($this, 'inject_color_styles'));
    }

    /**
     * Inject dynamic color styles in wp_head
     */
    public function inject_color_styles() {
        $slot_bg = TRM_Database::get_setting('trm_slot_bg_color', '#f5f5f5');
        $slot_text = TRM_Database::get_setting('trm_slot_text_color', '#333333');
        $selected_bg = TRM_Database::get_setting('trm_slot_selected_bg_color', '#28a745');
        $selected_text = TRM_Database::get_setting('trm_slot_selected_text_color', '#ffffff');
        
        // Get lighten color for hover
        $lighten_bg = $this->lighten_color($slot_bg, 10);

        ?>
        <style id="trm-custom-colors">
        .trm-time-slot-simple {
            background-color: <?php echo esc_attr($slot_bg); ?> !important;
            color: <?php echo esc_attr($slot_text); ?> !important;
            border: 2px solid #ddd !important;
        }
        .trm-time-slot-simple:hover {
            background-color: <?php echo esc_attr($lighten_bg); ?> !important;
            border-color: #4CAF50 !important;
        }
        .trm-time-slot-simple.selected {
            background-color: <?php echo esc_attr($selected_bg); ?> !important;
            color: <?php echo esc_attr($selected_text); ?> !important;
            border-color: <?php echo esc_attr($selected_bg); ?> !important;
        }
        </style>
        <?php
    }

    /**
     * Lighten a color for hover effect
     */
    private function lighten_color($color, $percent) {
        $color = ltrim($color, '#');
        $color = hexdec($color);

        $r = ($color >> 16) & 0xFF;
        $g = ($color >> 8) & 0xFF;
        $b = $color & 0xFF;

        $r = min(255, intval($r + (255 - $r) * ($percent / 100)));
        $g = min(255, intval($g + (255 - $g) * ($percent / 100)));
        $b = min(255, intval($b + (255 - $b) * ($percent / 100)));

        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }
}
