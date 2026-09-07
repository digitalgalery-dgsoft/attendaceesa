<?php
$bladeContent = file_get_contents(__DIR__ . '/../att-admin-v12/resources/views/portal/partials/daily_maintenance_dashboard.blade.php');
echo "Checking PHP syntax in daily_maintenance_dashboard.blade.php...\n";

// Check basic tag balance
$ifCount = substr_count($bladeContent, '@if');
$endifCount = substr_count($bladeContent, '@endif');
$forelseCount = substr_count($bladeContent, '@forelse');
$endforelseCount = substr_count($bladeContent, '@endforelse');
$forCount = substr_count($bladeContent, '@for');
$endforCount = substr_count($bladeContent, '@endfor');
$phpCount = substr_count($bladeContent, '@php');
$endphpCount = substr_count($bladeContent, '@endphp');

echo "@if: $ifCount, @endif: $endifCount\n";
echo "@forelse: $forelseCount, @endforelse: $endforelseCount\n";
echo "@for: $forCount, @endfor: $endforCount\n";
echo "@php: $phpCount, @endphp: $endphpCount\n";

if ($ifCount === $endifCount && $forelseCount === $endforelseCount && $forCount === $endforCount && $phpCount === $endphpCount) {
    echo "All Blade control structures are perfectly balanced!\n";
} else {
    echo "MISMATCH DETECTED!\n";
    exit(1);
}
