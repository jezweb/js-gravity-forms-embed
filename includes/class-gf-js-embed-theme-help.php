<?php
/**
 * Theme Help System class
 *
 * @package GravityFormsJSEmbed
 */

if (!defined('ABSPATH')) {
    exit;
}

class GF_JS_Embed_Theme_Help {
    
    private static $instance = null;
    
    /**
     * Help content for each control type
     */
    private $help_content = [
        'colors' => [
            'title' => 'Color Settings',
            'description' => 'Customize the color scheme of your forms to match your brand.',
            'items' => [
                '--gf-primary-color' => [
                    'label' => 'Primary Color',
                    'help' => 'The main brand color used for buttons, links, and focus states.',
                    'tips' => [
                        'Use a color that provides good contrast with white text',
                        'Consider your brand guidelines when selecting this color',
                        'Test with color blindness simulators for accessibility'
                    ]
                ],
                '--gf-text-color' => [
                    'label' => 'Text Color',
                    'help' => 'The default color for form text and labels.',
                    'tips' => [
                        'Ensure at least 4.5:1 contrast ratio with background',
                        'Darker colors generally provide better readability',
                        'Consider using #333 or darker for optimal readability'
                    ]
                ],
                '--gf-bg-color' => [
                    'label' => 'Background Color',
                    'help' => 'The main background color for form containers.',
                    'tips' => [
                        'Light backgrounds work best for most forms',
                        'Ensure good contrast with all text elements',
                        'Consider using subtle off-white colors for softer appearance'
                    ]
                ],
                '--gf-error-color' => [
                    'label' => 'Error Color',
                    'help' => 'Color used for error messages and validation feedback.',
                    'tips' => [
                        'Red is the standard color for errors',
                        'Ensure it stands out but isn\'t too harsh',
                        'Consider colorblind users - add icons for clarity'
                    ]
                ],
                '--gf-success-color' => [
                    'label' => 'Success Color',
                    'help' => 'Color used for success messages and positive feedback.',
                    'tips' => [
                        'Green is commonly used for success states',
                        'Should be visually distinct from error color',
                        'Test contrast with both light and dark backgrounds'
                    ]
                ]
            ]
        ],
        'typography' => [
            'title' => 'Typography Settings',
            'description' => 'Control the fonts and text appearance in your forms.',
            'items' => [
                '--gf-font-family' => [
                    'label' => 'Font Family',
                    'help' => 'The primary font used throughout the form.',
                    'tips' => [
                        'Use web-safe fonts for better compatibility',
                        'Consider loading custom fonts via your theme',
                        'Sans-serif fonts generally work best for forms'
                    ]
                ],
                '--gf-font-size-base' => [
                    'label' => 'Base Font Size',
                    'help' => 'The default font size for form text.',
                    'tips' => [
                        '16px minimum recommended for mobile devices',
                        'Larger sizes improve readability',
                        'Consider your target audience when setting size'
                    ]
                ],
                '--gf-line-height-base' => [
                    'label' => 'Line Height',
                    'help' => 'The spacing between lines of text.',
                    'tips' => [
                        '1.5 is a good default for readability',
                        'Increase for better readability in long text',
                        'Adjust based on your font choice'
                    ]
                ]
            ]
        ],
        'spacing' => [
            'title' => 'Spacing & Layout',
            'description' => 'Adjust the spacing and padding throughout your forms.',
            'items' => [
                '--gf-field-margin' => [
                    'label' => 'Field Margin',
                    'help' => 'Space between form fields.',
                    'tips' => [
                        'More space improves form scanability',
                        '20-30px is typically comfortable',
                        'Consider mobile screens when setting spacing'
                    ]
                ],
                '--gf-input-padding' => [
                    'label' => 'Input Padding',
                    'help' => 'Internal spacing within input fields.',
                    'tips' => [
                        'Adequate padding improves clickability',
                        'Consider touch targets on mobile (44px minimum)',
                        'Balance aesthetics with usability'
                    ]
                ]
            ]
        ],
        'design' => [
            'title' => 'Design Elements',
            'description' => 'Fine-tune visual design elements like borders and shadows.',
            'items' => [
                '--gf-border-radius-md' => [
                    'label' => 'Border Radius',
                    'help' => 'Roundness of corners on inputs and buttons.',
                    'tips' => [
                        '4-8px gives a modern, soft appearance',
                        '0px for sharp, professional look',
                        'Match your overall site design aesthetic'
                    ]
                ],
                '--gf-shadow-md' => [
                    'label' => 'Shadow Effects',
                    'help' => 'Drop shadows for depth and hierarchy.',
                    'tips' => [
                        'Subtle shadows add depth without distraction',
                        'Use consistently throughout the form',
                        'Consider removing on dark themes'
                    ]
                ]
            ]
        ]
    ];
    
    /**
     * Keyboard shortcuts
     */
    private $keyboard_shortcuts = [
        'general' => [
            'title' => 'General Shortcuts',
            'shortcuts' => [
                'Ctrl/Cmd + S' => 'Save current theme',
                'Ctrl/Cmd + Z' => 'Undo last change',
                'Ctrl/Cmd + Y' => 'Redo last change',
                'Esc' => 'Close dialogs and panels'
            ]
        ],
        'navigation' => [
            'title' => 'Navigation',
            'shortcuts' => [
                'Tab' => 'Move to next control',
                'Shift + Tab' => 'Move to previous control',
                'Arrow Keys' => 'Navigate within controls',
                'Enter' => 'Apply changes'
            ]
        ],
        'theme_management' => [
            'title' => 'Theme Management',
            'shortcuts' => [
                'Ctrl/Cmd + N' => 'Create new theme',
                'Ctrl/Cmd + D' => 'Duplicate selected theme',
                'Delete' => 'Delete selected theme (with confirmation)',
                'Ctrl/Cmd + E' => 'Export selected themes'
            ]
        ]
    ];
    
    /**
     * FAQ items
     */
    private $faq_items = [
        [
            'question' => 'How do I create a dark theme?',
            'answer' => 'Start with the "Dark" predefined theme and customize from there. Key changes include setting a dark background color (#1a1a1a or similar), light text color (#ffffff or #f0f0f0), and adjusting border colors for visibility.',
            'category' => 'themes'
        ],
        [
            'question' => 'Why are my color changes not showing?',
            'answer' => 'Make sure to click "Apply Changes" after making modifications. Also check that your theme is selected as the active theme. Clear your browser cache if changes still don\'t appear.',
            'category' => 'troubleshooting'
        ],
        [
            'question' => 'Can I use custom fonts?',
            'answer' => 'Yes! Enter any valid CSS font-family value. For custom web fonts, make sure they are loaded on your site before using them in the theme customizer.',
            'category' => 'typography'
        ],
        [
            'question' => 'How do I ensure my theme is accessible?',
            'answer' => 'Use the built-in contrast checker to ensure text is readable. Aim for at least 4.5:1 contrast ratio for normal text and 3:1 for large text. Test your theme with screen readers and keyboard navigation.',
            'category' => 'accessibility'
        ],
        [
            'question' => 'Can I share themes between sites?',
            'answer' => 'Yes! Use the export feature to download themes as JSON files, then import them on another site. You can also use the share feature to generate temporary URLs for theme sharing.',
            'category' => 'sharing'
        ],
        [
            'question' => 'What happens to my custom themes during updates?',
            'answer' => 'Custom themes are stored in the WordPress database and are preserved during plugin updates. However, we recommend regularly exporting your themes as backups.',
            'category' => 'maintenance'
        ]
    ];
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        add_action('wp_ajax_gf_js_embed_get_help_content', [$this, 'ajax_get_help_content']);
        add_filter('gf_js_embed_theme_customizer_localize', [$this, 'add_help_data']);
    }
    
    /**
     * Get help content for a specific topic
     */
    public function get_help_content($topic = null, $subtopic = null) {
        if (!$topic) {
            return $this->get_all_help_content();
        }
        
        if (isset($this->help_content[$topic])) {
            if ($subtopic && isset($this->help_content[$topic]['items'][$subtopic])) {
                return $this->help_content[$topic]['items'][$subtopic];
            }
            return $this->help_content[$topic];
        }
        
        return null;
    }
    
    /**
     * Get all help content organized by category
     */
    public function get_all_help_content() {
        return [
            'controls' => $this->help_content,
            'shortcuts' => $this->keyboard_shortcuts,
            'faq' => $this->organize_faq_by_category(),
            'getting_started' => $this->get_getting_started_content()
        ];
    }
    
    /**
     * Get getting started content
     */
    private function get_getting_started_content() {
        return [
            'title' => 'Getting Started with Theme Customizer',
            'steps' => [
                [
                    'title' => '1. Choose a Starting Point',
                    'content' => 'Select a predefined theme that closely matches your desired look, or start with the default theme.',
                    'icon' => 'dashicons-art'
                ],
                [
                    'title' => '2. Customize Colors',
                    'content' => 'Adjust the color scheme to match your brand. Start with the primary color and work your way through the palette.',
                    'icon' => 'dashicons-admin-appearance'
                ],
                [
                    'title' => '3. Adjust Typography',
                    'content' => 'Set your preferred fonts and sizes. Remember to test readability on different devices.',
                    'icon' => 'dashicons-editor-textcolor'
                ],
                [
                    'title' => '4. Fine-tune Spacing',
                    'content' => 'Adjust margins and padding to create the right amount of breathing room in your forms.',
                    'icon' => 'dashicons-align-center'
                ],
                [
                    'title' => '5. Save Your Theme',
                    'content' => 'Give your theme a memorable name and save it. You can always come back and make adjustments later.',
                    'icon' => 'dashicons-saved'
                ]
            ],
            'tips' => [
                'Use the live preview to see changes in real-time',
                'Export your themes regularly for backup',
                'Test your themes on different devices and browsers',
                'Consider creating multiple themes for different use cases'
            ]
        ];
    }
    
    /**
     * Organize FAQ items by category
     */
    private function organize_faq_by_category() {
        $organized = [];
        
        foreach ($this->faq_items as $item) {
            $category = $item['category'];
            if (!isset($organized[$category])) {
                $organized[$category] = [
                    'title' => ucfirst(str_replace('_', ' ', $category)),
                    'items' => []
                ];
            }
            $organized[$category]['items'][] = $item;
        }
        
        return $organized;
    }
    
    /**
     * AJAX handler for getting help content
     */
    public function ajax_get_help_content() {
        check_ajax_referer('gf_js_embed_theme_customizer', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'gf-js-embed'));
        }
        
        $topic = sanitize_text_field($_POST['topic'] ?? '');
        $subtopic = sanitize_text_field($_POST['subtopic'] ?? '');
        
        $content = $this->get_help_content($topic, $subtopic);
        
        if ($content) {
            wp_send_json_success($content);
        } else {
            wp_send_json_error(__('Help content not found', 'gf-js-embed'));
        }
    }
    
    /**
     * Add help data to customizer localization
     */
    public function add_help_data($data) {
        $data['help'] = [
            'available' => true,
            'shortcuts' => $this->keyboard_shortcuts,
            'tooltips' => $this->get_inline_tooltips()
        ];
        
        return $data;
    }
    
    /**
     * Get inline tooltips for controls
     */
    private function get_inline_tooltips() {
        $tooltips = [];
        
        foreach ($this->help_content as $category => $category_data) {
            foreach ($category_data['items'] as $variable => $item) {
                $tooltips[$variable] = $item['help'];
            }
        }
        
        return $tooltips;
    }
    
    /**
     * Generate contextual help HTML
     */
    public function render_help_button($context = '') {
        return sprintf(
            '<button type="button" class="gf-theme-help-trigger" data-help-context="%s" title="%s">
                <span class="dashicons dashicons-editor-help"></span>
                <span class="screen-reader-text">%s</span>
            </button>',
            esc_attr($context),
            esc_attr__('Get help for this setting', 'gf-js-embed'),
            esc_html__('Help', 'gf-js-embed')
        );
    }
    
    /**
     * Get video tutorial data
     */
    public function get_video_tutorials() {
        return [
            'getting_started' => [
                'title' => 'Getting Started with Theme Customizer',
                'duration' => '3:45',
                'url' => '', // Placeholder for actual video URL
                'thumbnail' => ''
            ],
            'creating_themes' => [
                'title' => 'Creating Custom Themes',
                'duration' => '5:20',
                'url' => '',
                'thumbnail' => ''
            ],
            'advanced_features' => [
                'title' => 'Advanced Theme Features',
                'duration' => '7:15',
                'url' => '',
                'thumbnail' => ''
            ]
        ];
    }
    
    /**
     * Get theme examples with use cases
     */
    public function get_theme_examples() {
        return [
            'corporate' => [
                'title' => 'Corporate Forms',
                'description' => 'Professional themes suitable for business websites',
                'examples' => ['Modern Business', 'Professional', 'Enterprise']
            ],
            'creative' => [
                'title' => 'Creative Designs',
                'description' => 'Vibrant themes for creative industries',
                'examples' => ['Gradient Dreams', 'Creative Agency', 'Artistic']
            ],
            'minimal' => [
                'title' => 'Minimalist Approach',
                'description' => 'Clean, simple themes with focus on content',
                'examples' => ['Ultra Minimal', 'Clean Slate', 'Zen']
            ],
            'accessibility' => [
                'title' => 'Accessibility First',
                'description' => 'Themes designed with accessibility in mind',
                'examples' => ['High Contrast', 'WCAG Compliant', 'Reader Friendly']
            ]
        ];
    }
}