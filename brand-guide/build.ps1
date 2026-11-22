# Builds the self-contained HTML and the print PDF for the Fennec Dash brand guide.
# Usage:  powershell -ExecutionPolicy Bypass -File brand-guide\build.ps1

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $MyInvocation.MyCommand.Path
$template = Join-Path $root 'index.html'
$outHtml  = Join-Path $root 'FennecDash-Brand-Guide.html'
$outPdf   = Join-Path $root 'FennecDash-Brand-Guide.pdf'

function DataUri([string]$path, [string]$mime) {
    $bytes = [IO.File]::ReadAllBytes($path)
    return "data:$mime;base64," + [Convert]::ToBase64String($bytes)
}

Write-Host 'Encoding assets...'
$html = Get-Content -LiteralPath $template -Raw -Encoding UTF8

$html = $html.Replace('__PJS_SRC__',   (DataUri (Join-Path $root 'fonts\PlusJakartaSans-var.woff2') 'font/woff2'))
$html = $html.Replace('__INTER_SRC__', (DataUri (Join-Path $root 'fonts\Inter-var.woff2') 'font/woff2'))
$html = $html.Replace('__IMG_LOGO_DARK__',  (DataUri (Join-Path $root 'assets\logo-dark.png') 'image/png'))
$html = $html.Replace('__IMG_LOGO_LIGHT__', (DataUri (Join-Path $root 'assets\logo-light.png') 'image/png'))
$html = $html.Replace('__IMG_MARK__',       (DataUri (Join-Path $root 'assets\mark.png') 'image/png'))
$html = $html.Replace('__IMG_OG__',         (DataUri (Join-Path $root 'assets\og-image.png') 'image/png'))

[IO.File]::WriteAllText($outHtml, $html, (New-Object System.Text.UTF8Encoding($false)))
Write-Host ("HTML written: {0} ({1:N0} KB)" -f $outHtml, ((Get-Item $outHtml).Length / 1KB))

$chrome = @(
    'C:\Program Files\Google\Chrome\Application\chrome.exe',
    'C:\Program Files (x86)\Google\Chrome\Application\chrome.exe',
    'C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe',
    'C:\Program Files\Microsoft\Edge\Application\msedge.exe'
) | Where-Object { Test-Path $_ } | Select-Object -First 1

if (-not $chrome) { throw 'Chrome or Edge was not found.' }

if (Test-Path $outPdf) { Remove-Item $outPdf -Force }

$uri = ([Uri]$outHtml).AbsoluteUri
$args = @(
    '--headless=new',
    '--disable-gpu',
    '--no-first-run',
    '--no-default-browser-check',
    '--virtual-time-budget=15000',
    '--run-all-compositor-stages-before-draw',
    "--print-to-pdf=$outPdf",
    $uri
)

Write-Host "Printing with $chrome ..."
& $chrome @args | Out-Null

if (-not (Test-Path $outPdf)) { throw 'PDF was not produced.' }

$pdfBytes = [IO.File]::ReadAllBytes($outPdf)
$pdfText  = [Text.Encoding]::ASCII.GetString($pdfBytes)
$pages    = ([regex]::Matches($pdfText, '/Type\s*/Page[^s]')).Count
$hash     = (Get-FileHash $outPdf -Algorithm SHA256).Hash

Write-Host ''
Write-Host ("PDF:      {0}" -f $outPdf)
Write-Host ("Size:     {0:N0} bytes" -f $pdfBytes.Length)
Write-Host ("Pages:    {0}" -f $pages)
Write-Host ("SHA256:   {0}" -f $hash)
