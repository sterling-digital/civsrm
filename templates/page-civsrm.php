<?php
/**
 * Template Name: CIVSRM Items Display
 * 
 * High-performance template for displaying CIVSRM items with WooCommerce Memberships integration
 * Provided by CI vs RM Display Plugin
 * 
 * @package CI_vs_RM
 * @since 2.0.2
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get the plugin instance for membership checking
$civsrm_plugin = CIVSRM_Plugin::get_instance();

// Check membership restrictions before rendering content
$page_id = get_the_ID();
$user_id = get_current_user_id();

// Log template access attempt
if (method_exists($civsrm_plugin, 'log_membership_decision')) {
    $civsrm_plugin->log_membership_decision('template_access_attempt', array(
        'page_id' => $page_id,
        'user_id' => $user_id,
        'template' => 'page-civsrm.php',
        'timestamp' => current_time('mysql')
    ));
}

get_header(); ?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">
        
        <?php
        // Double-check membership access at template level
        // This provides an additional layer of protection
        $should_show_content = true;
        
        // Check if WooCommerce Memberships is active and if there are restrictions
        if (class_exists('WC_Memberships') || function_exists('wc_memberships')) {
            // Check if the current page content is restricted
            if (function_exists('wc_memberships_is_post_content_restricted')) {
                $is_restricted = wc_memberships_is_post_content_restricted($page_id);
                
                if ($is_restricted) {
                    // Check if current user can view the content
                    if (function_exists('wc_memberships_user_can')) {
                        $can_access = wc_memberships_user_can($user_id, 'view', array('post' => $page_id));
                        $should_show_content = $can_access;
                        
                        // Log the access decision
                        if (method_exists($civsrm_plugin, 'log_membership_decision')) {
                            $civsrm_plugin->log_membership_decision('template_content_check', array(
                                'page_id' => $page_id,
                                'user_id' => $user_id,
                                'is_restricted' => $is_restricted,
                                'can_access' => $can_access,
                                'should_show_content' => $should_show_content
                            ));
                        }
                    } else {
                        // If we can't check user permissions, assume restricted
                        $should_show_content = false;
                        
                        if (method_exists($civsrm_plugin, 'log_membership_decision')) {
                            $civsrm_plugin->log_membership_decision('template_content_restricted', array(
                                'page_id' => $page_id,
                                'user_id' => $user_id,
                                'reason' => 'cannot_check_user_permissions'
                            ));
                        }
                    }
                }
            }
        }
        
        if ($should_show_content) {
            // User has access - show the CIVSRM content
            if (method_exists($civsrm_plugin, 'log_membership_decision')) {
                $civsrm_plugin->log_membership_decision('template_content_allowed', array(
                    'page_id' => $page_id,
                    'user_id' => $user_id
                ));
            }
            
            // Use the template handler to render content with filters enabled
            CIVSRM_Template::render(true);
            
        } else {
            // User doesn't have access - show restriction message
            if (method_exists($civsrm_plugin, 'log_membership_decision')) {
                $civsrm_plugin->log_membership_decision('template_content_blocked', array(
                    'page_id' => $page_id,
                    'user_id' => $user_id,
                    'reason' => 'membership_restriction'
                ));
            }
            
            // Let WooCommerce Memberships handle the restriction message
            // This ensures consistent messaging with the rest of the site
            if (function_exists('wc_memberships_get_user_content_restriction_message')) {
                $restriction_message = wc_memberships_get_user_content_restriction_message($page_id, $user_id);
                if ($restriction_message) {
                    echo '<div class="wc-memberships-restriction-message">';
                    echo wp_kses_post($restriction_message);
                    echo '</div>';
                } else {
                    // Fallback message if WooCommerce Memberships doesn't provide one
                    echo '<div class="civsrm-restriction-message">';
                    echo '<p>' . esc_html__('This content is restricted. Please check your membership status.', 'ci-vs-rm') . '</p>';
                    echo '</div>';
                }
            } else {
                // Fallback for when WooCommerce Memberships functions aren't available
                echo '<div class="civsrm-restriction-message">';
                echo '<p>' . esc_html__('This content is restricted. Please check your membership status.', 'ci-vs-rm') . '</p>';
                echo '</div>';
            }
        }
        ?>
        
    </main>
</div>

<?php get_footer(); ?>
