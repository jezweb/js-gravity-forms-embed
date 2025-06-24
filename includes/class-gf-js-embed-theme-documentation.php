<?php
/**
 * Theme Documentation class
 *
 * @package GravityFormsJSEmbed
 */

if (!defined('ABSPATH')) {
    exit;
}

class GF_JS_Embed_Theme_Documentation {
    
    private static $instance = null;
    
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
        add_action('wp_ajax_gf_js_embed_get_api_docs', [$this, 'ajax_get_api_docs']);
        add_action('wp_ajax_gf_js_embed_get_theme_examples', [$this, 'ajax_get_theme_examples']);
    }
    
    /**
     * Get API documentation
     */
    public function get_api_documentation() {
        return [
            'rest_endpoints' => $this->get_rest_endpoints_docs(),
            'php_hooks' => $this->get_php_hooks_docs(),
            'javascript_api' => $this->get_javascript_api_docs(),
            'theme_format' => $this->get_theme_format_docs()
        ];
    }
    
    /**
     * Get REST API endpoints documentation
     */
    private function get_rest_endpoints_docs() {
        return [
            [
                'endpoint' => '/wp-json/gf-js-embed/v1/themes',
                'method' => 'GET',
                'description' => 'Get all available themes (predefined and custom)',
                'parameters' => [],
                'response' => [
                    'predefined' => 'Array of predefined theme categories',
                    'custom' => 'Array of custom themes'
                ],
                'example' => '
GET /wp-json/gf-js-embed/v1/themes

Response:
{
    "predefined": {
        "modern": {
            "label": "Modern Themes",
            "themes": {...}
        }
    },
    "custom": {
        "my-theme": {
            "name": "My Theme",
            "variables": {...}
        }
    }
}'
            ],
            [
                'endpoint' => '/wp-json/gf-js-embed/v1/themes/save',
                'method' => 'POST',
                'description' => 'Save a custom theme',
                'parameters' => [
                    'name' => 'Theme name (required)',
                    'description' => 'Theme description (optional)',
                    'variables' => 'Theme CSS variables object (required)'
                ],
                'response' => [
                    'success' => 'Boolean indicating success',
                    'theme_name' => 'Saved theme name (may be modified for uniqueness)'
                ],
                'example' => '
POST /wp-json/gf-js-embed/v1/themes/save
Content-Type: application/json

{
    "name": "My Custom Theme",
    "description": "A beautiful custom theme",
    "variables": {
        "--gf-primary-color": "#007cba",
        "--gf-text-color": "#333333"
    }
}'
            ],
            [
                'endpoint' => '/wp-json/gf-js-embed/v1/themes/delete',
                'method' => 'POST',
                'description' => 'Delete a custom theme',
                'parameters' => [
                    'theme_name' => 'Name of theme to delete (required)'
                ],
                'response' => [
                    'success' => 'Boolean indicating success',
                    'message' => 'Success or error message'
                ]
            ],
            [
                'endpoint' => '/wp-json/gf-js-embed/v1/themes/export',
                'method' => 'POST',
                'description' => 'Export themes in various formats',
                'parameters' => [
                    'themes' => 'Array of theme names to export',
                    'format' => 'Export format: json or zip (default: json)'
                ],
                'response' => [
                    'url' => 'Download URL for exported file',
                    'filename' => 'Name of exported file'
                ]
            ]
        ];
    }
    
    /**
     * Get PHP hooks documentation
     */
    private function get_php_hooks_docs() {
        return [
            'filters' => [
                [
                    'hook' => 'gf_js_embed_theme_variables',
                    'description' => 'Filter CSS variables before they are applied',
                    'parameters' => [
                        '$variables' => 'Array of CSS variable name => value pairs',
                        '$theme_name' => 'Name of the current theme'
                    ],
                    'return' => 'Modified variables array',
                    'example' => '
add_filter("gf_js_embed_theme_variables", function($variables, $theme_name) {
    // Force a specific primary color for all themes
    $variables["--gf-primary-color"] = "#ff0000";
    return $variables;
}, 10, 2);'
                ],
                [
                    'hook' => 'gf_js_embed_predefined_themes',
                    'description' => 'Add or modify predefined themes',
                    'parameters' => [
                        '$themes' => 'Array of predefined theme categories'
                    ],
                    'return' => 'Modified themes array',
                    'example' => '
add_filter("gf_js_embed_predefined_themes", function($themes) {
    $themes["custom_category"] = [
        "label" => "My Custom Themes",
        "themes" => [
            "my_theme" => [
                "name" => "My Theme",
                "description" => "Custom theme",
                "variables" => [
                    "--gf-primary-color" => "#123456"
                ]
            ]
        ]
    ];
    return $themes;
});'
                ],
                [
                    'hook' => 'gf_js_embed_theme_customizer_capability',
                    'description' => 'Change required capability for theme customizer',
                    'parameters' => [
                        '$capability' => 'Default: manage_options'
                    ],
                    'return' => 'WordPress capability string',
                    'example' => '
add_filter("gf_js_embed_theme_customizer_capability", function($capability) {
    return "edit_posts"; // Allow editors to customize themes
});'
                ]
            ],
            'actions' => [
                [
                    'hook' => 'gf_js_embed_theme_saved',
                    'description' => 'Fired after a theme is saved',
                    'parameters' => [
                        '$theme_name' => 'Name of saved theme',
                        '$theme_data' => 'Complete theme data array'
                    ],
                    'example' => '
add_action("gf_js_embed_theme_saved", function($theme_name, $theme_data) {
    // Log theme saves
    error_log("Theme saved: " . $theme_name);
    
    // Maybe sync to external service
    my_sync_theme_to_api($theme_name, $theme_data);
}, 10, 2);'
                ],
                [
                    'hook' => 'gf_js_embed_theme_deleted',
                    'description' => 'Fired after a theme is deleted',
                    'parameters' => [
                        '$theme_name' => 'Name of deleted theme'
                    ]
                ],
                [
                    'hook' => 'gf_js_embed_theme_applied',
                    'description' => 'Fired when a theme is applied to a form',
                    'parameters' => [
                        '$theme_name' => 'Name of applied theme',
                        '$form_id' => 'Gravity Forms form ID'
                    ]
                ]
            ]
        ];
    }
    
    /**
     * Get JavaScript API documentation
     */
    private function get_javascript_api_docs() {
        return [
            'theme_manager' => [
                'description' => 'Global theme manager object for programmatic theme control',
                'methods' => [
                    [
                        'method' => 'GFThemeManager.applyTheme(themeName)',
                        'description' => 'Apply a theme to all embedded forms',
                        'parameters' => [
                            'themeName' => 'Name of theme to apply'
                        ],
                        'example' => '
// Apply a theme programmatically
GFThemeManager.applyTheme("modern-minimal");

// Apply with callback
GFThemeManager.applyTheme("dark-mode", function() {
    console.log("Dark mode theme applied!");
});'
                    ],
                    [
                        'method' => 'GFThemeManager.getCurrentTheme()',
                        'description' => 'Get the currently active theme',
                        'returns' => 'Theme object or null',
                        'example' => '
const currentTheme = GFThemeManager.getCurrentTheme();
console.log("Current theme:", currentTheme.name);'
                    ],
                    [
                        'method' => 'GFThemeManager.getThemeVariables(themeName)',
                        'description' => 'Get CSS variables for a specific theme',
                        'parameters' => [
                            'themeName' => 'Name of theme'
                        ],
                        'returns' => 'Object of CSS variable definitions'
                    ],
                    [
                        'method' => 'GFThemeManager.createCustomTheme(name, variables)',
                        'description' => 'Create a custom theme programmatically',
                        'parameters' => [
                            'name' => 'Theme name',
                            'variables' => 'Object of CSS variables'
                        ],
                        'example' => '
GFThemeManager.createCustomTheme("dynamic-theme", {
    "--gf-primary-color": getUserPreferredColor(),
    "--gf-font-family": getUserPreferredFont()
});'
                    ]
                ]
            ],
            'events' => [
                'description' => 'Theme-related events you can listen to',
                'events' => [
                    [
                        'event' => 'gf_theme_changed',
                        'description' => 'Fired when theme is changed',
                        'data' => [
                            'previousTheme' => 'Previous theme name',
                            'newTheme' => 'New theme name',
                            'variables' => 'New theme variables'
                        ],
                        'example' => '
document.addEventListener("gf_theme_changed", function(e) {
    console.log("Theme changed from", e.detail.previousTheme, 
                "to", e.detail.newTheme);
    
    // Update UI elements based on new theme
    updateUIForTheme(e.detail.variables);
});'
                    ],
                    [
                        'event' => 'gf_theme_loaded',
                        'description' => 'Fired when theme CSS is fully loaded',
                        'data' => [
                            'themeName' => 'Loaded theme name'
                        ]
                    ]
                ]
            ]
        ];
    }
    
    /**
     * Get theme format documentation
     */
    private function get_theme_format_docs() {
        return [
            'structure' => [
                'name' => [
                    'type' => 'string',
                    'required' => true,
                    'description' => 'Unique theme identifier',
                    'example' => 'my-custom-theme'
                ],
                'description' => [
                    'type' => 'string',
                    'required' => false,
                    'description' => 'Human-readable theme description',
                    'example' => 'A beautiful minimal theme for contact forms'
                ],
                'version' => [
                    'type' => 'string',
                    'required' => false,
                    'description' => 'Theme version for tracking changes',
                    'example' => '1.0.0'
                ],
                'variables' => [
                    'type' => 'object',
                    'required' => true,
                    'description' => 'CSS custom properties definitions',
                    'example' => [
                        '--gf-primary-color' => '#0073aa',
                        '--gf-text-color' => '#333333',
                        '--gf-font-size-base' => '16px'
                    ]
                ],
                'metadata' => [
                    'type' => 'object',
                    'required' => false,
                    'description' => 'Additional theme metadata',
                    'properties' => [
                        'author' => 'Theme author name',
                        'website' => 'Author or theme website',
                        'license' => 'Theme license',
                        'tags' => 'Array of descriptive tags',
                        'category' => 'Theme category for organization'
                    ]
                ]
            ],
            'example' => '
{
    "name": "professional-blue",
    "description": "A professional blue theme suitable for business forms",
    "version": "1.2.0",
    "variables": {
        "--gf-primary-color": "#1e40af",
        "--gf-primary-hover": "#1e3a8a",
        "--gf-primary-focus": "#1e3a8a",
        "--gf-text-color": "#1f2937",
        "--gf-text-muted": "#6b7280",
        "--gf-bg-color": "#ffffff",
        "--gf-bg-alt": "#f9fafb",
        "--gf-border-color": "#e5e7eb",
        "--gf-border-radius-md": "6px",
        "--gf-font-family": "Inter, system-ui, sans-serif",
        "--gf-font-size-base": "16px",
        "--gf-spacing-md": "16px"
    },
    "metadata": {
        "author": "Your Name",
        "website": "https://example.com",
        "license": "GPL-2.0",
        "tags": ["professional", "blue", "business", "minimal"],
        "category": "business"
    }
}'
        ];
    }
    
    /**
     * Get code examples
     */
    public function get_code_examples() {
        return [
            'basic_usage' => [
                'title' => 'Basic Theme Usage',
                'description' => 'How to use themes in your forms',
                'examples' => [
                    'shortcode' => '[gf_js_embed id="1" theme="modern-minimal"]',
                    'javascript' => '
// Embed with theme
const embed = new GravityFormsEmbed({
    formId: 1,
    targetId: "my-form-container",
    theme: "modern-minimal"
});',
                    'php' => '
// Apply theme via PHP
add_filter("gf_js_embed_form_config", function($config, $form_id) {
    $config["theme"] = "modern-minimal";
    return $config;
}, 10, 2);'
                ]
            ],
            'dynamic_themes' => [
                'title' => 'Dynamic Theme Selection',
                'description' => 'Change themes based on conditions',
                'examples' => [
                    'time_based' => '
// Use dark theme at night
function getTimeBasedTheme() {
    const hour = new Date().getHours();
    return (hour >= 20 || hour < 6) ? "dark-mode" : "light-mode";
}

const embed = new GravityFormsEmbed({
    formId: 1,
    targetId: "my-form",
    theme: getTimeBasedTheme()
});',
                    'user_preference' => '
// Use theme based on user preference
const userTheme = localStorage.getItem("preferred-theme") || "default";
const embed = new GravityFormsEmbed({
    formId: 1,
    targetId: "my-form",
    theme: userTheme
});

// Allow user to change theme
document.getElementById("theme-selector").addEventListener("change", (e) => {
    const newTheme = e.target.value;
    localStorage.setItem("preferred-theme", newTheme);
    GFThemeManager.applyTheme(newTheme);
});'
                ]
            ],
            'custom_integration' => [
                'title' => 'Custom Theme Integration',
                'description' => 'Integrate themes with your application',
                'examples' => [
                    'react' => '
// React component with theme support
import { useEffect, useState } from "react";

function GravityForm({ formId, theme = "default" }) {
    const [isLoaded, setIsLoaded] = useState(false);
    
    useEffect(() => {
        const embed = new GravityFormsEmbed({
            formId,
            targetId: `gf-container-${formId}`,
            theme,
            onLoad: () => setIsLoaded(true)
        });
        
        embed.render();
        
        return () => embed.destroy();
    }, [formId, theme]);
    
    return (
        <div id={`gf-container-${formId}`}>
            {!isLoaded && <div>Loading form...</div>}
        </div>
    );
}',
                    'vue' => '
// Vue 3 component with theme support
<template>
  <div :id="containerId">
    <div v-if="!loaded">Loading form...</div>
  </div>
</template>

<script>
export default {
    props: {
        formId: Number,
        theme: {
            type: String,
            default: "default"
        }
    },
    data() {
        return {
            loaded: false,
            embed: null
        };
    },
    computed: {
        containerId() {
            return `gf-container-${this.formId}`;
        }
    },
    mounted() {
        this.initializeForm();
    },
    beforeUnmount() {
        if (this.embed) {
            this.embed.destroy();
        }
    },
    watch: {
        theme(newTheme) {
            if (this.embed) {
                GFThemeManager.applyTheme(newTheme);
            }
        }
    },
    methods: {
        initializeForm() {
            this.embed = new GravityFormsEmbed({
                formId: this.formId,
                targetId: this.containerId,
                theme: this.theme,
                onLoad: () => {
                    this.loaded = true;
                }
            });
            
            this.embed.render();
        }
    }
};
</script>'
                ]
            ]
        ];
    }
    
    /**
     * AJAX handler for getting API documentation
     */
    public function ajax_get_api_docs() {
        check_ajax_referer('gf_js_embed_docs', 'nonce');
        
        $section = sanitize_text_field($_POST['section'] ?? 'all');
        $docs = $this->get_api_documentation();
        
        if ($section === 'all') {
            wp_send_json_success($docs);
        } elseif (isset($docs[$section])) {
            wp_send_json_success($docs[$section]);
        } else {
            wp_send_json_error(__('Documentation section not found', 'gf-js-embed'));
        }
    }
    
    /**
     * AJAX handler for getting theme examples
     */
    public function ajax_get_theme_examples() {
        check_ajax_referer('gf_js_embed_docs', 'nonce');
        
        wp_send_json_success($this->get_code_examples());
    }
}