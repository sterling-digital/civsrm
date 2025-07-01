<?php
/**
 * Main Plugin Class
 *
 * @package CI_vs_RM
 * @since 2.2.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class CIVSRM_Plugin {
    
    /**
     * Plugin instance
     *
     * @var CIVSRM_Plugin
     */
    private static $instance = null;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
        $this->load_dependencies();
    }
    
    /**
     * Get plugin instance
     *
     * @return CIVSRM_Plugin
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        
        // Template registration hooks
        add_filter('theme_page_templates', array($this, 'add_page_template'));
        add_filter('wp_insert_post_data', array($this, 'register_page_template'));
        add_filter('page_template', array($this, 'view_page_template'));
        
        // Add cache clearing hooks
        add_action('save_post', array($this, 'clear_cache_on_post_save'));
        add_action('edited_civsrm-category', array($this, 'clear_cache'));
        add_action('created_civsrm-category', array($this, 'clear_cache'));
        add_action('deleted_civsrm-category', array($this, 'clear_cache'));
        
        // Register shortcode
        add_shortcode('civsrm_display', array($this, 'display_shortcode'));
    }
    
    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        require_once CIVSRM_PLUGIN_PATH . 'includes/class-civsrm-shortcode.php';
        require_once CIVSRM_PLUGIN_PATH . 'includes/class-civsrm-template.php';
    }
    
    /**
     * Plugin initialization
     */
    public function init() {
        // Load text domain for translations
        load_plugin_textdomain('ci-vs-rm', false, dirname(CIVSRM_PLUGIN_BASENAME) . '/languages');
    }
    
    /**
     * Enqueue plugin assets
     */
    public function enqueue_assets() {
        // Only enqueue if shortcode is being used on the page or if it's the CIVSRM page
        global $post;
        
        $should_enqueue = false;
        
        // Check if shortcode is present
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'civsrm_display')) {
            $should_enqueue = true;
        }
        
        // Check if it's a page using the CIVSRM template
        if (is_page()) {
            $template_slug = get_page_template_slug();
            $custom_template = get_post_meta(get_the_ID(), '_wp_page_template', true);
            if ($template_slug === 'page-civsrm.php' || $custom_template === 'page-civsrm.php') {
                $should_enqueue = true;
            }
        }
        
        // Check if it's a page with slug 'civsrm'
        if (is_page('civsrm')) {
            $should_enqueue = true;
        }
        
        if ($should_enqueue) {
            wp_enqueue_style(
                'civsrm-styles',
                CIVSRM_PLUGIN_URL . 'assets/css/civsrm-styles.css',
                array(),
                CIVSRM_PLUGIN_VERSION
            );
            
            // Enqueue membership integration styles
            wp_enqueue_style(
                'civsrm-membership-styles',
                CIVSRM_PLUGIN_URL . 'assets/css/civsrm-membership-styles.css',
                array('civsrm-styles'),
                CIVSRM_PLUGIN_VERSION
            );
            
            wp_enqueue_script(
                'civsrm-search',
                CIVSRM_PLUGIN_URL . 'assets/js/civsrm-search.js',
                array(),
                CIVSRM_PLUGIN_VERSION,
                true
            );
            
            // Enqueue Mark.js library for text highlighting
            wp_enqueue_script(
                'markjs',
                CIVSRM_PLUGIN_URL . 'assets/js/mark.min.js',
                array(),
                '8.11.1',
                true
            );
        }
    }
    
    /**
     * Display shortcode handler
     */
    public function display_shortcode($atts) {
        $shortcode_handler = new CIVSRM_Shortcode();
        return $shortcode_handler->render($atts);
    }
    
    /**
     * Clear cache when CIVSRM posts are updated
     */
    public function clear_cache_on_post_save($post_id) {
        if (get_post_type($post_id) === 'civsrm-item') {
            $this->clear_cache();
        }
    }
    
    /**
     * Clear all CIVSRM caches
     */
    public function clear_cache() {
        delete_transient('civsrm_shortcode_data');
        delete_transient('civsrm_optimized_data');
    }
    
    /**
     * Add custom page template to the page template dropdown
     *
     * @param array $page_templates Array of page templates
     * @return array Modified array of page templates
     */
    public function add_page_template($page_templates) {
        $page_templates['page-civsrm.php'] = __('CIVSRM Items Display', 'ci-vs-rm');
        return $page_templates;
    }
    
    /**
     * Register the selected page template
     *
     * @param array $data Post data
     * @return array Modified post data
     */
    public function register_page_template($data) {
        // Create the key used for the lookup
        $cache_key = 'page_templates-' . md5(get_theme_root() . '/' . get_stylesheet());
        
        // Retrieve the cache list
        $templates = wp_get_theme()->get_page_templates();
        if (empty($templates)) {
            $templates = array();
        }
        
        // New cache, therefore remove the old one
        wp_cache_delete($cache_key, 'themes');
        
        // Now add our template to the list of templates by merging our templates
        // with the existing templates array from the cache.
        $templates = array_merge($templates, $this->get_custom_templates());
        
        // Add the modified cache to allow WordPress to pick it up for listing
        // available templates
        wp_cache_add($cache_key, $templates, 'themes', 1800);
        
        return $data;
    }
    
    /**
     * Load the custom page template
     *
     * @param string $template The path of the template to include
     * @return string The path of the template to include
     */
    public function view_page_template($template) {
        if (is_page()) {
            $page_id = get_the_ID();
            $custom_template = get_post_meta($page_id, '_wp_page_template', true);
            
            if ($custom_template === 'page-civsrm.php') {
                // Check if we should load the custom template (considering membership restrictions)
                if ($this->should_load_custom_template($page_id)) {
                    $plugin_template = CIVSRM_PLUGIN_PATH . 'templates/page-civsrm.php';
                    if (file_exists($plugin_template)) {
                        return $plugin_template;
                    }
                }
            }
        }
        
        return $template;
    }
    
    /**
     * Get custom templates
     *
     * @return array Array of custom templates
     */
    private function get_custom_templates() {
        return array(
            'page-civsrm.php' => __('CIVSRM Items Display', 'ci-vs-rm')
        );
    }
    
    /**
     * Get optimized data for display
     */
    public static function get_optimized_data() {
        global $wpdb;
        
        // Single query to get all categories
        $categories = get_terms(array(
            'taxonomy' => 'civsrm-category',
            'hide_empty' => true,
            'orderby' => 'name',
            'order' => 'ASC'
        ));
        
        if (is_wp_error($categories) || empty($categories)) {
            return array('categories' => array(), 'items' => array());
        }
        
        // Get all category IDs
        $category_ids = wp_list_pluck($categories, 'term_id');
        
        // Single optimized query to get all items with their classifications
        $items_query = "
            SELECT p.ID, p.post_title, p.post_content, pm.meta_value as classification, tt.term_id
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
            INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'civsrm_classification'
            WHERE p.post_type = 'civsrm-item'
            AND p.post_status = 'publish'
            AND tt.taxonomy = 'civsrm-category'
            AND tt.term_id IN (" . implode(',', array_map('intval', $category_ids)) . ")
            ORDER BY p.post_title ASC
        ";
        
        $items = $wpdb->get_results($items_query);
        
        // Organize items by category
        $organized_items = array();
        foreach ($items as $item) {
            $category_id = $item->term_id;
            
            if (!isset($organized_items[$category_id])) {
                $organized_items[$category_id] = array('ci' => array(), 'rm' => array());
            }
            
            $classification = strtolower($item->classification ?: '');
            if ($classification === 'capital improvement') {
                $organized_items[$category_id]['ci'][] = $item;
            } else {
                $organized_items[$category_id]['rm'][] = $item;
            }
        }
        
        return array(
            'categories' => $categories,
            'items' => $organized_items
        );
    }
    
    // ========================================
    // WooCommerce Memberships Integration Methods
    // ========================================
    
    /**
     * Check if WooCommerce Memberships plugin is active and available
     *
     * @return bool True if WooCommerce Memberships is active
     */
    private function is_woocommerce_memberships_active() {
        // Check if the main WooCommerce Memberships class exists
        return class_exists('WC_Memberships') || function_exists('wc_memberships');
    }
    
    /**
     * Determine if the custom template should be loaded based on membership restrictions
     *
     * @param int $page_id The page ID to check
     * @return bool True if custom template should be loaded, false otherwise
     */
    private function should_load_custom_template($page_id) {
        // Check if WooCommerce Memberships is active
        if (!$this->is_woocommerce_memberships_active()) {
            return true; // No restrictions if WooCommerce Memberships is not active
        }
        
        // Check if user can access the content
        if (function_exists('wc_memberships_user_can')) {
            $user_id = get_current_user_id();
            return wc_memberships_user_can($user_id, 'view', array('post' => $page_id));
        }
        
        return true; // Default to allowing access if we can't determine
    }
}
