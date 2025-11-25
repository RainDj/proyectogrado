<?php
$primaryColor   = '#0d6efd';
$secondaryColor = '#6c757d';
$backgroundBody = '#f8f9fa';
$cardBg         = '#ffffff';
$textColor      = '#212529';
$dangerColor    = '#dc3545';
$warningColor   = '#ffc107';
$successColor   = '#198754';

$baseFontFamily = 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif';

function render_theme_styles() {
    global $primaryColor, $secondaryColor, $backgroundBody, $cardBg, $textColor,
           $dangerColor, $warningColor, $successColor, $baseFontFamily;
    echo "<style>
        :root {
            --primary-color: {$primaryColor};
            --secondary-color: {$secondaryColor};
            --background-color: {$backgroundBody};
            --card-bg: {$cardBg};
            --text-color: {$textColor};
            --danger-color: {$dangerColor};
            --warning-color: {$warningColor};
            --success-color: {$successColor};
            --font-family-base: {$baseFontFamily};
        }
    </style>";
}
?>
