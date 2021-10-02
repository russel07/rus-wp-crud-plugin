<?php
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

function drop_product_table_uninstall() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'products';
    $sql = "DROP TABLE IF EXISTS $table_name";

    //require_once(ABSPATH.'wp-admin/includes/upgrade.php');
    $wpdb->query($sql);
    //dbDelta($sql);

    delete_option("product_table_version");
}

register_deactivation_hook(PLUGIN_FILE_URL, 'drop_product_table_uninstall');

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class ProductTableHandler extends WP_List_Table{
    public function __construct()
    {
        global $status, $page;
        $args = array(
            'singular' => 'product',
            'plural' => 'products',
        );

        parent::__construct($args);
    }

    function column_default($item, $column_name){
        return $item[$column_name];
    }

    function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="id[]" value="%s" />',
            $item['id']
        );
    }

    function get_columns()
    {
        return array(
            'cb' => '<input type="checkbox" />',
            'product_title' => __('Title', 'custom_product_crud'),
            'product_description' => __('Description', 'custom_product_crud'),
            'price' => __('Price $', 'custom_product_crud'),
        );
    }

    function get_sortable_columns()
    {
        return array(
            'product_title' => array('product_title', true),
            'product_description' => array('product_description', false),
            'price' => array('price', true)
        );
    }

    function get_bulk_actions()
    {
        return array(
            'delete' => 'Delete'
        );
    }

    function process_bulk_action(){
        global $wpdb;
        $table_name = $wpdb->prefix . 'products';

        if ('delete' === $this->current_action()) {
            $ids = isset($_REQUEST['id']) ? $_REQUEST['id'] : array();
            if (is_array($ids)) $ids = implode(',', $ids);

            if (!empty($ids)) {
                $wpdb->query("DELETE FROM $table_name WHERE id IN($ids)");
            }
        }
    }

    function prepare_items()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'products';

        $per_page = 10;

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();

        // here we configure table headers, defined in our methods
        $this->_column_headers = array($columns, $hidden, $sortable);

        // [OPTIONAL] process bulk action if any
        $this->process_bulk_action();

        // will be used in pagination settings
        $total_items = $wpdb->get_var("SELECT COUNT(id) FROM $table_name");

        // prepare query params, as usual current page, order by and order direction
        $paged = isset($_REQUEST['paged']) ? ($per_page * max(0, intval($_REQUEST['paged']) - 1)) : 0;
        $orderby = (isset($_REQUEST['orderby']) && in_array($_REQUEST['orderby'], array_keys($this->get_sortable_columns()))) ? $_REQUEST['orderby'] : 'product_title';
        $order = (isset($_REQUEST['order']) && in_array($_REQUEST['order'], array('asc', 'desc'))) ? $_REQUEST['order'] : 'asc';

        $this->items = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name ORDER BY $orderby $order LIMIT %d OFFSET %d", $per_page, $paged), ARRAY_A);

        // [REQUIRED] configure pagination
        $this->set_pagination_args(array(
            'total_items' => $total_items, // total items defined above
            'per_page' => $per_page, // per page constant defined at top of method
            'total_pages' => ceil($total_items / $per_page) // calculate pages count
        ));
    }


}

function manage_product_html() {
    //require_once('admin/view/hello-world.php');
    global $wpdb;

    $table = new ProductTableHandler();
    $table->prepare_items();

    $message = '';
    if ('delete' === $table->current_action()) {
        $message = '<div class="updated below-h2" id="message"><p>' . sprintf(__('Items deleted: %d', 'custom_product_crud'), count($_REQUEST['id'])) . '</p></div>';
    }
    ?>
    <div class="wrap">

        <div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
        <h2><?php _e('Products', 'custom_product_crud')?> <a class="add-new-h2" href="<?php echo get_admin_url(get_current_blog_id(), 'admin.php?page=add-product');?>"><?php _e('Add new', 'custom_product_crud')?></a>
        </h2>
        <?php echo $message; ?>

        <form id="products-table" method="GET">
            <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>"/>
            <?php $table->display() ?>
        </form>

    </div>
<?php }

function add_product_form(){
    global $wpdb;
    $table_name = $wpdb->prefix . 'products';

    $message = '';
    $notice = '';

    // this is default $item which will be used for new records
    $default = array(
        'id' => 0,
        'product_title' => '',
        'product_description' => '',
        'price' => 0.0,
    );

    $item = $default;
    add_meta_box('product_form_meta_box', 'Product Data', 'get_product_form', 'product', 'normal', 'default');
    ?>
    <div class="wrap">
        <div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
        <h2><?php _e('Person', 'custom_product_crud')?> <a class="add-new-h2" href="<?php echo get_admin_url(get_current_blog_id(), 'admin.php?page=products');?>"><?php _e('back to list', 'custom_product_crud')?></a>
        </h2>

        <?php if (!empty($notice)): ?>
            <div id="notice" class="error"><p><?php echo $notice ?></p></div>
        <?php endif;?>
        <?php if (!empty($message)): ?>
            <div id="message" class="updated"><p><?php echo $message ?></p></div>
        <?php endif;?>

        <form id="form" method="POST">
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce(basename(__FILE__))?>"/>

            <input type="hidden" name="id" value=""/>

            <div class="metabox-holder" id="poststuff">
                <div id="post-body">
                    <div id="post-body-content">
                        <?php do_meta_boxes('person', 'normal', $item); ?>

                        <input type="submit" value="<?php _e('Save', 'custom_product_crud')?>" id="submit" class="button-primary" name="submit">
                    </div>
                </div>
            </div>
        </form>
    </div>
<?php }

function get_product_form($item){?>
    <table cellspacing="2" cellpadding="5" style="width: 100%;" class="form-table">
        <tbody>
        <tr class="form-field">
            <th valign="top" scope="row">
                <label for="product_title"><?php _e('Title', 'custom_product_crud')?></label>
            </th>
            <td>
                <input id="product_title" name="product_title" type="text" style="width: 95%" value="<?php echo esc_attr($item['product_title'])?>"
                       size="100" class="code" placeholder="<?php _e('Product Title', 'custom_product_crud')?>" required>
            </td>
        </tr>
        <tr class="form-field">
            <th valign="top" scope="row">
                <label for="product_description"><?php _e('Description', 'custom_product_crud')?></label>
            </th>
            <td>
                <textarea id="product_description" name="product_description" style="width: 95%" size="200" class="code" placeholder="<?php _e('Product Description', 'custom_product_crud')?>" required></textarea>
            </td>
        </tr>
        <tr class="form-field">
            <th valign="top" scope="row">
                <label for="price"><?php _e('Price', 'custom_product_crud')?></label>
            </th>
            <td>
                <input id="price" name="price" type="number" style="width: 95%" value="<?php echo esc_attr($item['price'])?>"
                       size="50" class="code" placeholder="<?php _e('Product Price', 'custom_product_crud')?>" required>
            </td>
        </tr>
        </tbody>
    </table>

<?php   }

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
        'add_product_form'
    );
}

add_action('admin_menu', 'products_admin_menu');
