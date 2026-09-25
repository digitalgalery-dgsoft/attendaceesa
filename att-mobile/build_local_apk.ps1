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
        Write-Host "Building APK for version: $version (build $($matches[2]))"
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

Write-Host "=== BUILDING APK RELEASE (LOCAL ONLY) ==="
flutter build apk --release
if ($LASTEXITCODE -ne 0) {
    Write-Host "Flutter build APK failed with exit code $LASTEXITCODE"
    exit $LASTEXITCODE
}

$destApk = "app-release-$version.apk"
if (Test-Path $sourceApk) {
    Copy-Item -Path $sourceApk -Destination $destApk -Force
    Copy-Item -Path $sourceApk -Destination "app-release.apk" -Force
    Write-Host "APK created successfully:"
    Write-Host "1) $PWD\$destApk"
    Write-Host "2) $PWD\app-release.apk"
} else {
    Write-Host "Failed to find built APK at $sourceApk"
    exit 1
}

# --- BUILD AAB (Android App Bundle) ---
$sourceAab = "build\app\outputs\bundle\release\app-release.aab"
if (Test-Path $sourceAab) {
    Remove-Item -Path $sourceAab -Force
}

Write-Host "=== BUILDING AAB (APP BUNDLE LOCAL ONLY) ==="
flutter build appbundle --release
if ($LASTEXITCODE -ne 0) {
    Write-Host "Flutter build AAB failed with exit code $LASTEXITCODE"
    exit $LASTEXITCODE
}

$destAab = "app-release-$version.aab"
if (Test-Path $sourceAab) {
    Copy-Item -Path $sourceAab -Destination $destAab -Force
    Copy-Item -Path $sourceAab -Destination "app-release.aab" -Force
    Write-Host "AAB created successfully:"
    Write-Host "1) $PWD\$destAab"
    Write-Host "2) $PWD\app-release.aab"
    Write-Host "Note: Files are kept locally and NOT uploaded to server as requested."
} else {
    Write-Host "Warning: AAB build output not found."
}
