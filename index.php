<?php
/*
Plugin Name: Library
Description: Make library from custom post type books.
Author: MS
*/

// Register Custom Post Type
function custom_books_post_type() {
    $labels = array(
        'name'                  => _x( 'Books', 'Post Type General Name', 'text_domain' ),
        'singular_name'         => _x( 'Book', 'Post Type Singular Name', 'text_domain' ),
        'menu_name'             => __( 'Books', 'text_domain' ),
        'public'                => true,
        'has_archive'           => true,
        'supports'              => array( 'title', 'thumbnail' ),
    );
    $args = array(
        'labels'                => $labels,
        'public'                => true,
        'hierarchical'          => false,
        'has_archive'           => true,
        'supports'              => array( 'title', 'thumbnail' ),
        'menu_icon'             => 'dashicons-book-alt',
        'register_meta_box_cb'  => 'custom_books_meta_box',
    );
    register_post_type( 'books', $args );
}
add_action( 'init', 'custom_books_post_type' );

// Callback function for custom fields
function custom_books_meta_box( $post ) {
    add_meta_box(
        'custom_books_meta_box',
        'Custom Fields',
        'custom_books_meta_box_callback',
        'books',
        'normal',
        'high'
    );
}

// Custom Fields Callback
function custom_books_meta_box_callback( $post ) {
    $post_content = get_post_meta( $post->ID, '_custom_books_post_content', true );
    $tags = get_post_meta( $post->ID, '_custom_books_tags', true );
    $category = get_post_meta( $post->ID, '_custom_books_category', true );
    ?>

    <div class="wrap book-info-wrap">
        <label for="custom_books_post_content">Post Content:</label>
        <textarea id="custom_books_post_content" name="custom_books_post_content"><?php echo esc_textarea( $post_content ); ?></textarea>

        <label for="custom_books_tags">Tags:</label>
        <input type="text" id="custom_books_tags" name="custom_books_tags" value="<?php echo esc_attr( $tags ); ?>">

        <label for="custom_books_category">Category:</label>
        <input type="text" id="custom_books_category" name="custom_books_category" value="<?php echo esc_attr( $category ); ?>">
    </div>


    <link rel="stylesheet" href="<?php echo plugins_url('style.css', __FILE__); ?>">

    <?php
}

// Save Custom Fields
function custom_books_save_meta_box( $post_id ) {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    update_post_meta( $post_id, '_custom_books_post_title', sanitize_text_field( $_POST['custom_books_post_title'] ) );
    update_post_meta( $post_id, '_custom_books_post_content', wp_kses_post( $_POST['custom_books_post_content'] ) );
    update_post_meta( $post_id, '_custom_books_tags', sanitize_text_field( $_POST['custom_books_tags'] ) );
    update_post_meta( $post_id, '_custom_books_category', sanitize_text_field( $_POST['custom_books_category'] ) );
}
add_action('save_post_books', 'custom_books_save_meta_box');

// Add Admin Menu
function custom_books_admin_menu() {
    add_menu_page(
        'Books Settings',
        'Books Settings',
        'manage_options',
        'books-settings',
        'custom_books_settings_page',
        'dashicons-admin-generic',
        30
    );
}
add_action( 'admin_menu', 'custom_books_admin_menu' );

// Admin Settings Page
function custom_books_settings_page() {
    // Save settings if form is submitted
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['save_books_settings'])) {
            $books_display_count = isset($_POST['books_display_count']) ? intval($_POST['books_display_count']) : 10;
            $books_footer_text = isset($_POST['books_footer_text']) ? sanitize_text_field($_POST['books_footer_text']) : '';

            update_option('books_display_count', $books_display_count);
            update_option('books_footer_text', $books_footer_text);
        }
    }

    // Output the settings form
    ?>
    <div class="wrap books-settings-wrap">
        <h2>Books Settings</h2>
        <form id="books-import-form" method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('custom_books_import_nonce', 'security'); ?>

            <label for="books_csv_file">Import CSV</label>
            <input type="file" name="books_csv_file" accept=".csv" />
            <p class="description">Upload a CSV file with columns: post_title, post_content, tag, category</p>

            <button id="import-csv-button" class="button button-primary">Import CSV</button>

            <label for="books_display_count">Number of Books to Display</label>
            <input type="number" name="books_display_count" class="field" value="<?php echo esc_attr(get_option('books_display_count', 10)); ?>" />

            <label for="books_footer_text">Footer Text</label>
            <textarea name="books_footer_text" class="field"><?php echo esc_textarea(get_option('books_footer_text', '')); ?></textarea>

            <button type="submit" class="button button-primary" name="save_books_settings">Save Changes</button>

        </form>
    </div>

    <link rel="stylesheet" href="<?php echo plugins_url('style.css', __FILE__); ?>">

    <script>
        jQuery(document).ready(function($) {
            // Save Changes Button Click Event
            $('#save-changes-button').on('click', function(e) {
                e.preventDefault();

                var booksDisplayCount = $('#books_display_count').val();
                var booksFooterText = $('#books_footer_text').val();

                $.ajax({
                    type: 'POST',
                    url: 'https://projekty.hotchili.pl/esperienza/wp-admin/admin-ajax.php',
                    data: {
                        action: 'custom_books_save_changes',
                        security: '<?php echo wp_create_nonce("custom_books_save_changes_nonce"); ?>',
                        books_display_count: booksDisplayCount,
                        books_footer_text: booksFooterText
                    },
                    success: function(response) {
                        console.log(ajaxUrl);
                        alert(response.success || response.error);
                    },
                    error: function(error) {
                        alert('Error occurred while saving changes.');
                    }
                });
            });


            $('#import-csv-button').on('click', function(e) {
                e.preventDefault();

                var formData = new FormData($('#books-import-form')[0]);
                formData.append('action', 'custom_books_import_csv');
                formData.append('security', '<?php echo wp_create_nonce("custom_books_import_nonce"); ?>');

                $.ajax({
                    type: 'POST',
                    url: ajaxurl,
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        alert(response.success || response.error);
                    },
                    error: function(error) {
                        alert('Error occurred during CSV import.');
                    }
                });
            });
        });
    </script>
    <?php
}

// Import CSV asynchronously using AJAX
function custom_books_import_csv_ajax() {
    check_admin_referer('custom_books_import_nonce', 'security');

    $response = array();

    if (isset($_FILES['books_csv_file'])) {
        $file = $_FILES['books_csv_file'];
        $allowed_types = array('csv');
        $file_type = pathinfo($file['name'], PATHINFO_EXTENSION);

        if (!in_array($file_type, $allowed_types)) {
            $response['error'] = 'Invalid file type. Please upload a CSV file.';
        } else {
            $file_path = $file['tmp_name'];

            $csv_data = array_map('str_getcsv', file($file_path));
            $csv_headers = array_shift($csv_data);

            // Validate CSV structure
            $required_headers = array('post_title', 'post_content', 'tag', 'category');
            if ($csv_headers != $required_headers) {
                $response['error'] = 'Invalid CSV structure. Please make sure the file has the correct columns: post_title, post_content, tag, category';
            } else {
                // Process CSV data
                foreach ($csv_data as $row) {
                    $post_title = sanitize_text_field($row[0]);
                    $post_content = wp_kses_post($row[1]);
                    $tags = array_map('sanitize_text_field', explode(',', $row[2]));
                    $category = sanitize_text_field($row[3]);

                    // Check for duplicates
                    $existing_post = get_page_by_title($post_title, OBJECT, 'books');
                    if (!$existing_post) {
                        // Create new post
                        $post_data = array(
                            'post_title'   => $post_title,
                            'post_content' => $post_content,
                            'post_type'    => 'books',
                            'post_status'  => 'publish',
                        );

                        $post_id = wp_insert_post($post_data);

                        // Add custom fields
                        if (!is_wp_error($post_id)) {
                            update_post_meta($post_id, '_custom_books_post_title', $post_title);
                            update_post_meta($post_id, '_custom_books_post_content', $post_content);
                            update_post_meta($post_id, '_custom_books_tags', implode(',', $tags));
                            update_post_meta($post_id, '_custom_books_category', $category);
                        }
                    }
                }

                $response['success'] = 'CSV file imported successfully.';
            }
        }
    } else {
        $response['error'] = 'No CSV file provided.';
    }

    wp_send_json($response);
    die();
}

add_action('wp_ajax_custom_books_import_csv', 'custom_books_import_csv_ajax');

// Register Settings
function custom_books_register_settings() {
    register_setting( 'custom_books_settings_group', 'books_display_count', 'intval' );
    register_setting( 'custom_books_settings_group', 'books_footer_text' );
    // Additional settings can be added for CSV import, handling, etc.
}
add_action( 'admin_init', 'custom_books_register_settings' );

// Shortcode to display custom posts
function custom_books_shortcode() {
    // Get the value of the books_footer_text field
    $books_display_count = get_option('books_display_count', 'intval');
    $books_footer_text = get_option('books_footer_text', '');

    $args = array(
        'post_type'      => 'books',
        'posts_per_page' => -1, // Display all posts
        'orderby'        => 'title', // Order by post title
        'order'          => 'ASC',   // Order in ascending order

    );

    $query = new WP_Query($args);

    if ($query->have_posts()) {
        ?>     <link rel="stylesheet" href="<?php echo plugins_url('style.css', __FILE__); ?>">
        <?php
        $output = '<div class="wrap library-wrap"><div class="top-panel"><h2>Library</h2></div>';

        $output .= '<div class="book-list">';
        while ($query->have_posts()) {
            $query->the_post();

            $post_title = get_post_meta(get_the_ID(), '_custom_books_post_title', true);
            $tags = get_post_meta(get_the_ID(), '_custom_books_tags', true);

            $category = get_post_meta(get_the_ID(), '_custom_books_category', true);
            $output .= '<div class="book">';
            $output .= '<div class="icon"><svg xmlns="http://www.w3.org/2000/svg" height="16" width="14" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2023 Fonticons, Inc.--><path d="M96 0C43 0 0 43 0 96V416c0 53 43 96 96 96H384h32c17.7 0 32-14.3 32-32s-14.3-32-32-32V384c17.7 0 32-14.3 32-32V32c0-17.7-14.3-32-32-32H384 96zm0 384H352v64H96c-17.7 0-32-14.3-32-32s14.3-32 32-32zm32-240c0-8.8 7.2-16 16-16H336c8.8 0 16 7.2 16 16s-7.2 16-16 16H144c-8.8 0-16-7.2-16-16zm16 48H336c8.8 0 16 7.2 16 16s-7.2 16-16 16H144c-8.8 0-16-7.2-16-16s7.2-16 16-16z"/></svg></div>';

            $output .= '<strong>' . esc_html(get_the_title()) . '</strong><br>';
            $output .= 'Tags: ' . esc_html($tags) . '<br>';
            $output .= 'Category: ' . esc_html($category);
            $output .= '</div>';

        }
        $output .= '</div>';
        $output .= '<div class="pagination"><div class="arrow left-arrow"><</div>
        <div class="pagination-numbers">
            <div class="number active">1</div>
            <div class="number">2</div>
        </div>
        <div class="arrow right-arrow">></div></div>';

        wp_reset_postdata();
    } else {
        $output = 'No books found.';
    }

    // Display the value of the books_footer_text field

    $output .= '<div class="bottom-panel"><p>' . esc_html($books_footer_text) . '</p></div>';
    $output .= '</div>';

    ?>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    var bookListContainer = document.querySelector('.book-list');
    var bookElements = bookListContainer.querySelectorAll('.book');
    var paginationNumbersContainer = document.querySelector('.pagination-numbers');
    
    var currentPage = 1;
    var startBooksPerPage = <?php echo json_encode($books_display_count); ?>;
    var booksPerPage = startBooksPerPage;
    var currentFilterLetter = null;

    function displayBooks(page) {
      var startIndex = (page - 1) * booksPerPage;
      var endIndex = startIndex + booksPerPage;

      bookElements.forEach(function (book, index) {
        var bookLetter = book.innerText.trim().charAt(0).toUpperCase();

        if ((!currentFilterLetter || currentFilterLetter === bookLetter) &&
          index >= startIndex && index < endIndex) {
          book.style.display = 'block';
        } else {
          book.style.display = 'none';
        }
      });

      updatePagination(page);
    }

    function updatePagination(page) {
      paginationNumbersContainer.innerHTML = '';

      var totalPages = Math.ceil(bookElements.length / booksPerPage);

      for (var i = 1; i <= totalPages; i++) {
        var numberElement = document.createElement('div');
        numberElement.classList.add('number');
        numberElement.innerText = i;
        numberElement.addEventListener('click', function () {
          currentPage = parseInt(this.innerText, 5);
          booksPerPage = 5;
          displayBooks(currentPage);
        });

        if (i === page) {
          numberElement.classList.add('active');
        }

        paginationNumbersContainer.appendChild(numberElement);
      }
    }

    displayBooks(currentPage);

    // Handle left arrow click
    document.querySelector('.left-arrow').addEventListener('click', function () {
      if (currentPage > 1) {
        currentPage--;

        booksPerPage = 5;
        displayBooks(currentPage);
      }
    });

    // Handle right arrow click
    document.querySelector('.right-arrow').addEventListener('click', function () {
      var totalPages = Math.ceil(bookElements.length / booksPerPage);

      if (currentPage < totalPages) {
        currentPage++;

        booksPerPage = 5;
        displayBooks(currentPage);
      }
    });
  });
</script>
    <?php

    return $output;
}
add_shortcode('library', 'custom_books_shortcode');