$ErrorActionPreference = 'Stop'
$pptPath = "d:\Project\attendace\Resume_Cyber_Security_dan_PlayStore_ESA_Mobile.pptx"
$outDir = "d:\Project\attendace\scratch_slides"

if (-not (Test-Path $outDir)) {
    New-Item -ItemType Directory -Path $outDir -Force | Out-Null
}

$ppt = New-Object -ComObject PowerPoint.Application
$prs = $ppt.Presentations.Open($pptPath, 0, 0, 0)

for ($i = 1; $i -le $prs.Slides.Count; $i++) {
    $imgFile = Join-Path $outDir ("slide_{0:D2}.png" -f $i)
    $prs.Slides.Item($i).Export($imgFile, "PNG", 1920, 1080)
    Write-Output "Exported slide $i to $imgFile"
}

$prs.Close()
$ppt.Quit()
[System.Runtime.Interopservices.Marshal]::ReleaseComObject($prs) | Out-Null
[System.Runtime.Interopservices.Marshal]::ReleaseComObject($ppt) | Out-Null
[System.GC]::Collect()

Write-Output "All slides exported successfully to $outDir!"
