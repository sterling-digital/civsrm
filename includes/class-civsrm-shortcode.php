<?php
/**
 * Shortcode Handler Class
 *
 * @package CI_vs_RM
 * @since 2.0.2
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class CIVSRM_Shortcode {
    
    /**
     * Render the shortcode with WooCommerce Memberships integration
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function render($atts) {
        // Parse shortcode attributes
        $atts = shortcode_atts(array(
            'title' => 'New York Sales and Use Tax Handbook For Contractors',
            'subtitle' => 'Capital Improvement vs Repair and Maintenance',
            'use_cache' => 'true',
            'show_filters' => 'false',
            'check_membership' => 'true' // New attribute to control membership checking
        ), $atts, 'civsrm_display');
        
        // Get current page and user info for membership checking
        $page_id = get_the_ID();
        $user_id = get_current_user_id();
        
        // Get plugin instance for logging
        $civsrm_plugin = CIVSRM_Plugin::get_instance();
        
        // Log shortcode access attempt
        if (method_exists($civsrm_plugin, 'log_membership_decision')) {
            $civsrm_plugin->log_membership_decision('shortcode_access_attempt', array(
                'page_id' => $page_id,
                'user_id' => $user_id,
                'shortcode_atts' => $atts,
                'timestamp' => current_time('mysql')
            ));
        }
        
        // Check membership restrictions if enabled
        if ($atts['check_membership'] === 'true') {
            $membership_check = $this->check_shortcode_access($page_id, $user_id, $civsrm_plugin);
            
            if (!$membership_check['has_access']) {
                // User doesn't have access - return restriction message
                if (method_exists($civsrm_plugin, 'log_membership_decision')) {
                    $civsrm_plugin->log_membership_decision('shortcode_access_denied', array(
                        'page_id' => $page_id,
                        'user_id' => $user_id,
                        'reason' => $membership_check['restriction_reason']
                    ));
                }
                
                return $this->get_restriction_message($page_id, $user_id);
            }
        }
        
        // User has access or membership checking is disabled - proceed with content
        if (method_exists($civsrm_plugin, 'log_membership_decision')) {
            $civsrm_plugin->log_membership_decision('shortcode_access_granted', array(
                'page_id' => $page_id,
                'user_id' => $user_id,
                'check_membership' => $atts['check_membership']
            ));
        }
        
        // Start output buffering
        ob_start();
        
        // Use caching if enabled
        if ($atts['use_cache'] === 'true') {
            $civsrm_data = get_transient('civsrm_shortcode_data');
            if (false === $civsrm_data) {
                $civsrm_data = CIVSRM_Plugin::get_optimized_data();
                set_transient('civsrm_shortcode_data', $civsrm_data, HOUR_IN_SECONDS);
            }
        } else {
            $civsrm_data = CIVSRM_Plugin::get_optimized_data();
        }
        
        if (empty($civsrm_data['categories'])) {
            return '<p>' . __('No CIVSRM categories found.', 'ci-vs-rm') . '</p>';
        }
        
        $this->render_content($civsrm_data, $atts);
        
        return ob_get_clean();
    }
    
    /**
     * Render shortcode content
     *
     * @param array $data The organized data
     * @param array $atts Shortcode attributes
     */
    private function render_content($data, $atts) {
        $categories = $data['categories'];
        $items = $data['items'];
        $show_filters = ($atts['show_filters'] === 'true');
        ?>
        
        <div class="civsrm-container">
            
            <div class="title-container">
                <h1 class="elementor-heading-title elementor-size-default"><?php echo esc_html($atts['title']); ?></h1>
                <div class="elementor-element elementor-widget elementor-widget-heading">
                    <div class="elementor-widget-container">
                        <h2 class="elementor-heading-title elementor-size-default"><?php echo esc_html($atts['subtitle']); ?></h2>
                    </div>
                </div>
            </div>
            
            <div class="civsrm-search-box">
                <input type="text" id="civsrm-search" placeholder="<?php esc_attr_e('Search items...', 'ci-vs-rm'); ?>">

                <div class="search-buttons">
                    <button type="button" id="search-btn"><?php esc_html_e('Search', 'ci-vs-rm'); ?></button>
                    <button type="button" id="reset-btn"><?php esc_html_e('Reset', 'ci-vs-rm'); ?></button>
                    <?php if ($show_filters): ?>
                        <button type="button" id="filter-btn" title="<?php esc_attr_e('Filter by categories', 'ci-vs-rm'); ?>">
                            <i class="fas fa-eye-slash"></i>
                        </button>
                    <?php endif; ?>
                </div>

                <?php if ($show_filters): ?>
                    <div id="filter-panel" class="filter-panel" style="display: none;">
                        <div class="filter-header">
                            <h4><?php esc_html_e('Filter by Categories', 'ci-vs-rm'); ?></h4>
                            <button type="button" id="clear-filters-btn"><?php esc_html_e('Clear All', 'ci-vs-rm'); ?></button>
                        </div>
                        <div class="filter-checkboxes">
                            <?php foreach ($categories as $category): ?>
                                <label class="filter-checkbox">
									<input type="checkbox" value="<?php echo esc_attr($category->term_id); ?>" data-category="<?php echo esc_attr(sanitize_title($category->name)); ?>">
									<span class="checkmark"></span>
                                    <?php echo esc_html($category->name); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div id="no-results" class="no-results" style="display: none;">
                    <p><?php esc_html_e('No items found matching your search criteria.', 'ci-vs-rm'); ?></p>
                </div>
            </div>

            <?php foreach ($categories as $category): ?>
                <?php 
                $category_items = isset($items[$category->term_id]) ? $items[$category->term_id] : array('ci' => array(), 'rm' => array());
                if (empty($category_items['ci']) && empty($category_items['rm'])) {
                    continue;
                }
                ?>

                <div class="civsrm-category-group">
                    <h3 id="<?php echo esc_attr(sanitize_title($category->name)); ?>" class="civsrm-category-name">
                        <?php echo esc_html($category->name); ?>
                    </h3>

                    <div class="civsrm-items">
                        <div class="ci-items">
                            <h4><?php esc_html_e('Capital Improvement', 'ci-vs-rm'); ?></h4>
                            <?php foreach ($category_items['ci'] as $item): ?>
                                <div class="civsrm-item" data-title="<?php echo esc_attr($item->post_title); ?>">
                                    <div class="description">
                                        <?php echo wp_kses_post(wpautop($item->post_content)); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="rm-items">
                            <h4><?php esc_html_e('Repair, Maintenance or Installation', 'ci-vs-rm'); ?></h4>
                            <?php foreach ($category_items['rm'] as $item): ?>
                                <div class="civsrm-item" data-title="<?php echo esc_attr($item->post_title); ?>">
                                    <div class="description">
                                        <?php echo wp_kses_post(wpautop($item->post_content)); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

            <?php endforeach; ?>

        </div>
        
        <?php
    }
    
    // ========================================
    // WooCommerce Memberships Integration Methods for Shortcode
    // ========================================
    
    /**
     * Check if user has access to shortcode content based on page membership restrictions
     *
     * @param int $page_id The page ID where the shortcode is used
     * @param int $user_id The user ID to check access for
     * @param CIVSRM_Plugin $plugin_instance Plugin instance for logging
     * @return array Access check results
     */
    private function check_shortcode_access($page_id, $user_id, $plugin_instance) {
        $access_result = array(
            'has_access' => true,
            'restriction_reason' => null,
            'is_wc_memberships_active' => false,
            'page_is_restricted' => false
        );
        
        // Check if WooCommerce Memberships is active
        if (!class_exists('WC_Memberships') && !function_exists('wc_memberships')) {
            $access_result['restriction_reason'] = 'no_wc_memberships';
            if (method_exists($plugin_instance, 'log_membership_decision')) {
                $plugin_instance->log_membership_decision('shortcode_no_wc_memberships', array(
                    'page_id' => $page_id,
                    'user_id' => $user_id
                ));
            }
            return $access_result; // No restrictions if WC Memberships isn't active
        }
        
        $access_result['is_wc_memberships_active'] = true;
        
        // Check if the page content is restricted
        if (function_exists('wc_memberships_is_post_content_restricted')) {
            $is_restricted = wc_memberships_is_post_content_restricted($page_id);
            $access_result['page_is_restricted'] = $is_restricted;
            
            if ($is_restricted) {
                // Page is restricted - check if user has access
                if (function_exists('wc_memberships_user_can')) {
                    $can_access = wc_memberships_user_can($user_id, 'view', array('post' => $page_id));
                    $access_result['has_access'] = $can_access;
                    
                    if (!$can_access) {
                        $access_result['restriction_reason'] = $user_id > 0 ? 'insufficient_membership' : 'not_logged_in';
                    }
                    
                    if (method_exists($plugin_instance, 'log_membership_decision')) {
                        $plugin_instance->log_membership_decision('shortcode_access_check', array(
                            'page_id' => $page_id,
                            'user_id' => $user_id,
                            'is_restricted' => $is_restricted,
                            'can_access' => $can_access,
                            'restriction_reason' => $access_result['restriction_reason']
                        ));
                    }
                } else {
                    // Can't check user permissions - assume restricted
                    $access_result['has_access'] = false;
                    $access_result['restriction_reason'] = 'cannot_check_permissions';
                    
                    if (method_exists($plugin_instance, 'log_membership_decision')) {
                        $plugin_instance->log_membership_decision('shortcode_cannot_check_permissions', array(
                            'page_id' => $page_id,
                            'user_id' => $user_id
                        ));
                    }
                }
            }
        } else {
            // Can't check if content is restricted - log this
            if (method_exists($plugin_instance, 'log_membership_decision')) {
                $plugin_instance->log_membership_decision('shortcode_cannot_check_restriction', array(
                    'page_id' => $page_id,
                    'user_id' => $user_id,
                    'message' => 'wc_memberships_is_post_content_restricted function not available'
                ));
            }
        }
        
        return $access_result;
    }
    
    /**
     * Get appropriate restriction message for shortcode
     *
     * @param int $page_id The page ID
     * @param int $user_id The user ID
     * @return string HTML restriction message
     */
    private function get_restriction_message($page_id, $user_id) {
        $message = '';
        
        // Try to get WooCommerce Memberships restriction message first
        if (function_exists('wc_memberships_get_user_content_restriction_message')) {
            $wc_message = wc_memberships_get_user_content_restriction_message($page_id, $user_id);
            if ($wc_message) {
                $message = '<div class="wc-memberships-restriction-message civsrm-shortcode-restriction">';
                $message .= wp_kses_post($wc_message);
                $message .= '</div>';
                return $message;
            }
        }
        
        // Fallback messages based on user status
        if ($user_id === 0) {
            // User not logged in
            $message = '<div class="civsrm-shortcode-restriction civsrm-login-required">';
            $message .= '<p>' . esc_html__('This content requires membership access. Please log in to view.', 'ci-vs-rm') . '</p>';
            
            // Add login link if available
            if (function_exists('wp_login_url')) {
                $login_url = wp_login_url(get_permalink($page_id));
                $message .= '<p><a href="' . esc_url($login_url) . '" class="civsrm-login-link">';
                $message .= esc_html__('Log in here', 'ci-vs-rm') . '</a></p>';
            }
            
            $message .= '</div>';
        } else {
            // User logged in but doesn't have required membership
            $message = '<div class="civsrm-shortcode-restriction civsrm-membership-required">';
            $message .= '<p>' . esc_html__('This content is restricted to members with specific membership levels.', 'ci-vs-rm') . '</p>';
            
            // Try to provide membership upgrade link
            if (function_exists('wc_get_page_id')) {
                $membership_page_id = wc_get_page_id('myaccount');
                if ($membership_page_id > 0) {
                    $membership_url = get_permalink($membership_page_id);
                    $message .= '<p><a href="' . esc_url($membership_url) . '" class="civsrm-membership-link">';
                    $message .= esc_html__('Check your membership status', 'ci-vs-rm') . '</a></p>';
                }
            }
            
            $message .= '</div>';
        }
        
        return $message;
    }
}
