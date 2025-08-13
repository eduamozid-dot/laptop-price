<?php
/**
 * Script to create WordPress plugin ZIP file
 * Run this script to package the Used Laptop Pricer plugin
 */

// Configuration
$plugin_dir = 'used-laptop-pricer';
$output_file = 'used-laptop-pricer-v1.0.0.zip';

// Files and directories to include
$include_files = array(
    'used-laptop-pricer.php',
    'composer.json',
    'README.md',
    'admin/',
    'includes/',
    'templates/',
    'assets/',
    'sample-data/'
);

// Files and directories to exclude
$exclude_patterns = array(
    '*.log',
    '*.tmp',
    '.git/',
    '.gitignore',
    'node_modules/',
    'vendor/',
    'tests/',
    '*.zip'
);

echo "Creating WordPress plugin ZIP file...\n";

// Check if plugin directory exists
if (!is_dir($plugin_dir)) {
    echo "Error: Plugin directory '$plugin_dir' not found!\n";
    exit(1);
}

// Create ZIP file
$zip = new ZipArchive();
if ($zip->open($output_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
    echo "Error: Cannot create ZIP file '$output_file'!\n";
    exit(1);
}

// Function to check if file should be excluded
function should_exclude($file_path) {
    global $exclude_patterns;
    
    foreach ($exclude_patterns as $pattern) {
        if (fnmatch($pattern, basename($file_path))) {
            return true;
        }
    }
    
    return false;
}

// Function to add directory to ZIP
function add_directory_to_zip($zip, $dir, $base_path = '') {
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    
    foreach ($files as $file) {
        if (!$file->isDir()) {
            $file_path = $file->getRealPath();
            $relative_path = $base_path . str_replace($dir . '/', '', $file_path);
            
            if (!should_exclude($relative_path)) {
                echo "Adding: $relative_path\n";
                $zip->addFile($file_path, $relative_path);
            }
        }
    }
}

// Add files and directories
foreach ($include_files as $item) {
    $item_path = $plugin_dir . '/' . $item;
    
    if (is_file($item_path)) {
        if (!should_exclude($item)) {
            echo "Adding: $item\n";
            $zip->addFile($item_path, $item);
        }
    } elseif (is_dir($item_path)) {
        echo "Adding directory: $item/\n";
        add_directory_to_zip($zip, $item_path, $item . '/');
    }
}

$zip->close();

echo "\nPlugin ZIP file created successfully: $output_file\n";
echo "File size: " . number_format(filesize($output_file)) . " bytes\n";

// Verify ZIP file
if (file_exists($output_file)) {
    $zip = new ZipArchive();
    if ($zip->open($output_file) === TRUE) {
        $file_count = $zip->numFiles;
        echo "ZIP file contains $file_count files\n";
        $zip->close();
        
        echo "\nPlugin is ready for installation!\n";
        echo "Upload '$output_file' to your WordPress site via Plugins > Add New > Upload Plugin\n";
    } else {
        echo "Error: Cannot verify ZIP file!\n";
        exit(1);
    }
} else {
    echo "Error: ZIP file was not created!\n";
    exit(1);
}

echo "\nInstallation instructions:\n";
echo "1. Go to WordPress Admin > Plugins > Add New\n";
echo "2. Click 'Upload Plugin'\n";
echo "3. Choose the file: $output_file\n";
echo "4. Click 'Install Now'\n";
echo "5. Activate the plugin\n";
echo "6. Go to 'لپ‌تاپ پرایسر' menu to configure\n";
echo "\nFor more information, see README.md\n";