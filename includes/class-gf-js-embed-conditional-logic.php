<?php
/**
 * Conditional Logic class
 *
 * @package GravityFormsJSEmbed
 */

if (!defined('ABSPATH')) {
    exit;
}

class GF_JS_Embed_Conditional_Logic {
    
    private static $instance = null;
    
    /**
     * Supported operators
     */
    const OPERATORS = [
        'is' => 'Is',
        'isnot' => 'Is Not',
        'contains' => 'Contains',
        'starts_with' => 'Starts With',
        'ends_with' => 'Ends With',
        'greater_than' => 'Greater Than',
        'less_than' => 'Less Than',
        'is_empty' => 'Is Empty',
        'is_not_empty' => 'Is Not Empty'
    ];
    
    /**
     * Supported actions
     */
    const ACTIONS = [
        'show' => 'Show Field',
        'hide' => 'Hide Field',
        'enable' => 'Enable Field',
        'disable' => 'Disable Field',
        'require' => 'Make Required',
        'unrequire' => 'Make Optional'
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
        // Add REST endpoints
        add_action('rest_api_init', [$this, 'register_rest_endpoints']);
        
        // Filter form data to add conditional logic
        add_filter('gf_js_embed_form_data', [$this, 'add_conditional_logic_data'], 10, 2);
        
        // Add scripts
        add_action('wp_enqueue_scripts', [$this, 'enqueue_conditional_logic_scripts']);
        
        // Admin hooks
        add_filter('gf_js_embed_admin_settings_fields', [$this, 'add_admin_settings']);
        add_action('wp_ajax_gf_js_embed_save_conditional_logic', [$this, 'handle_save_conditional_logic']);
        add_action('wp_ajax_gf_js_embed_get_conditional_logic', [$this, 'handle_get_conditional_logic']);
    }
    
    /**
     * Register REST endpoints
     */
    public function register_rest_endpoints() {
        $namespace = 'gf-embed/v1';
        
        // Evaluate conditional logic
        register_rest_route($namespace, '/conditional-logic/evaluate', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_evaluate_logic'],
            'permission_callback' => '__return_true',
            'args' => [
                'form_id' => [
                    'required' => true,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint'
                ],
                'field_values' => [
                    'required' => true,
                    'type' => 'object'
                ]
            ]
        ]);
        
        // Get field dependencies
        register_rest_route($namespace, '/conditional-logic/dependencies', [
            'methods' => 'GET',
            'callback' => [$this, 'handle_get_dependencies'],
            'permission_callback' => '__return_true',
            'args' => [
                'form_id' => [
                    'required' => true,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint'
                ]
            ]
        ]);
    }
    
    /**
     * Add conditional logic data to form
     */
    public function add_conditional_logic_data($form_data, $form) {
        // Get conditional logic rules for the form
        $rules = $this->get_form_conditional_logic($form['id']);
        
        if (empty($rules)) {
            return $form_data;
        }
        
        // Build conditional logic structure
        $conditional_logic = [
            'enabled' => true,
            'rules' => $rules,
            'dependencies' => $this->build_dependencies($rules),
            'initial_states' => $this->calculate_initial_states($form, $rules)
        ];
        
        $form_data['conditional_logic'] = $conditional_logic;
        
        return $form_data;
    }
    
    /**
     * Get conditional logic rules for a form
     */
    private function get_form_conditional_logic($form_id) {
        $rules = get_post_meta($form_id, '_gf_js_embed_conditional_logic', true);
        
        if (empty($rules)) {
            // Check if form has native Gravity Forms conditional logic
            $form = GFAPI::get_form($form_id);
            if ($form) {
                $rules = $this->convert_native_conditional_logic($form);
            }
        }
        
        return $rules ?: [];
    }
    
    /**
     * Convert native Gravity Forms conditional logic
     */
    private function convert_native_conditional_logic($form) {
        $rules = [];
        
        foreach ($form['fields'] as $field) {
            if (!empty($field['conditionalLogic'])) {
                $logic = $field['conditionalLogic'];
                
                $rule = [
                    'id' => 'field_' . $field['id'],
                    'field_id' => $field['id'],
                    'action' => $logic['actionType'] === 'show' ? 'show' : 'hide',
                    'logic_type' => $logic['logicType'] ?? 'all', // all or any
                    'conditions' => []
                ];
                
                foreach ($logic['rules'] as $condition) {
                    $rule['conditions'][] = [
                        'field_id' => $condition['fieldId'],
                        'operator' => $this->convert_operator($condition['operator']),
                        'value' => $condition['value']
                    ];
                }
                
                $rules[] = $rule;
            }
        }
        
        return $rules;
    }
    
    /**
     * Convert Gravity Forms operator to our format
     */
    private function convert_operator($gf_operator) {
        $operator_map = [
            'is' => 'is',
            'isnot' => 'isnot',
            '>' => 'greater_than',
            '<' => 'less_than',
            'contains' => 'contains',
            'starts_with' => 'starts_with',
            'ends_with' => 'ends_with'
        ];
        
        return $operator_map[$gf_operator] ?? 'is';
    }
    
    /**
     * Build field dependencies map
     */
    private function build_dependencies($rules) {
        $dependencies = [];
        
        foreach ($rules as $rule) {
            $target_field = $rule['field_id'];
            
            foreach ($rule['conditions'] as $condition) {
                $source_field = $condition['field_id'];
                
                if (!isset($dependencies[$source_field])) {
                    $dependencies[$source_field] = [];
                }
                
                if (!in_array($target_field, $dependencies[$source_field])) {
                    $dependencies[$source_field][] = $target_field;
                }
            }
        }
        
        return $dependencies;
    }
    
    /**
     * Calculate initial field states
     */
    private function calculate_initial_states($form, $rules) {
        $states = [];
        
        foreach ($form['fields'] as $field) {
            $field_id = $field['id'];
            $states[$field_id] = [
                'visible' => true,
                'enabled' => true,
                'required' => $field['isRequired'] ?? false
            ];
        }
        
        // Apply rules with empty values to get initial states
        $empty_values = [];
        foreach ($rules as $rule) {
            $result = $this->evaluate_rule($rule, $empty_values);
            $this->apply_rule_result($states, $rule, $result);
        }
        
        return $states;
    }
    
    /**
     * Evaluate a single rule
     */
    public function evaluate_rule($rule, $field_values) {
        $conditions_met = [];
        
        foreach ($rule['conditions'] as $condition) {
            $field_value = $field_values[$condition['field_id']] ?? '';
            $condition_met = $this->evaluate_condition($condition, $field_value);
            $conditions_met[] = $condition_met;
        }
        
        // Apply logic type (all = AND, any = OR)
        if ($rule['logic_type'] === 'any') {
            return in_array(true, $conditions_met, true);
        } else {
            return !in_array(false, $conditions_met, true);
        }
    }
    
    /**
     * Evaluate a single condition
     */
    private function evaluate_condition($condition, $field_value) {
        $operator = $condition['operator'];
        $expected_value = $condition['value'];
        
        switch ($operator) {
            case 'is':
                return $field_value == $expected_value;
                
            case 'isnot':
                return $field_value != $expected_value;
                
            case 'contains':
                return strpos($field_value, $expected_value) !== false;
                
            case 'starts_with':
                return strpos($field_value, $expected_value) === 0;
                
            case 'ends_with':
                return substr($field_value, -strlen($expected_value)) === $expected_value;
                
            case 'greater_than':
                return is_numeric($field_value) && is_numeric($expected_value) && $field_value > $expected_value;
                
            case 'less_than':
                return is_numeric($field_value) && is_numeric($expected_value) && $field_value < $expected_value;
                
            case 'is_empty':
                return empty($field_value);
                
            case 'is_not_empty':
                return !empty($field_value);
                
            default:
                return false;
        }
    }
    
    /**
     * Apply rule result to field states
     */
    private function apply_rule_result(&$states, $rule, $condition_met) {
        $field_id = $rule['field_id'];
        $action = $rule['action'];
        
        if (!isset($states[$field_id])) {
            $states[$field_id] = [
                'visible' => true,
                'enabled' => true,
                'required' => false
            ];
        }
        
        switch ($action) {
            case 'show':
                $states[$field_id]['visible'] = $condition_met;
                break;
                
            case 'hide':
                $states[$field_id]['visible'] = !$condition_met;
                break;
                
            case 'enable':
                $states[$field_id]['enabled'] = $condition_met;
                break;
                
            case 'disable':
                $states[$field_id]['enabled'] = !$condition_met;
                break;
                
            case 'require':
                $states[$field_id]['required'] = $condition_met;
                break;
                
            case 'unrequire':
                $states[$field_id]['required'] = !$condition_met;
                break;
        }
    }
    
    /**
     * Handle evaluate logic request
     */
    public function handle_evaluate_logic($request) {
        $form_id = $request->get_param('form_id');
        $field_values = $request->get_param('field_values');
        
        // Get rules
        $rules = $this->get_form_conditional_logic($form_id);
        
        if (empty($rules)) {
            return new WP_REST_Response([
                'success' => true,
                'data' => [
                    'states' => []
                ]
            ], 200);
        }
        
        // Get form for initial states
        $form = GFAPI::get_form($form_id);
        if (!$form) {
            return new WP_Error('form_not_found', 'Form not found', ['status' => 404]);
        }
        
        // Calculate states
        $states = $this->calculate_initial_states($form, $rules);
        
        // Apply rules
        foreach ($rules as $rule) {
            $result = $this->evaluate_rule($rule, $field_values);
            $this->apply_rule_result($states, $rule, $result);
        }
        
        return new WP_REST_Response([
            'success' => true,
            'data' => [
                'states' => $states
            ]
        ], 200);
    }
    
    /**
     * Handle get dependencies request
     */
    public function handle_get_dependencies($request) {
        $form_id = $request->get_param('form_id');
        
        $rules = $this->get_form_conditional_logic($form_id);
        $dependencies = $this->build_dependencies($rules);
        
        return new WP_REST_Response([
            'success' => true,
            'data' => [
                'dependencies' => $dependencies
            ]
        ], 200);
    }
    
    /**
     * Enqueue conditional logic scripts
     */
    public function enqueue_conditional_logic_scripts() {
        if (!is_admin()) {
            wp_enqueue_script(
                'gf-embed-conditional-logic',
                GF_JS_EMBED_PLUGIN_URL . 'assets/js/gf-embed-conditional-logic.js',
                ['gf-embed-events'],
                GF_JS_EMBED_VERSION,
                true
            );
            
            wp_localize_script('gf-embed-conditional-logic', 'gfConditionalLogicConfig', [
                'restUrl' => rest_url('gf-embed/v1/'),
                'nonce' => wp_create_nonce('wp_rest'),
                'debounceDelay' => 300
            ]);
        }
    }
    
    /**
     * Add admin settings fields
     */
    public function add_admin_settings($fields) {
        $fields['conditional_logic'] = [
            'title' => __('Conditional Logic', 'gf-js-embed'),
            'callback' => [$this, 'render_admin_settings']
        ];
        
        return $fields;
    }
    
    /**
     * Render admin settings
     */
    public function render_admin_settings() {
        ?>
        <div id="conditional-logic-settings">
            <p>Configure conditional logic rules for form fields.</p>
            <div id="conditional-logic-rules"></div>
            <button type="button" class="button" id="add-conditional-rule">Add Rule</button>
        </div>
        <?php
    }
    
    /**
     * Handle save conditional logic AJAX
     */
    public function handle_save_conditional_logic() {
        check_ajax_referer('gf_js_embed_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $form_id = absint($_POST['form_id']);
        $rules = json_decode(stripslashes($_POST['rules']), true);
        
        update_post_meta($form_id, '_gf_js_embed_conditional_logic', $rules);
        
        wp_send_json_success([
            'message' => __('Conditional logic saved successfully', 'gf-js-embed')
        ]);
    }
    
    /**
     * Handle get conditional logic AJAX
     */
    public function handle_get_conditional_logic() {
        check_ajax_referer('gf_js_embed_admin', 'nonce');
        
        $form_id = absint($_POST['form_id']);
        $rules = $this->get_form_conditional_logic($form_id);
        
        wp_send_json_success([
            'rules' => $rules
        ]);
    }
}