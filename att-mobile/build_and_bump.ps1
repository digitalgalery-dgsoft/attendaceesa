$ErrorActionPreference = "Stop"

# Use D:\Temp, H:\Temp, or G:\Temp for Gradle and Java bundle tool temp files to prevent drive C: disk full error
if (Test-Path "D:\Temp") {
    $env:TEMP = "D:\Temp"
    $env:TMP = "D:\Temp"
    $env:_JAVA_OPTIONS = "-Djava.io.tmpdir=D:\Temp"
    $env:GRADLE_OPTS = "-Djava.io.tmpdir=D:\Temp"
} elseif (Test-Path "H:\Temp") {
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

$content = Get-Content $pubspecPath
$newContent = @()
$version = ""

foreach ($line in $content) {
    if ($line -match "^version:\s*(.*)\+(.*)") {
        $vNum = $matches[1]
        $bNum = [int]$matches[2]
        
        # Split vNum into parts
        $parts = $vNum.Split('.')
        $patch = [int]$parts[2] + 1
        $newVNum = "{0}.{1}.{2}" -f $parts[0], $parts[1], $patch
        $newBNum = $bNum + 1
        
        $version = "$newVNum"
        $newLine = "version: $newVNum+$newBNum"
        $newContent += $newLine
        Write-Host "Bumping version from $vNum+$bNum to $newVNum+$newBNum"
    } else {
        $newContent += $line
    }
}

$newContent | Set-Content $pubspecPath

$sourceApk = "build\app\outputs\flutter-apk\app-release.apk"
if (Test-Path $sourceApk) {
    Remove-Item -Path $sourceApk -Force
}

Write-Host "Building APK..."
flutter build apk --release
if ($LASTEXITCODE -ne 0) {
    Write-Host "Flutter build APK failed with exit code $LASTEXITCODE"
    exit $LASTEXITCODE
}
$destApk = "app-release-$version.apk"

if (Test-Path $sourceApk) {
    Copy-Item -Path $sourceApk -Destination $destApk -Force
    Copy-Item -Path $sourceApk -Destination "app-release.apk" -Force
    Write-Host "APK built successfully: $destApk"
    
    $adminPublic = "..\att-admin-v12\public"
    if (Test-Path $adminPublic) {
        Copy-Item -Path $sourceApk -Destination "$adminPublic\app-release.apk" -Force
        Copy-Item -Path $sourceApk -Destination "$adminPublic\$destApk" -Force
        Copy-Item -Path $sourceApk -Destination "$adminPublic\app-release-1.0.158.apk" -Force
        Write-Host "Copied APK to $adminPublic\app-release.apk and $adminPublic\$destApk"
    }
    Copy-Item -Path $sourceApk -Destination "..\app-release.apk" -Force
    Copy-Item -Path $sourceApk -Destination "..\$destApk" -Force
    Copy-Item -Path $sourceApk -Destination "..\app-release-1.0.158.apk" -Force
    Write-Host "Copied APK to ..\app-release.apk and ..\$destApk"
} else {
    Write-Host "Failed to build APK."
    exit 1
}

# --- BUILD AAB (Android App Bundle for Google Play Store) ---
$sourceAab = "build\app\outputs\bundle\release\app-release.aab"
if (Test-Path $sourceAab) {
    Remove-Item -Path $sourceAab -Force
}

Write-Host "Building AAB (App Bundle)..."
flutter build appbundle --release
$destAab = "app-release-$version.aab"

if (Test-Path $sourceAab) {
    Copy-Item -Path $sourceAab -Destination $destAab -Force
    Copy-Item -Path $sourceAab -Destination "app-release.aab" -Force
    Copy-Item -Path $sourceAab -Destination "app-release-1.0.158.aab" -Force
    Write-Host "AAB built successfully: $destAab"
    
    $adminPublic = "..\att-admin-v12\public"
    if (Test-Path $adminPublic) {
        Copy-Item -Path $sourceAab -Destination "$adminPublic\app-release.aab" -Force
        Copy-Item -Path $sourceAab -Destination "$adminPublic\$destAab" -Force
        Copy-Item -Path $sourceAab -Destination "$adminPublic\app-release-1.0.158.aab" -Force
        Write-Host "Copied AAB to $adminPublic\app-release.aab and $adminPublic\$destAab"
    }
    Copy-Item -Path $sourceAab -Destination "..\app-release.aab" -Force
    Copy-Item -Path $sourceAab -Destination "..\$destAab" -Force
    Copy-Item -Path $sourceAab -Destination "..\app-release-1.0.158.aab" -Force
    Write-Host "Copied AAB to ..\app-release.aab and ..\$destAab"
} else {
    Write-Host "Failed to build AAB."
    exit 1
}

