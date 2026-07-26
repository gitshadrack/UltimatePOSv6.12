[CmdletBinding()]
param()

$ErrorActionPreference = 'Stop'
$agentDirectory = Split-Path -Parent $MyInvocation.MyCommand.Path
$phpExecutable = Join-Path $agentDirectory 'runtime\php.exe'
$serverDirectory = Join-Path $agentDirectory 'server'
$serverScript = Join-Path $serverDirectory 'server.php'
$queueWorkerScript = Join-Path $serverDirectory 'queue-worker.php'
$logDirectory = Join-Path $agentDirectory 'logs'
$standardLog = Join-Path $logDirectory 'print-server.log'
$errorLog = Join-Path $logDirectory 'print-server-error.log'

if (-not (Test-Path -LiteralPath $phpExecutable -PathType Leaf)) {
    throw "Bundled PHP runtime is missing: $phpExecutable"
}

if (-not (Test-Path -LiteralPath $serverScript -PathType Leaf)) {
    throw "POS Print Server is missing: $serverScript"
}

if (-not (Test-Path -LiteralPath $queueWorkerScript -PathType Leaf)) {
    throw "POS print queue worker is missing: $queueWorkerScript"
}

New-Item -ItemType Directory -Path $logDirectory -Force | Out-Null

foreach ($logFile in @($standardLog, $errorLog)) {
    if ((Test-Path -LiteralPath $logFile) -and (Get-Item -LiteralPath $logFile).Length -gt 5MB) {
        $archive = "$logFile.previous"
        if (Test-Path -LiteralPath $archive) {
            Remove-Item -LiteralPath $archive -Force
        }
        Move-Item -LiteralPath $logFile -Destination $archive
    }
}

while ($true) {
    try {
        $existingListener = Get-NetTCPConnection -State Listen -LocalPort 6441 -ErrorAction SilentlyContinue
        if ($existingListener) {
            Add-Content -LiteralPath $standardLog -Value "$(Get-Date -Format o) Port 6441 is already in use; launcher is stopping."
            exit 0
        }

        Add-Content -LiteralPath $standardLog -Value "$(Get-Date -Format o) Starting UltimatePOS Print Server."
        Push-Location $serverDirectory
        $queueWorker = $null
        try {
            $queueWorker = Start-Process `
                -FilePath $phpExecutable `
                -ArgumentList @('-d', 'display_errors=0', '-d', 'log_errors=0', $queueWorkerScript) `
                -WorkingDirectory $serverDirectory `
                -WindowStyle Hidden `
                -PassThru
            & $phpExecutable -d display_errors=0 -d log_errors=0 $serverScript 2>&1 |
                ForEach-Object {
                    Add-Content -LiteralPath $standardLog -Value $_ -Encoding UTF8
                }
        } finally {
            if ($queueWorker -and -not $queueWorker.HasExited) {
                Stop-Process -Id $queueWorker.Id -Force -ErrorAction SilentlyContinue
            }
            Pop-Location
        }
    } catch {
        Add-Content -LiteralPath $errorLog -Value "$(Get-Date -Format o) $($_.Exception.Message)"
    }

    Add-Content -LiteralPath $standardLog -Value "$(Get-Date -Format o) Print server stopped; retrying in 5 seconds."
    Start-Sleep -Seconds 5
}
