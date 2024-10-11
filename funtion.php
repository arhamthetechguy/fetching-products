// fetching products
// Function to fetch products from the FakeStore API
function get_products_from_fakestore_api() {
    // Make the GET request to the FakeStore API endpoint
    $response = wp_remote_get( 'https://fakestoreapi.com/products' );

    // Check for errors in the response
    if ( is_wp_error( $response ) ) {
        return 'Failed to retrieve data';
    }

    // Extract the response body (the actual data returned by the API)
    $body = wp_remote_retrieve_body( $response );

    // Decode the JSON data into a PHP array/object
    $products = json_decode( $body );

    // Return the products if the response is not empty
    if ( !empty( $products ) ) {
        return $products;
    } else {
        return 'No products found';
    }
}

// Shortcode function to display the products with pagination and links to single pages
function display_fakestore_products_with_pagination() {
    $products = get_products_from_fakestore_api();

    if ( is_string( $products ) ) {
        return $products;
    }

    $products_per_page = 10;
    $paged = get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1;
    $total_pages = ceil( count( $products ) / $products_per_page );

    $paged_products = array_slice( $products, ( $paged - 1 ) * $products_per_page, $products_per_page );

    ob_start();
    ?>
    <div class="fakestore-products">
        <?php foreach ( $paged_products as $product ): ?>
            <div class="fakestore-product">
                <?php
                // Use the correct single product page URL here
                $product_detail_url = site_url('/single-product-page/?product_id=' . $product->id);
                ?>
                <a href="<?php echo esc_url( $product_detail_url ); ?>">
                    <img src="<?php echo esc_url( $product->image ); ?>" alt="<?php echo esc_attr( $product->title ); ?>" />
                    <h2><?php echo esc_html( $product->title ); ?></h2>
                </a>
                <p><?php echo esc_html( substr( $product->description, 0, 100 ) ) . '...'; ?></p>
                <p>Category: <?php echo esc_html( $product->category ); ?></p>
                <p>Price: $<?php echo esc_html( $product->price ); ?></p>
                <p>Rating: <?php echo esc_html( $product->rating->rate ); ?> (<?php echo esc_html( $product->rating->count ); ?> reviews)</p>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="fakestore-pagination">
        <?php
        echo paginate_links( array(
            'base'      => str_replace( 999999999, '%#%', esc_url( get_pagenum_link( 999999999 ) ) ),
            'total'     => $total_pages,
            'current'   => $paged,
            'format'    => '?paged=%#%',
            'prev_text' => '&laquo; Previous',
            'next_text' => 'Next &raquo;',
        ) );
        ?>
    </div>

    <?php
    return ob_get_clean();
}

add_shortcode( 'fakestore_products', 'display_fakestore_products_with_pagination' );

// Ensure that WordPress recognizes the paged query var
function add_pagination_support() {
    if ( !is_admin() ) {
        add_rewrite_rule( '^page/([0-9]+)/?', 'index.php?paged=$matches[1]', 'top' );
    }
}
add_action( 'init', 'add_pagination_support' );

// Allow WordPress to process 'paged' as a query variable
function add_pagination_query_vars( $vars ) {
    $vars[] = 'paged';
    return $vars;
}
add_filter( 'query_vars', 'add_pagination_query_vars' );


// Set this to true to enable debug output, false to disable
define('DEBUG_MODE', false);

function display_single_fakestore_product() {
    // Check if the product_id or preview_id query var is present
    $product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : (isset($_GET['preview_id']) ? intval($_GET['preview_id']) : null);

    if ($product_id) {
        // Fetch all products from the API
        $products = get_products_from_fakestore_api();

        // Only print the product ID from the URL if DEBUG_MODE is true
        if (DEBUG_MODE) {
            echo 'Product ID from URL: ' . $product_id . '<br>';
        }

        // Loop through products to find the matching one
        foreach ($products as $product) {
            // Only compare product IDs if DEBUG_MODE is true
            if (DEBUG_MODE) {
                echo 'Comparing product ID: ' . $product->id . ' with URL ID: ' . $product_id . '<br>';
            }

            // Compare the product's id with the product_id from the URL
            if ($product->id == $product_id) {
                // If found, output product details
                ob_start();
                ?>
                <div class="single-fakestore-product">
                    <img src="<?php echo esc_url($product->image); ?>" alt="<?php echo esc_attr($product->title); ?>" />
                    <h1><?php echo esc_html($product->title); ?></h1>
                    <p><?php echo esc_html($product->description); ?></p>
                    <p>Category: <?php echo esc_html($product->category); ?></p>
                    <p>Price: $<?php echo esc_html($product->price); ?></p>
                    <p>Rating: <?php echo esc_html($product->rating->rate); ?> (<?php echo esc_html($product->rating->count); ?> reviews)</p>
                </div>
                <?php
                // Output the content and return
                return ob_get_clean(); // Return the output for the matched product
            }
        }
    }

    // If no product_id or product not found
    return '<p>Product not found.</p>';
}

// Add shortcode for single product page
add_shortcode('fakestore_single_product', 'display_single_fakestore_product');

