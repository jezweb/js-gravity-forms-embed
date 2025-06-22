<?php
/**
 * Local Development Server for Gravity Forms JS Embed
 * 
 * Usage: php -S localhost:8080 local-server.php
 */

// Allow CORS for local development
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key, X-CSRF-Token');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get the request URI
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Simple router
switch (true) {
    // Serve the JavaScript SDK
    case $uri === '/gf-js-embed/v1/embed.js':
        header('Content-Type: application/javascript');
        $sdk_path = dirname(__DIR__) . '/assets/js/gf-embed-sdk.js';
        if (file_exists($sdk_path)) {
            // Replace PHP template variables
            $content = file_get_contents($sdk_path);
            $content = str_replace(
                "apiUrl: '', // Will be set dynamically",
                "apiUrl: 'http://localhost:8080/wp-json/gf-embed/v1',",
                $content
            );
            echo $content;
        } else {
            echo "// SDK file not found";
        }
        break;
        
    // API: Get form data
    case preg_match('#/wp-json/gf-embed/v1/form/(\d+)#', $uri, $matches):
        header('Content-Type: application/json');
        $form_id = $matches[1];
        
        // Mock form data
        $forms = [
            '1' => [
                'id' => 1,
                'title' => 'Contact Form',
                'description' => 'Get in touch with us',
                'displayTitle' => true,
                'displayDescription' => true,
                'button' => ['text' => 'Submit'],
                'fields' => [
                    ['id' => 1, 'type' => 'text', 'label' => 'Full Name', 'isRequired' => true, 'placeholder' => 'John Doe'],
                    ['id' => 2, 'type' => 'email', 'label' => 'Email', 'isRequired' => true, 'placeholder' => 'john@example.com'],
                    ['id' => 3, 'type' => 'select', 'label' => 'Subject', 'isRequired' => true, 
                     'choices' => [
                         ['text' => 'General Inquiry', 'value' => 'general'],
                         ['text' => 'Support', 'value' => 'support'],
                         ['text' => 'Sales', 'value' => 'sales']
                     ]],
                    ['id' => 4, 'type' => 'textarea', 'label' => 'Message', 'isRequired' => true],
                    ['id' => 5, 'type' => 'checkbox', 'label' => 'Options', 
                     'choices' => [
                         ['text' => 'Subscribe to newsletter', 'value' => 'newsletter'],
                         ['text' => 'I agree to terms', 'value' => 'terms']
                     ]],
                    ['id' => 6, 'type' => 'signature', 'label' => 'Signature'],
                    ['id' => 7, 'type' => 'list', 'label' => 'Items', 'enableColumns' => true,
                     'choices' => [
                         ['text' => 'Item Name'],
                         ['text' => 'Quantity'],
                         ['text' => 'Price']
                     ]]
                ]
            ],
            '2' => [
                'id' => 2,
                'title' => 'Advanced Test Form',
                'description' => 'Testing all field types',
                'displayTitle' => true,
                'displayDescription' => true,
                'button' => ['text' => 'Submit Test'],
                'fields' => [
                    ['id' => 1, 'type' => 'text', 'label' => 'Text Field', 'isRequired' => true],
                    ['id' => 2, 'type' => 'number', 'label' => 'Number', 'rangeMin' => 0, 'rangeMax' => 100],
                    ['id' => 3, 'type' => 'date', 'label' => 'Date'],
                    ['id' => 4, 'type' => 'time', 'label' => 'Time'],
                    ['id' => 5, 'type' => 'fileupload', 'label' => 'File Upload', 'allowedExtensions' => '.jpg,.png,.pdf'],
                    ['id' => 6, 'type' => 'website', 'label' => 'Website URL'],
                    ['id' => 7, 'type' => 'password', 'label' => 'Password'],
                    ['id' => 8, 'type' => 'calculation', 'label' => 'Total', 'formula' => '{2} * 10']
                ]
            ]
        ];
        
        if (isset($forms[$form_id])) {
            echo json_encode(['success' => true, 'form' => $forms[$form_id]]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Form not found']);
        }
        break;
        
    // API: Submit form
    case preg_match('#/wp-json/gf-embed/v1/submit/(\d+)#', $uri):
        header('Content-Type: application/json');
        
        // Simulate validation
        $errors = [];
        $data = $_POST;
        
        // Random validation failure for testing
        if (rand(1, 10) <= 2) {
            $errors[2] = 'Please enter a valid email address';
        }
        
        if (empty($errors)) {
            echo json_encode([
                'success' => true,
                'entry_id' => rand(1000, 9999),
                'confirmation' => [
                    'type' => 'message',
                    'message' => 'Thank you for your submission! Entry ID: #' . rand(1000, 9999)
                ]
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'errors' => $errors,
                'message' => 'Please correct the errors below.'
            ]);
        }
        break;
        
    // API: Get assets
    case preg_match('#/wp-json/gf-embed/v1/assets/(\d+)#', $uri):
        header('Content-Type: application/json');
        
        $css = file_get_contents(dirname(__DIR__) . '/includes/class-gf-js-embed-styling.php');
        preg_match('/\/\* Base Styles.*?\'\;/s', $css, $matches);
        $base_css = $matches[0] ?? '';
        $base_css = str_replace(["'", "        '"], "", $base_css);
        
        echo json_encode([
            'css' => $base_css,
            'translations' => [
                'loading' => 'Loading form...',
                'error' => 'Error loading form',
                'submit' => 'Submit',
                'submitting' => 'Submitting...',
                'required' => 'This field is required',
                'invalid_email' => 'Please enter a valid email'
            ],
            'config' => [
                'dateFormat' => 'mm/dd/yyyy',
                'timeFormat' => '12:00 AM',
                'currency' => 'USD'
            ]
        ]);
        break;
        
    // Serve test page
    case $uri === '/' || $uri === '/index.html':
        header('Content-Type: text/html');
        ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gravity Forms JS Embed - Local Test Server</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .header {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
            margin-bottom: 30px;
        }
        .form-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .status {
            display: inline-block;
            padding: 5px 10px;
            background: #4CAF50;
            color: white;
            border-radius: 20px;
            font-size: 14px;
            margin-left: 10px;
        }
        .info-box {
            background: #e3f2fd;
            border: 1px solid #2196F3;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
        }
        .info-box h3 { margin-top: 0; color: #1976D2; }
        .endpoint-list {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            font-family: monospace;
            font-size: 14px;
        }
        .test-buttons {
            margin: 20px 0;
            text-align: center;
        }
        .test-buttons button {
            background: #2196F3;
            color: white;
            border: none;
            padding: 10px 20px;
            margin: 0 5px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        .test-buttons button:hover {
            background: #1976D2;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🚀 Gravity Forms JS Embed - Local Test Server</h1>
        <p>Server running at <strong>http://localhost:8080</strong> <span class="status">● Online</span></p>
    </div>

    <div class="info-box">
        <h3>📡 Available API Endpoints:</h3>
        <div class="endpoint-list">
GET  /gf-js-embed/v1/embed.js         - JavaScript SDK<br>
GET  /wp-json/gf-embed/v1/form/{id}   - Get form configuration<br>
POST /wp-json/gf-embed/v1/submit/{id} - Submit form data<br>
GET  /wp-json/gf-embed/v1/assets/{id} - Get form CSS and translations
        </div>
    </div>

    <div class="test-buttons">
        <button onclick="loadForm(1)">Load Form 1 (Basic)</button>
        <button onclick="loadForm(2)">Load Form 2 (Advanced)</button>
        <button onclick="clearForms()">Clear Forms</button>
    </div>

    <div class="form-container">
        <h2>Test Form 1 - Basic Contact Form</h2>
        <div id="test-form-1"></div>
    </div>

    <div class="form-container">
        <h2>Test Form 2 - Advanced Fields</h2>
        <div id="test-form-2"></div>
    </div>

    <!-- Load the SDK -->
    <script src="/gf-js-embed/v1/embed.js"></script>
    
    <script>
        // Manual form loading functions
        function loadForm(formId) {
            const container = document.getElementById('test-form-' + formId);
            container.innerHTML = ''; // Clear existing content
            
            // Set data attribute and load form
            container.setAttribute('data-gf-form', formId);
            GravityFormsEmbed.loadForm(formId, container);
        }
        
        function clearForms() {
            document.getElementById('test-form-1').innerHTML = '';
            document.getElementById('test-form-2').innerHTML = '';
        }
        
        // Listen for events
        document.addEventListener('gfEmbedFormReady', function(e) {
            console.log('✅ Form ready:', e.detail);
        });
        
        document.addEventListener('gfEmbedSubmitSuccess', function(e) {
            console.log('🎉 Submission success:', e.detail);
        });
        
        document.addEventListener('gfEmbedSubmitError', function(e) {
            console.log('❌ Submission error:', e.detail);
        });
    </script>
</body>
</html>
        <?php
        break;
        
    // 404 for everything else
    default:
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Endpoint not found', 'uri' => $uri]);
}