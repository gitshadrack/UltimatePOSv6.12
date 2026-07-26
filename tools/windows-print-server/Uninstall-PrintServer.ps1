[CmdletBinding()]
param(
    [string]$InstallDirectory = (Join-Path $env:LOCALAPPDATA 'UltimatePOS\PrintServer'),
    [switch]$FinalCleanup
)

$ErrorActionPreference = 'Stop'
$taskName = 'UltimatePOS Print Server'
$allowedParent = [IO.Path]::GetFullPath((Join-Path $env:LOCALAPPDATA 'UltimatePOS'))
$resolvedTarget = [IO.Path]::GetFullPath($InstallDirectory)

if (-not $resolvedTarget.StartsWith($allowedParent, [StringComparison]::OrdinalIgnoreCase)) {
    throw "Refusing to remove an unexpected directory: $resolvedTarget"
}

if ($FinalCleanup) {
    Start-Sleep -Seconds 2
    if (Test-Path -LiteralPath $resolvedTarget) {
        Remove-Item -LiteralPath $resolvedTarget -Recurse -Force
    }
    exit 0
}

Write-Host 'Uninstalling UltimatePOS Print Server...' -ForegroundColor Cyan
if (Get-ScheduledTask -TaskName $taskName -ErrorAction SilentlyContinue) {
    Stop-ScheduledTask -TaskName $taskName -ErrorAction SilentlyContinue
    Unregister-ScheduledTask -TaskName $taskName -Confirm:$false
}

$agentProcesses = Get-CimInstance Win32_Process -ErrorAction SilentlyContinue |
    Where-Object {
        $_.CommandLine -and
        $_.CommandLine.IndexOf($resolvedTarget, [StringComparison]::OrdinalIgnoreCase) -ge 0 -and
        ($_.CommandLine -match 'server\.php|Start-PrintServer\.ps1')
    }
foreach ($process in $agentProcesses) {
    Stop-Process -Id $process.ProcessId -Force -ErrorAction SilentlyContinue
}

$programsDirectory = Join-Path $env:APPDATA 'Microsoft\Windows\Start Menu\Programs\UltimatePOS'
foreach ($shortcutName in @('Test Print Server.lnk', 'Uninstall Print Server.lnk')) {
    $shortcut = Join-Path $programsDirectory $shortcutName
    if (Test-Path -LiteralPath $shortcut) {
        Remove-Item -LiteralPath $shortcut -Force
    }
}

$temporaryUninstaller = Join-Path $env:TEMP "UltimatePOS-PrintServer-Uninstall-$PID.ps1"
Copy-Item -LiteralPath $MyInvocation.MyCommand.Path -Destination $temporaryUninstaller -Force
Start-Process -FilePath 'powershell.exe' -WindowStyle Hidden -ArgumentList @(
    '-NoProfile',
    '-ExecutionPolicy', 'Bypass',
    '-File', "`"$temporaryUninstaller`"",
    '-InstallDirectory', "`"$resolvedTarget`"",
    '-FinalCleanup'
)

Write-Host 'UltimatePOS Print Server has been removed.' -ForegroundColor Green

