[CmdletBinding()]
param(
    [string]$PackageRoot,
    [string]$InstallDirectory = (Join-Path $env:LOCALAPPDATA 'UltimatePOS\PrintServer')
)

$ErrorActionPreference = 'Stop'
if ([string]::IsNullOrWhiteSpace($PackageRoot)) {
    $PackageRoot = $PSScriptRoot
}
$taskName = 'UltimatePOS Print Server'
$allowedParent = [IO.Path]::GetFullPath((Join-Path $env:LOCALAPPDATA 'UltimatePOS'))
$InstallDirectory = [IO.Path]::GetFullPath($InstallDirectory)
$serverPayload = Join-Path $PackageRoot 'payload\pos_print_server'
$runtimePayload = Join-Path $PackageRoot 'runtime'
$sourceLauncher = Join-Path $PackageRoot 'Start-PrintServer.ps1'
$sourceTester = Join-Path $PackageRoot 'Test-PrintServer.ps1'
$sourceUninstaller = Join-Path $PackageRoot 'Uninstall-PrintServer.ps1'

Write-Host 'Installing UltimatePOS Print Server...' -ForegroundColor Cyan

if (-not $InstallDirectory.StartsWith($allowedParent, [StringComparison]::OrdinalIgnoreCase)) {
    throw "Refusing to install into an unexpected directory: $InstallDirectory"
}

foreach ($requiredFile in @(
    (Join-Path $serverPayload 'server.php'),
    (Join-Path $serverPayload 'vendor\autoload.php'),
    (Join-Path $runtimePayload 'php.exe'),
    $sourceLauncher,
    $sourceTester,
    $sourceUninstaller
)) {
    if (-not (Test-Path -LiteralPath $requiredFile -PathType Leaf)) {
        throw "The deployment package is incomplete. Missing: $requiredFile"
    }
}

$existingTask = Get-ScheduledTask -TaskName $taskName -ErrorAction SilentlyContinue
if ($existingTask) {
    Stop-ScheduledTask -TaskName $taskName -ErrorAction SilentlyContinue
    Unregister-ScheduledTask -TaskName $taskName -Confirm:$false
}

$oldProcesses = Get-CimInstance Win32_Process -ErrorAction SilentlyContinue |
    Where-Object {
        $_.CommandLine -and
        $_.CommandLine.IndexOf($InstallDirectory, [StringComparison]::OrdinalIgnoreCase) -ge 0 -and
        ($_.CommandLine -match 'server\.php|queue-worker\.php|Start-PrintServer\.ps1')
    }
foreach ($process in $oldProcesses) {
    Stop-Process -Id $process.ProcessId -Force -ErrorAction SilentlyContinue
}

New-Item -ItemType Directory -Path $InstallDirectory -Force | Out-Null
New-Item -ItemType Directory -Path (Join-Path $InstallDirectory 'logs') -Force | Out-Null

foreach ($replaceableDirectory in @('server', 'runtime')) {
    $replaceablePath = Join-Path $InstallDirectory $replaceableDirectory
    if (Test-Path -LiteralPath $replaceablePath) {
        Remove-Item -LiteralPath $replaceablePath -Recurse -Force
    }
}

Copy-Item -LiteralPath $serverPayload -Destination (Join-Path $InstallDirectory 'server') -Recurse -Force
Copy-Item -LiteralPath $runtimePayload -Destination (Join-Path $InstallDirectory 'runtime') -Recurse -Force
Copy-Item -LiteralPath $sourceLauncher -Destination (Join-Path $InstallDirectory 'Start-PrintServer.ps1') -Force
Copy-Item -LiteralPath $sourceTester -Destination (Join-Path $InstallDirectory 'Test-PrintServer.ps1') -Force
Copy-Item -LiteralPath $sourceUninstaller -Destination (Join-Path $InstallDirectory 'Uninstall-PrintServer.ps1') -Force

$installedLauncher = Join-Path $InstallDirectory 'Start-PrintServer.ps1'
$taskArguments = "-NoProfile -WindowStyle Hidden -ExecutionPolicy Bypass -File `"$installedLauncher`""
$action = New-ScheduledTaskAction -Execute 'powershell.exe' -Argument $taskArguments
$currentUser = [System.Security.Principal.WindowsIdentity]::GetCurrent().Name
$trigger = New-ScheduledTaskTrigger -AtLogOn -User $currentUser
$principal = New-ScheduledTaskPrincipal -UserId $currentUser -LogonType Interactive -RunLevel Limited
$settings = New-ScheduledTaskSettingsSet `
    -StartWhenAvailable `
    -RestartCount 20 `
    -RestartInterval (New-TimeSpan -Minutes 1) `
    -MultipleInstances IgnoreNew `
    -ExecutionTimeLimit ([TimeSpan]::Zero
    )

$task = New-ScheduledTask -Action $action -Trigger $trigger -Principal $principal -Settings $settings
Register-ScheduledTask -TaskName $taskName -InputObject $task -Force | Out-Null

$programsDirectory = Join-Path $env:APPDATA 'Microsoft\Windows\Start Menu\Programs\UltimatePOS'
New-Item -ItemType Directory -Path $programsDirectory -Force | Out-Null
$shell = New-Object -ComObject WScript.Shell

$testShortcut = $shell.CreateShortcut((Join-Path $programsDirectory 'Test Print Server.lnk'))
$testShortcut.TargetPath = 'powershell.exe'
$testShortcut.Arguments = "-NoProfile -ExecutionPolicy Bypass -File `"$(Join-Path $InstallDirectory 'Test-PrintServer.ps1')`""
$testShortcut.WorkingDirectory = $InstallDirectory
$testShortcut.Save()

$uninstallShortcut = $shell.CreateShortcut((Join-Path $programsDirectory 'Uninstall Print Server.lnk'))
$uninstallShortcut.TargetPath = 'powershell.exe'
$uninstallShortcut.Arguments = "-NoProfile -ExecutionPolicy Bypass -File `"$(Join-Path $InstallDirectory 'Uninstall-PrintServer.ps1')`""
$uninstallShortcut.WorkingDirectory = $InstallDirectory
$uninstallShortcut.Save()

Start-ScheduledTask -TaskName $taskName
Start-Sleep -Seconds 3

$installedTester = Join-Path $InstallDirectory 'Test-PrintServer.ps1'
& powershell.exe -NoProfile -ExecutionPolicy Bypass -File $installedTester -Quiet
$testExitCode = $LASTEXITCODE
if ($testExitCode -ne 0) {
    Write-Warning "Installation completed, but the server did not answer on port 6441. Check $InstallDirectory\logs."
    exit 2
}

Write-Host 'Installation completed successfully.' -ForegroundColor Green
Write-Host 'The print server will start automatically whenever this Windows user signs in.'
Write-Host 'Cashiers do not need to run any commands.'
