#!/usr/bin/env php
<?php
/**
 * PHP Syntax Validator for Gravity Forms JS Embed Plugin
 * 
 * This script validates all PHP files in the plugin without requiring WordPress
 */

// Colors for output
$colors = [
    'red' => "\033[31m",
    'green' => "\033[32m",
    'yellow' => "\033[33m",
    'blue' => "\033[34m",
    'reset' => "\033[0m"
];

echo $colors['blue'] . "🔍 Gravity Forms JS Embed - PHP Syntax Validator\n" . $colors['reset'];
echo str_repeat("=", 50) . "\n\n";

// Get plugin directory
$pluginDir = dirname(__DIR__);
$errors = [];
$warnings = [];
$filesChecked = 0;

// Function to validate PHP file
function validatePHPFile($file) {
    global $colors;
    
    $output = [];
    $returnCode = 0;
    
    // Check syntax
    exec("php -l " . escapeshellarg($file) . " 2>&1", $output, $returnCode);
    
    $result = [
        'valid' => $returnCode === 0,
        'output' => implode("\n", $output),
        'errors' => [],
        'warnings' => []
    ];
    
    // Additional checks
    $content = file_get_contents($file);
    
    // Check for common issues
    if (strpos($content, '<?php') !== 0 && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
        $result['warnings'][] = "File should start with <?php";
    }
    
    // Check for WordPress coding standards issues
    if (preg_match('/\s+$/', $content)) {
        $result['warnings'][] = "Trailing whitespace detected";
    }
    
    // Check for closing PHP tag in class files
    if (strpos($file, 'class-') !== false && strpos($content, '?>') !== false) {
        $result['warnings'][] = "Class files should not have closing PHP tag";
    }
    
    // Check for proper file headers
    if (strpos($file, 'includes/') !== false && !preg_match('/\/\*\*\s*\n\s*\*.*@package/s', $content)) {
        $result['warnings'][] = "Missing or incomplete file header documentation";
    }
    
    // Check for security
    if (strpos($file, 'includes/') !== false && !preg_match('/if\s*\(\s*!\s*defined\s*\(\s*[\'"]ABSPATH[\'"]\s*\)\s*\)/', $content)) {
        $result['errors'][] = "Missing ABSPATH check for direct access prevention";
    }
    
    return $result;
}

// Function to find all PHP files
function findPHPFiles($dir) {
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $files[] = $file->getPathname();
        }
    }
    
    return $files;
}

// Find and validate all PHP files
$phpFiles = findPHPFiles($pluginDir);

echo "Found " . count($phpFiles) . " PHP files to validate\n\n";

foreach ($phpFiles as $file) {
    $relativePath = str_replace($pluginDir . '/', '', $file);
    echo "Checking: " . $relativePath . " ... ";
    
    $result = validatePHPFile($file);
    $filesChecked++;
    
    if ($result['valid'] && empty($result['errors']) && empty($result['warnings'])) {
        echo $colors['green'] . "✓ PASS" . $colors['reset'] . "\n";
    } elseif ($result['valid'] && !empty($result['warnings'])) {
        echo $colors['yellow'] . "⚠ WARNING" . $colors['reset'] . "\n";
        foreach ($result['warnings'] as $warning) {
            $warnings[] = $relativePath . ': ' . $warning;
            echo "  → " . $warning . "\n";
        }
    } else {
        echo $colors['red'] . "✗ FAIL" . $colors['reset'] . "\n";
        if (!$result['valid']) {
            $errors[] = $relativePath . ': ' . $result['output'];
            echo "  → " . $result['output'] . "\n";
        }
        foreach ($result['errors'] as $error) {
            $errors[] = $relativePath . ': ' . $error;
            echo "  → " . $error . "\n";
        }
    }
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "📊 Validation Summary:\n";
echo "  Files checked: " . $filesChecked . "\n";
echo "  Errors: " . $colors['red'] . count($errors) . $colors['reset'] . "\n";
echo "  Warnings: " . $colors['yellow'] . count($warnings) . $colors['reset'] . "\n";

// Additional checks
echo "\n🔒 Security Checks:\n";

// Check main plugin file
$mainFile = $pluginDir . '/gravity-forms-js-embed.php';
if (file_exists($mainFile)) {
    $mainContent = file_get_contents($mainFile);
    
    // Check plugin headers
    if (preg_match('/Plugin Name:\s*(.+)/', $mainContent, $matches)) {
        echo "  ✓ Plugin Name: " . $matches[1] . "\n";
    } else {
        echo "  " . $colors['red'] . "✗ Missing Plugin Name header" . $colors['reset'] . "\n";
    }
    
    if (preg_match('/Version:\s*(.+)/', $mainContent, $matches)) {
        echo "  ✓ Version: " . $matches[1] . "\n";
    } else {
        echo "  " . $colors['red'] . "✗ Missing Version header" . $colors['reset'] . "\n";
    }
}

// Check for common security issues
$securityIssues = 0;
foreach ($phpFiles as $file) {
    $content = file_get_contents($file);
    
    // Check for direct $_GET/$_POST usage without sanitization
    if (preg_match('/\$_(GET|POST|REQUEST)\s*\[/', $content) && 
        !preg_match('/(sanitize_|esc_|intval|absint)/', $content)) {
        $securityIssues++;
    }
}

if ($securityIssues > 0) {
    echo "  " . $colors['yellow'] . "⚠ Found $securityIssues file(s) with potential unsanitized input" . $colors['reset'] . "\n";
} else {
    echo "  ✓ No obvious security issues found\n";
}

// WordPress compatibility check
echo "\n📦 WordPress Compatibility:\n";
$requiredFunctions = [
    'add_action' => 'WordPress hooks',
    'add_filter' => 'WordPress filters',
    'register_rest_route' => 'REST API',
    'wp_enqueue_script' => 'Script enqueuing'
];

$missingFunctions = [];
foreach ($phpFiles as $file) {
    $content = file_get_contents($file);
    foreach ($requiredFunctions as $func => $desc) {
        if (strpos($content, $func) !== false) {
            unset($requiredFunctions[$func]);
        }
    }
}

if (empty($requiredFunctions)) {
    echo "  ✓ All core WordPress functions are used\n";
} else {
    foreach ($requiredFunctions as $func => $desc) {
        echo "  " . $colors['yellow'] . "⚠ Not using: $func ($desc)" . $colors['reset'] . "\n";
    }
}

// Final result
echo "\n" . str_repeat("=", 50) . "\n";
if (count($errors) === 0) {
    echo $colors['green'] . "✅ All PHP files passed validation!" . $colors['reset'] . "\n";
    exit(0);
} else {
    echo $colors['red'] . "❌ Validation failed with " . count($errors) . " error(s)" . $colors['reset'] . "\n";
    exit(1);
}