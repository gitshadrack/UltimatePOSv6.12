[CmdletBinding()]
param(
    [Parameter(Mandatory = $true)]
    [string]$PrintServerSource,

    [Parameter(Mandatory = $true)]
    [string]$PhpRuntimeDirectory,

    [string]$OutputDirectory
)

$ErrorActionPreference = 'Stop'
if ([string]::IsNullOrWhiteSpace($OutputDirectory)) {
    $OutputDirectory = Join-Path $PSScriptRoot 'artifacts'
}
$printServerSource = [IO.Path]::GetFullPath($PrintServerSource)
$phpRuntimeDirectory = [IO.Path]::GetFullPath($PhpRuntimeDirectory)
$outputDirectory = [IO.Path]::GetFullPath($OutputDirectory)
$packageDirectory = Join-Path $outputDirectory 'UltimatePOS-PrintServer'
$zipPath = Join-Path $outputDirectory 'UltimatePOS-PrintServer.zip'

foreach ($requiredFile in @(
    (Join-Path $printServerSource 'server.php'),
    (Join-Path $printServerSource 'vendor\autoload.php'),
    (Join-Path $phpRuntimeDirectory 'php.exe'),
    (Join-Path $phpRuntimeDirectory 'php8ts.dll')
)) {
    if (-not (Test-Path -LiteralPath $requiredFile -PathType Leaf)) {
        throw "Required package input is missing: $requiredFile"
    }
}

if (Test-Path -LiteralPath $packageDirectory) {
    Remove-Item -LiteralPath $packageDirectory -Recurse -Force
}
if (Test-Path -LiteralPath $zipPath) {
    Remove-Item -LiteralPath $zipPath -Force
}

New-Item -ItemType Directory -Path $packageDirectory -Force | Out-Null
New-Item -ItemType Directory -Path (Join-Path $packageDirectory 'payload') -Force | Out-Null

# Bundle the official signed runtime so cashier PCs can install offline.
$prerequisiteDirectory = Join-Path $packageDirectory 'prerequisites'
New-Item -ItemType Directory -Path $prerequisiteDirectory -Force | Out-Null
$redistributable = Join-Path $prerequisiteDirectory 'vc_redist.x64.exe'
[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12
Invoke-WebRequest -Uri 'https://aka.ms/vs/17/release/vc_redist.x64.exe' -OutFile $redistributable -UseBasicParsing
$signature = Get-AuthenticodeSignature -LiteralPath $redistributable
if ($signature.Status -ne 'Valid' -or $signature.SignerCertificate.Subject -notmatch 'O=Microsoft Corporation') {
    throw 'Downloaded Visual C++ Redistributable does not have a valid Microsoft signature.'
}

Copy-Item -LiteralPath $printServerSource -Destination (Join-Path $packageDirectory 'payload\pos_print_server') -Recurse
Copy-Item -LiteralPath $phpRuntimeDirectory -Destination (Join-Path $packageDirectory 'runtime') -Recurse

$payloadOverrides = Join-Path $PSScriptRoot 'payload-overrides'
foreach ($overrideFile in @(
    'server.php',
    'queue-worker.php',
    'lib\DurablePrintQueue.php'
)) {
    $sourceOverride = Join-Path $payloadOverrides $overrideFile
    $destinationOverride = Join-Path $packageDirectory ('payload\pos_print_server\'+$overrideFile)
    if (-not (Test-Path -LiteralPath $sourceOverride -PathType Leaf)) {
        throw "Required print queue override is missing: $sourceOverride"
    }
    Copy-Item -LiteralPath $sourceOverride -Destination $destinationOverride -Force
}

# escpos-php 2.x contains a character-class range that older PCRE accepted but
# PHP 8/PCRE2 rejects. Put the hyphen last so Windows SMB printer paths work.
$windowsConnector = Join-Path $packageDirectory 'payload\pos_print_server\vendor\mike42\escpos-php\src\Mike42\Escpos\PrintConnectors\WindowsPrintConnector.php'
$connectorSource = [IO.File]::ReadAllText($windowsConnector)
$legacySmbCharacterClass = '[\s\d\w-+]'
$compatibleSmbCharacterClass = '[\s\d\w+-]'
if (-not $connectorSource.Contains($legacySmbCharacterClass)) {
    throw "The expected legacy SMB printer expression was not found in $windowsConnector."
}
$connectorSource = $connectorSource.Replace($legacySmbCharacterClass, $compatibleSmbCharacterClass)
$legacyImplodeCall = 'implode($this -> buffer)'
$compatibleImplodeCall = 'implode("", $this -> buffer)'
if (-not $connectorSource.Contains($legacyImplodeCall)) {
    throw "The expected legacy implode call was not found in $windowsConnector."
}
$connectorSource = $connectorSource.Replace($legacyImplodeCall, $compatibleImplodeCall)
[IO.File]::WriteAllText(
    $windowsConnector,
    $connectorSource,
    [Text.UTF8Encoding]::new($false)
)

# The bundled UltimatePOS print formatter also uses the PHP 7 implode argument
# order, which became an error in PHP 8.
$escposFormatter = Join-Path $packageDirectory 'payload\pos_print_server\lib\Escpos.php'
$formatterSource = [IO.File]::ReadAllText($escposFormatter)
$legacyLineImplodeCall = 'implode($allLines, "\n")'
$compatibleLineImplodeCall = 'implode("\n", $allLines)'
if (-not $formatterSource.Contains($legacyLineImplodeCall)) {
    throw "The expected legacy line implode call was not found in $escposFormatter."
}
$formatterSource = $formatterSource.Replace($legacyLineImplodeCall, $compatibleLineImplodeCall)
[IO.File]::WriteAllText(
    $escposFormatter,
    $formatterSource,
    [Text.UTF8Encoding]::new($false)
)

# Use the receipt formatter that tolerates an unavailable optional logo.
Copy-Item -LiteralPath (Join-Path $payloadOverrides 'lib\Escpos.php') -Destination $escposFormatter -Force

foreach ($fileName in @(
    'Install.cmd',
    'Uninstall.cmd',
    'Test Printer Server.cmd',
    'Install-PrintServer.ps1',
    'Uninstall-PrintServer.ps1',
    'Start-PrintServer.ps1',
    'Test-PrintServer.ps1',
    'README.md'
)) {
    Copy-Item -LiteralPath (Join-Path $PSScriptRoot $fileName) -Destination (Join-Path $packageDirectory $fileName)
}

Copy-Item -LiteralPath (Join-Path $PSScriptRoot 'runtime-php.ini') -Destination (Join-Path $packageDirectory 'runtime\php.ini') -Force

$tarCommand = Get-Command 'tar.exe' -ErrorAction SilentlyContinue
if ($tarCommand) {
    & $tarCommand.Source -a -c -f $zipPath -C $outputDirectory (Split-Path -Leaf $packageDirectory)
    if ($LASTEXITCODE -ne 0) {
        throw "tar.exe failed to create the deployment ZIP (exit code $LASTEXITCODE)."
    }
} else {
Compress-Archive -LiteralPath $packageDirectory -DestinationPath $zipPath -CompressionLevel Optimal
}

Write-Host "Deployment package created: $zipPath" -ForegroundColor Green

$innoCompilerCandidates = @(
    'C:\Program Files (x86)\Inno Setup 6\ISCC.exe',
    'C:\Program Files\Inno Setup 6\ISCC.exe'
)
$innoCompiler = $innoCompilerCandidates |
    Where-Object { Test-Path -LiteralPath $_ -PathType Leaf } |
    Select-Object -First 1
$innoScript = Join-Path $PSScriptRoot 'UltimatePOS-PrintServer-Setup.iss'

if ($innoCompiler -and (Test-Path -LiteralPath $innoScript -PathType Leaf)) {
    & $innoCompiler $innoScript
    if ($LASTEXITCODE -ne 0) {
        throw "Inno Setup failed to build the setup executable (exit code $LASTEXITCODE)."
    }

    Write-Host (
        'Setup executable created: ' +
        (Join-Path $outputDirectory 'UltimatePOS-PrintServer-Setup.exe')
    ) -ForegroundColor Green
} else {
    Write-Warning 'Inno Setup 6 was not found; the ZIP was created without a Setup.exe.'
}
