# UltimatePOS Windows Print Server Deployment

This package turns the official UltimatePOS `pos_print_server` into an
automatic per-user background agent. Cashiers do not run PHP commands and no
command window remains open.

## Build the deployment ZIP

Run this once on the technician/development computer:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\Build-DeploymentPackage.ps1 `
  -PrintServerSource "C:\path\to\pos_print_server" `
  -PhpRuntimeDirectory "C:\path\to\portable-php"
```

The output contains:

- `artifacts\UltimatePOS-PrintServer-Setup.exe` — preferred one-click installer.
- `artifacts\UltimatePOS-PrintServer.zip` — manual/fallback deployment package.

The PHP directory must be a portable Windows PHP runtime containing `php.exe`,
`php8ts.dll`, and the required DLLs. The build replaces its `php.ini` with the
minimal configuration included here. `Setup.exe` is produced when Inno Setup 6
is installed on the build computer.

During packaging, the build also corrects the legacy `escpos-php` Windows SMB
printer regular expression and reversed `implode()` call so they work with PHP
8/PCRE2. Without these compatibility corrections, valid paths such as
`smb://localhost/receipt_printer` are rejected or fail before Windows receives
the print job.

## Install on a cashier computer

1. Install the thermal printer driver and print a Windows test page.
2. For a USB Windows printer, share it with a simple name such as
   `receipt_printer`.
3. Run `UltimatePOS-PrintServer-Setup.exe` once while signed in as the cashier
   Windows user.
5. Confirm the installer reports that the server is ready.
6. In UltimatePOS, configure the Windows printer path as
   `smb://localhost/receipt_printer`, then assign it to the business location.

If Windows SmartScreen appears because the EXE is not digitally signed, choose
`More info` only after confirming the file came from the trusted system
administrator. Production-wide distribution should use a code-signing
certificate.

The installer copies files to:

```text
%LOCALAPPDATA%\UltimatePOS\PrintServer
```

It registers the `UltimatePOS Print Server` Scheduled Task for the current
Windows user, starts it immediately, restarts it after failures, and starts it
automatically at every sign-in.

## Support and diagnostics

The Windows Start menu contains:

- `UltimatePOS > Test Print Server`
- `UltimatePOS > Uninstall Print Server`

Logs are stored in:

```text
%LOCALAPPDATA%\UltimatePOS\PrintServer\logs
```

The agent only listens on `127.0.0.1:6441`. Do not expose port 6441 through the
internet or router.

## Offline receipt queue

Version 1.1 saves each configured-printer receipt under:

```text
%LOCALAPPDATA%\UltimatePOS\PrintServer\queue
```

The receipt is written to disk before the print server acknowledges it. A
separate hidden queue worker retries failed jobs after 5, 15, 30, 60, 120, and
then 300 seconds. The POS displays short states: `Sending receipt`,
`Receipt queued`, `Printing`, and `Receipt printed`.

Important behavior:

- Repeated messages with the same print job ID are deduplicated.
- After printing, the receipt payload is deleted and only small status metadata
  remains for 48 hours.
- Unprinted receipts expire after 24 hours so stale receipts do not print days
  later.
- Updating the agent preserves the queue directory.
- Uninstalling the agent removes its pending queue.
- Open Drawer commands are never queued or replayed.
- If the local print server itself is stopped, the browser cannot deliver a
  new job to the disk queue. The automatic startup task minimizes this case.
- A power failure immediately after physical printing but before status is
  saved can very rarely cause a duplicate retry. Supervisors should reconcile
  unexpected duplicates against the invoice number.

Queue-worker diagnostics are written to:

```text
%LOCALAPPDATA%\UltimatePOS\PrintServer\logs\queue-worker.log
```

## Updating

Build a new ZIP and run its `Install.cmd`. The installer replaces the local
payload and runtime, preserves pending receipts, recreates the scheduled task,
and starts the new version.
