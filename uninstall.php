<?php

global $wpdb;

$table_name = $wpdb->prefix . 'products';
$sql = "DROP TABLE IF EXISTS $table_name";
$wpdb->query($sql);