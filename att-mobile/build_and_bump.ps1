$ErrorActionPreference = "Stop"

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
    Write-Host "Flutter build failed with exit code $LASTEXITCODE"
    exit $LASTEXITCODE
}
$destApk = "app-release-$version.apk"

if (Test-Path $sourceApk) {
    Copy-Item -Path $sourceApk -Destination $destApk -Force
    Write-Host "APK built successfully: $destApk"
    
    $adminPublic = "..\att-admin-v12\public"
    if (Test-Path $adminPublic) {
        Copy-Item -Path $sourceApk -Destination "$adminPublic\app-release.apk" -Force
        Copy-Item -Path $sourceApk -Destination "$adminPublic\$destApk" -Force
        Write-Host "Copied to $adminPublic\app-release.apk and $adminPublic\$destApk"
    }
} else {
    Write-Host "Failed to build APK."
    exit 1
}

