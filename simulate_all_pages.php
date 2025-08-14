<?php
require_once 'lang.php';

$translator = UniversalTranslator::getInstance();
$translator->setAutoTranslate(true); // only for development

// Simulate calls to all your phrases
$keys = [
    'Home', 'About', 'Contact', 'Services', 'Login', 'Logout', 'Register', 'Dashboard',
    'Save', 'Cancel', 'Delete', 'Edit', 'View', 'Add', 'Update', 'Submit', 'Search',
    'Filter', 'Export', 'Import', 'Name', 'Email', 'Password', 'Phone', 'Address',
    'Message', 'Subject', 'Date', 'Time', 'Description', 'Title',
    'Success', 'Error', 'Warning', 'Info', 'Loading', 'No data found',
    'Are you sure?', 'Operation completed successfully', 'An error occurred',
    'HOF Dashboard', 'Human Resource Dashboard', 'Rectorate Dashboard',
    'Requests for Review', 'Request Type:', 'Status:', 'More Details', 'Approve', 'Reject', 'Update Status'
];

// You can add more manually or scan your codebase later

foreach ($keys as $key) {
    echo "Translating: {$key} => " . __($key) . "\n";
}
?>