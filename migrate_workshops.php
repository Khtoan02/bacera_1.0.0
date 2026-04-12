<?php
require_once('/Applications/ServBay/www/bacera/wp-load.php');
global $wpdb;

$tw = $wpdb->prefix . 'bacera_workshops';
$ts = $wpdb->prefix . 'bacera_workshop_slots';
$tb = $wpdb->prefix . 'bacera_workshop_bookings';

if ($wpdb->get_var("SHOW TABLES LIKE '{$tw}'") !== $tw) {
    die("No custom workshops table found.");
}

$workshops = $wpdb->get_results("SELECT * FROM {$tw}", ARRAY_A);
if ($workshops) {
    foreach ($workshops as $w) {
        $existing = get_posts([
            'post_type' => 'workshop',
            'meta_key' => '_old_id',
            'meta_value' => $w['id'],
            'post_status' => 'any'
        ]);
        if (!empty($existing)) {
            $post_id = $existing[0]->ID;
        } else {
            $post_id = wp_insert_post([
                'post_type' => 'workshop',
                'post_title' => $w['title'],
                'post_content' => $w['description'],
                'post_name' => $w['slug'],
                'post_status' => $w['status'] == 'active' ? 'publish' : 'draft',
            ]);
            
            update_post_meta($post_id, '_old_id', $w['id']);
            update_post_meta($post_id, '_price', $w['price']);
            update_post_meta($post_id, '_duration', $w['duration']);
            update_post_meta($post_id, '_trainer', $w['trainer']);
            update_post_meta($post_id, '_target', $w['target']);
            update_post_meta($post_id, '_includes', $w['includes']);
            update_post_meta($post_id, '_excludes', $w['excludes']);
            update_post_meta($post_id, '_process', $w['process']);
            update_post_meta($post_id, '_thumbnail_url', $w['thumbnail']);
        }
        
        if ($post_id) {
            $wpdb->update($ts, ['workshop_id' => $post_id], ['workshop_id' => $w['id']]);
            $wpdb->update($tb, ['workshop_id' => $post_id], ['workshop_id' => $w['id']]);
        }
    }
}
echo "Migration Complete!";
