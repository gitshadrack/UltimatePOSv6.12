[CmdletBinding()]
param(
    [switch]$Quiet
)

$ErrorActionPreference = 'Stop'
$socket = [System.Net.WebSockets.ClientWebSocket]::new()
$timeout = [System.Threading.CancellationTokenSource]::new()
$timeout.CancelAfter([TimeSpan]::FromSeconds(4))

try {
    $null = $socket.ConnectAsync([Uri]'ws://127.0.0.1:6441', $timeout.Token).GetAwaiter().GetResult()
    if ($socket.State -ne [System.Net.WebSockets.WebSocketState]::Open) {
        throw "The WebSocket did not reach the Open state."
    }

    if (-not $Quiet) {
        Write-Host 'UltimatePOS Print Server is READY on ws://127.0.0.1:6441.' -ForegroundColor Green
    }
    exit 0
} catch {
    if (-not $Quiet) {
        Write-Host 'UltimatePOS Print Server is NOT AVAILABLE.' -ForegroundColor Red
        Write-Host $_.Exception.Message -ForegroundColor Yellow
        Write-Host "Check: $env:LOCALAPPDATA\UltimatePOS\PrintServer\logs" -ForegroundColor Yellow
    }
    exit 1
} finally {
    if ($socket.State -eq [System.Net.WebSockets.WebSocketState]::Open) {
        $closeTimeout = [System.Threading.CancellationTokenSource]::new()
        $closeTimeout.CancelAfter([TimeSpan]::FromSeconds(1))
        try {
            $null = $socket.CloseAsync(
                [System.Net.WebSockets.WebSocketCloseStatus]::NormalClosure,
                'Test complete',
                $closeTimeout.Token
            ).GetAwaiter().GetResult()
        } catch {
            # The legacy server may close before acknowledging the close frame.
        }
        $closeTimeout.Dispose()
    }
    $timeout.Dispose()
    $socket.Dispose()
}
