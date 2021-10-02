<?php

function manage_product_html() {?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <p>This is first paragraph</p>
    </div>
    <?php
}

function products_admin_menu() {
    add_menu_page(
        'Manage Product',// page title
        'Manage Product',// menu title
        'manage_options',// capability
        'products',// menu slug
        'manage_product_html'
    );

    add_submenu_page(
        'products',
        'Products',
        'All Products',
        'manage_options',
        'products',
        'manage_product_html'
    );

    add_submenu_page(
        'products',
        'Add Product',
        'Add Product',
        'manage_options',
        'add-product',
        'manage_product_html'
    );
}

add_action('admin_menu', 'products_admin_menu');

global $product_table_version;
$product_table_version = 1.0;


function product_table_install(){
    global $wpdb;
    global $product_table_version;

    $table_name = $wpdb->prefix.'products';

    $sql = "CREATE TABLE  IF NOT EXISTS " . $table_name . " (
        id int(11) NOT NULL AUTO_INCREMENT,
        product_title varchar(100) NOT NULL,
        product_description VARCHAR(200) NOT NULL,
        price float(8,2) NOT NULL,
        PRIMARY KEY  (id)
    );";

    require_once(ABSPATH.'wp-admin/includes/upgrade.php');

    dbDelta($sql);

    add_option('product_table_version', $product_table_version);

    $installedVersion = get_option('product_table_version');

    //For update version
    if($installedVersion  !== $product_table_version){

    }

}

register_activation_hook(PLUGIN_FILE_URL, 'product_table_install');

function product_seeder(){
    global $wpdb;
    $table_name = $wpdb->prefix.'products';
    $wpdb->insert($table_name, array(
            'product_title' => 'MackBook Pro',
            'product_description' => 'Apple M1 chip with 8‑core CPU, 8‑core GPU, and 16‑core Neural Engine 8GB unified memory 256GB SSD storage 13-inch Retina display with True Tone',
            'price' => 1350.0
    ));

}

register_activation_hook(PLUGIN_FILE_URL, 'product_seeder');


function product_table_update_check(){
    global $product_table_version;
}