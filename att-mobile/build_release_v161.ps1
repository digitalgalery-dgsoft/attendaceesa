$ErrorActionPreference = "Stop"

# Use H:\Temp or G:\Temp to prevent Drive C: disk full error
if (Test-Path "H:\Temp") {
    $env:TEMP = "H:\Temp"
    $env:TMP = "H:\Temp"
    $env:_JAVA_OPTIONS = "-Djava.io.tmpdir=H:\Temp"
    $env:GRADLE_OPTS = "-Djava.io.tmpdir=H:\Temp"
} elseif (Test-Path "G:\Temp") {
    $env:TEMP = "G:\Temp"
    $env:TMP = "G:\Temp"
    $env:_JAVA_OPTIONS = "-Djava.io.tmpdir=G:\Temp"
    $env:GRADLE_OPTS = "-Djava.io.tmpdir=G:\Temp"
}

$pubspecPath = "pubspec.yaml"
if (-Not (Test-Path $pubspecPath)) {
    Write-Host "pubspec.yaml not found! Make sure you are running this in the att-mobile directory."
    exit 1
}

$version = ""
$content = Get-Content $pubspecPath
foreach ($line in $content) {
    if ($line -match "^version:\s*([0-9\.]+)\+([0-9]+)") {
        $version = $matches[1]
        Write-Host "Building for current version: $version (build $($matches[2]))"
        break
    }
}

if ([string]::IsNullOrEmpty($version)) {
    Write-Host "Could not determine version from pubspec.yaml"
    exit 1
}

$sourceApk = "build\app\outputs\flutter-apk\app-release.apk"
if (Test-Path $sourceApk) {
    Remove-Item -Path $sourceApk -Force
}

Write-Host "=== BUILDING APK RELEASE ==="
flutter build apk --release
if ($LASTEXITCODE -ne 0) {
    Write-Host "Flutter build APK failed with exit code $LASTEXITCODE"
    exit $LASTEXITCODE
}

$destApk = "app-release-$version.apk"
if (Test-Path $sourceApk) {
    Copy-Item -Path $sourceApk -Destination $destApk -Force
    Copy-Item -Path $sourceApk -Destination "app-release.apk" -Force
    Write-Host "APK copied locally: $destApk and app-release.apk"
    
    $adminPublic = "..\att-admin-v12\public"
    if (Test-Path $adminPublic) {
        Copy-Item -Path $sourceApk -Destination "$adminPublic\app-release.apk" -Force
        Copy-Item -Path $sourceApk -Destination "$adminPublic\$destApk" -Force
        Write-Host "Copied APK to $adminPublic"
    }
    Copy-Item -Path $sourceApk -Destination "..\app-release.apk" -Force
    Copy-Item -Path $sourceApk -Destination "..\$destApk" -Force
    Write-Host "Copied APK to root directory"
} else {
    Write-Host "Failed to find built APK at $sourceApk"
    exit 1
}

# --- BUILD AAB (Android App Bundle for Google Play Store) ---
$sourceAab = "build\app\outputs\bundle\release\app-release.aab"
if (Test-Path $sourceAab) {
    Remove-Item -Path $sourceAab -Force
}

Write-Host "=== BUILDING AAB (APP BUNDLE) ==="
flutter build appbundle --release
$destAab = "app-release-$version.aab"

if (Test-Path $sourceAab) {
    Copy-Item -Path $sourceAab -Destination $destAab -Force
    Copy-Item -Path $sourceAab -Destination "app-release.aab" -Force
    Write-Host "AAB copied locally: $destAab and app-release.aab"
    
    $adminPublic = "..\att-admin-v12\public"
    if (Test-Path $adminPublic) {
        Copy-Item -Path $sourceAab -Destination "$adminPublic\app-release.aab" -Force
        Copy-Item -Path $sourceAab -Destination "$adminPublic\$destAab" -Force
        Write-Host "Copied AAB to $adminPublic"
    }
    Copy-Item -Path $sourceAab -Destination "..\app-release.aab" -Force
    Copy-Item -Path $sourceAab -Destination "..\$destAab" -Force
    Write-Host "Copied AAB to root directory"
} else {
    Write-Host "Warning: AAB build output not found, but APK was successfully built."
}

Write-Host "=== BUILD FINISHED SUCCESSFULLY FOR VERSION $version ==="
