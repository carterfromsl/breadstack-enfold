<?php
/*
Plugin Name: Breadstack Enfold Integration
Description: Enfold theme integration enhancements for Breadstack WooCommerce.
Version: 1.0.3
Author: StratLab Marketing
Author URI: https://strategylab.ca/
Text Domain: breadstack-enfold
Requires at least: 6.0
Requires PHP: 7.0
Update URI: https://github.com/carterfromsl/breadstack-enfold/
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Connect with the StratLab Auto-Updater for plugin updates
add_action('plugins_loaded', function() {
    if (class_exists('StratLabUpdater')) {
        if (!function_exists('get_plugin_data')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        $plugin_file = __FILE__;
        $plugin_data = get_plugin_data($plugin_file);

        do_action('stratlab_register_plugin', [
            'slug' => plugin_basename($plugin_file),
            'repo_url' => 'https://api.github.com/repos/carterfromsl/breadstack-enfold/releases/latest',
            'version' => $plugin_data['Version'], 
            'name' => $plugin_data['Name'],
            'author' => $plugin_data['Author'],
            'homepage' => $plugin_data['PluginURI'],
            'description' => $plugin_data['Description'],
            'access_token' => '', // Add if needed for private repo
        ]);
    }
});

// Enqueue general JS and CSS files
function bs_enfold_enqueue_scripts() {
  wp_enqueue_style('bs-enfold-frontend-styles', plugin_dir_url(__FILE__) . ' breadstack-enfold.css');
  wp_enqueue_script('bs-enfold-frontend-script', plugin_dir_url(__FILE__) . 'breadstack-enfold.js', array('jquery'), null, true);
}
add_action('wp_enqueue_scripts', 'bs_enfold_enqueue_scripts');

// Create Admin Settings Page
add_action('admin_menu', 'breadstack_enfold_add_admin_page');

function breadstack_enfold_add_admin_page() {
    add_options_page(
        'Breadstack Enfold Settings',
        'Breadstack Enfold Settings',
        'manage_options',
        'breadstack-enfold-settings',
        'breadstack_enfold_settings_page'
    );
}

function breadstack_enfold_settings_page() {
    ?>
    <div class="wrap">
        <h1>Breadstack Enfold Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('breadstack_enfold_settings_group');
            do_settings_sections('breadstack-enfold-settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Register Settings
add_action('admin_init', 'breadstack_enfold_register_settings');

function breadstack_enfold_register_settings() {
    register_setting('breadstack_enfold_settings_group', 'breadstack_enfold_header_icon_enabled');
    register_setting('breadstack_enfold_settings_group', 'breadstack_enfold_account_url');
    register_setting('breadstack_enfold_settings_group', 'breadstack_enfold_account_title');
	register_setting('breadstack_enfold_settings_group', 'breadstack_enfold_custom_header_content');
	register_setting('breadstack_enfold_settings_group', 'breadstack_enfold_footer_branding_content');

    add_settings_section('breadstack_enfold_main_section', 'Main Settings', null, 'breadstack-enfold-settings');

    add_settings_field(
        'breadstack_enfold_header_icon_enabled',
        'Enable Header Account Icon',
        'breadstack_enfold_checkbox_callback',
        'breadstack-enfold-settings',
        'breadstack_enfold_main_section',
        ['label_for' => 'breadstack_enfold_header_icon_enabled']
    );

    add_settings_field(
        'breadstack_enfold_account_url',
        'Account Page URL',
        'breadstack_enfold_url_field_callback',
        'breadstack-enfold-settings',
        'breadstack_enfold_main_section',
        ['label_for' => 'breadstack_enfold_account_url']
    );

    add_settings_field(
        'breadstack_enfold_account_title',
        'Account Page Title',
        'breadstack_enfold_title_field_callback',
        'breadstack-enfold-settings',
        'breadstack_enfold_main_section',
        ['label_for' => 'breadstack_enfold_account_title']
    );
	
	add_settings_field(
        'breadstack_enfold_custom_header_content',
        'Custom Header Content',
        'breadstack_enfold_textarea_field_callback',
        'breadstack-enfold-settings',
        'breadstack_enfold_main_section',
        ['label_for' => 'breadstack_enfold_custom_header_content']
    );
	add_settings_field(
        'breadstack_enfold_footer_branding_content',
        'Footer Branding',
        'breadstack_enfold_footer_textarea_field_callback',
        'breadstack-enfold-settings',
        'breadstack_enfold_main_section',
        ['label_for' => 'breadstack_enfold_footer_branding_content']
    );
}

function breadstack_enfold_checkbox_callback($args) {
    $value = get_option($args['label_for'], 0);
    echo '<input type="checkbox" id="' . esc_attr($args['label_for']) . '" name="' . esc_attr($args['label_for']) . '" value="1"' . checked(1, $value, false) . '/>';
}

function breadstack_enfold_url_field_callback($args) {
    $value = get_option($args['label_for'], '/account/');
    echo '<input type="text" id="' . esc_attr($args['label_for']) . '" name="' . esc_attr($args['label_for']) . '" value="' . esc_attr($value) . '" />';
}

function breadstack_enfold_title_field_callback($args) {
    $value = get_option($args['label_for'], 'My Account');
    echo '<input type="text" id="' . esc_attr($args['label_for']) . '" name="' . esc_attr($args['label_for']) . '" value="' . esc_attr($value) . '" />';
}

function breadstack_enfold_textarea_field_callback($args) {
    $value = get_option($args['label_for'], '');
    echo '<textarea id="' . esc_attr($args['label_for']) . '" name="' . esc_attr($args['label_for']) . '" rows="5" cols="50">' . esc_textarea($value) . '</textarea>';
}

function breadstack_enfold_footer_textarea_field_callback($args) {
    $value = get_option($args['label_for'], '');
    echo '<textarea id="' . esc_attr($args['label_for']) . '" name="' . esc_attr($args['label_for']) . '" rows="5" cols="50">' . esc_textarea($value) . '</textarea>';
}

// Hook into the Enfold theme's header output
add_action('wp_head', 'breadstack_enfold_add_account_icon', 20);

function breadstack_enfold_add_account_icon() {
    if (!get_option('breadstack_enfold_header_icon_enabled', 0)) {
        return;
    }

    $account_url = esc_url(get_option('breadstack_enfold_account_url', '/account/'));
    $account_title = esc_html(get_option('breadstack_enfold_account_title', 'My Account'));

    // Add the account icon HTML
    add_action('ava_main_header', function () use ($account_url, $account_title) {
        echo '<a title="' . $account_title . '" class="wc-account-icon" href="' . $account_url . '">
                <span class="account-label">' . $account_title . '</span>
              </a>';
    });
	// Insert Custom Header Content
    $custom_content = get_option('breadstack_enfold_custom_header_content', '');
    if (!empty($custom_content)) {
        add_action('ava_main_header', function () use ($custom_content) {
            echo do_shortcode('<div class="breadstack-header-content">' . $custom_content . '</div>');
        });
    }
}

add_action('wp_footer', 'add_current_location_class_from_cookie_script');

function add_current_location_class_from_cookie_script() {
    ?>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Retrieve the user_selected_location cookie
            const selectedLocationCookie = document.cookie
                .split('; ')
                .find(row => row.startsWith('user_selected_location='));

            if (selectedLocationCookie) {
                // Parse the cookie value (assumes JSON format)
                let selectedLocationData;
                try {
                    selectedLocationData = JSON.parse(decodeURIComponent(selectedLocationCookie.split('=')[1]));
                } catch (e) {
                    console.error('Failed to parse user_selected_location cookie:', e);
                    return;
                }

                const activeSlug = selectedLocationData.slug;

                if (activeSlug) {
                    // Add the "current-location" class to the matching .cova-store
                    const storeElements = document.querySelectorAll('.cova-store input[type="radio"]');
                    storeElements.forEach(function (input) {
                        const parent = input.closest('.cova-store');
                        if (parent && input.id === activeSlug) {
                            parent.classList.add('current-location');
                        }
                    });
                }
            }

            // Handle label clicks to dynamically update the "current-location" class
            document.querySelectorAll('.cova-store label').forEach(function (label) {
                label.addEventListener('click', function () {
                    // Remove "current-location" class from all .cova-store elements
                    document.querySelectorAll('.cova-store').forEach(function (store) {
                        store.classList.remove('current-location');
                    });

                    // Add "current-location" class to the parent .cova-store of the clicked label
                    const parentStore = label.closest('.cova-store');
                    if (parentStore) {
                        parentStore.classList.add('current-location');
                    }
                });
            });
        });
    </script>
    <?php
}

add_action('wp_footer', 'breadstack_enfold_footer_branding');

function breadstack_enfold_footer_branding() {
    $footer_content = get_option('breadstack_enfold_footer_branding_content', '');

    if (!empty($footer_content)) {
        echo '<div class="socket-spacer"></div><footer class="breadstack-socket">' . do_shortcode($footer_content) . '</footer>';
    }
}

function enqueue_sidebar_toggle_script() {
    if (is_woocommerce()) {
        ?>
        <script>
            document.addEventListener("DOMContentLoaded", () => {
                const searchButton = document.querySelector("#search-button");
                const sidebar = document.querySelector(".sidebar");

                if (searchButton && sidebar) {
                    searchButton.addEventListener("click", () => {
                        // Add the 'active' class to the sidebar
                        sidebar.classList.add("active");

                        // Check if a search-close button already exists
                        let closeButton = sidebar.querySelector(".search-close");
                        if (!closeButton) {
                            // Create a new search-close button
                            closeButton = document.createElement("button");
                            closeButton.classList.add("search-close");
                            closeButton.textContent = "Close";
                            sidebar.appendChild(closeButton);

                            // Add event listener to the search-close button
                            closeButton.addEventListener("click", () => {
                                sidebar.classList.remove("active");
                            });
                        }
                    });
                }
            });
        </script>
        <?php
    }
}
add_action('wp_footer', 'enqueue_sidebar_toggle_script');

function insert_dynamic_query_script() {
    if (is_woocommerce()) {
        ?>
        <script>
            document.addEventListener("DOMContentLoaded", () => {
				// Function to get URL query parameters as an object
				function getQueryParams() {
					const params = new URLSearchParams(window.location.search);
					return {
						brand: params.get("bs-brand"),
						plantType: params.get("bs-plant-type"),
						size: params.get("bs-size-sl"),
					};
				}

				// Get the query parameters
				const { brand, plantType, size } = getQueryParams();

				// Select the target element
				const contentWrapper = document.querySelector(".template-shop .entry-content-wrapper");

				// Check if the wrapper exists and we have valid query parameters
				if (contentWrapper && (brand || plantType || size)) {
					// Create the wrapping div
					const wrapper = document.createElement("div");
					wrapper.classList.add("bs-active-filters-wrap");

					// Create the title
					const title = document.createElement("h4");
					title.textContent = "Active Filters";
					wrapper.appendChild(title);

					// Create the <ul> element
					const ul = document.createElement("ul");
					ul.classList.add("bs-active-filters");

					// Append list items for each query parameter
					if (brand) {
						const li = document.createElement("li");
						li.innerHTML = `<b>Brand:</b> <em>${brand}</em>`;
						ul.appendChild(li);
					}
					if (plantType) {
						const li = document.createElement("li");
						li.innerHTML = `<b>Plant Type:</b> <em>${plantType}</em>`;
						ul.appendChild(li);
					}
					if (size) {
						const li = document.createElement("li");
						li.innerHTML = `<b>Size:</b> <em>${size}g</em>`;
						ul.appendChild(li);
					}

					// Add the <ul> to the wrapper
					wrapper.appendChild(ul);

					// Create the "Clear Filters" button
					const clearButton = document.createElement("button");
					clearButton.textContent = "Clear Filters";
					clearButton.classList.add("bs-clear-filters");
					clearButton.addEventListener("click", function () {
						// Trigger a click on the ".bs_filter_reset label" element
						const resetLabel = document.querySelector(".bs_filter_reset label");
						if (resetLabel) {
							resetLabel.click();
						} else {
							console.warn("No '.bs_filter_reset label' element found.");
						}
					});

					// Append the button to the wrapper
					wrapper.appendChild(clearButton);

					// Prepend the wrapper to the content wrapper
					contentWrapper.insertBefore(wrapper, contentWrapper.firstChild);
				}
			});
        </script>
        <?php
    }
}
add_action('wp_footer', 'insert_dynamic_query_script');

?>
