<?php
/**
 * Template Handler Class
 *
 * @package CI_vs_RM
 * @since 2.0.2
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class CIVSRM_Template {
    
    /**
     * Render the template content
     *
     * @param bool $show_filters Whether to show category filters
     */
    public static function render($show_filters = true) {
        // Get cached data or fetch fresh data
        $civsrm_data = get_transient('civsrm_optimized_data');
        
        if (false === $civsrm_data) {
            $civsrm_data = CIVSRM_Plugin::get_optimized_data();
            // Cache for 1 hour
            set_transient('civsrm_optimized_data', $civsrm_data, HOUR_IN_SECONDS);
        }
        
        if (empty($civsrm_data['categories'])) {
            echo '<p>' . __('No CIVSRM categories found.', 'ci-vs-rm') . '</p>';
            return;
        }
        
        self::render_content($civsrm_data, $show_filters);
    }
    
    /**
     * Render the optimized content
     *
     * @param array $data The organized data
     * @param bool $show_filters Whether to show category filters
     */
    private static function render_content($data, $show_filters = true) {
        $categories = $data['categories'];
        $items = $data['items'];
        ?>
        
        <div class="civsrm-container">
            
            <div class="title-container">
                <h1 class="elementor-heading-title elementor-size-default">New York Sales and Use Tax Handbook For Contractors</h1>
                <div class="elementor-element elementor-widget elementor-widget-heading">
                    <div class="elementor-widget-container">
                        <h2 class="elementor-heading-title elementor-size-default">Capital Improvement vs Repair and Maintenance</h2>
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
                    continue; // Skip empty categories
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
}
