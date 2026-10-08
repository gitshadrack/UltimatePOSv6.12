# UltimatePOS Windows Print Server

This guide covers configuration, installation, testing, daily operation,
offline receipt recovery, cash-drawer use, updating, and troubleshooting.

The Print Server runs on each Windows cashier computer. It allows an online or
hosted UltimatePOS website to print directly to a receipt printer attached to
that computer. Cashiers do not need WAMP, XAMPP, PHP commands, or an open
terminal window.

## How printing works

1. The cashier completes a sale in UltimatePOS.
2. The browser sends the receipt to `ws://127.0.0.1:6441`.
3. The local Print Server saves the receipt in its disk queue.
4. The hidden queue worker sends it to the configured Windows printer.
5. If the printer is unavailable, the worker keeps retrying automatically.

`127.0.0.1` always means the cashier's own computer. The hosted ERP server
does not need access to the shop's USB printer.

## Requirements

- Windows 10 or Windows 11.
- The Windows user account normally used by the cashier.
- A supported ESC/POS thermal receipt printer.
- The correct Windows printer driver.
- Access to UltimatePOS Settings and Business Locations during configuration.
- `UltimatePOS-PrintServer-Setup.exe` supplied by the system administrator.

## 1. Configure the receipt printer in Windows

Complete these steps before installing the Print Server.

1. Connect and power on the receipt printer.
2. Install the manufacturer's Windows driver.
3. Open **Windows Settings > Bluetooth & devices > Printers & scanners**.
4. Select the receipt printer and open **Printer properties**.
5. Print a Windows test page.
6. Do not continue until the Windows test page prints correctly.

For a USB printer, share the working Windows printer queue:

1. Open **Printer properties > Sharing**.
2. Enable **Share this printer**.
3. Use a short share name without spaces, for example `ReceiptPrinter`.
4. Apply the changes.

The resulting UltimatePOS path is:

```text
smb://localhost/ReceiptPrinter
```

Important:

- Share the printer queue that successfully prints a Windows test page.
- Do not enter a USB port such as `USB001` or `USB003` as the UltimatePOS
  printer path.
- Do not share an old duplicate printer showing `Offline`, `Error`, `COM`, or
  `LPT` unless it is the queue that actually prints.
- If the Windows share name changes, update the path in UltimatePOS.

## USB, local-port, and network printer paths

### Printer connected through `USB001`, `USB002`, or `USB003`

`USB001`, `USB002`, and `USB003` are internal Windows port assignments. Do not
enter them as the UltimatePOS printer path.

1. Confirm the printer using that USB port prints a Windows test page.
2. Share the working Windows printer queue as `ReceiptPrinter`.
3. Configure UltimatePOS as:

```text
Connection Type: Windows
Path: smb://localhost/ReceiptPrinter
```

Windows automatically routes the shared printer job to its assigned USB port.

### Printer connected through `LPT1` or `COM1`

The bundled connector accepts direct local ports from `LPT1` to `LPT9` and
`COM1` to `COM9`. A path such as `LPT1` or `COM1` should only be used when the
printer is genuinely connected or correctly mapped to that port.

For ordinary USB printers, use a shared Windows printer queue instead of trying
to map the USB port as `LPT1`.

### ESC/POS network printer using raw TCP

When the printer supports raw ESC/POS printing over TCP:

```text
Connection Type: Network
IP Address: 192.168.1.50
Port: 9100
Path: leave blank
```

Replace `192.168.1.50` with the printer's actual address. Port `9100` is common,
but confirm the correct raw-printing port in the printer manual or
configuration page.

The cashier computer must be on a network that can reach the printer. A
technician can test the connection with:

```powershell
Test-NetConnection 192.168.1.50 -Port 9100
```

`TcpTestSucceeded` should be `True`. Give the printer a static IP address or a
DHCP reservation so its address does not change.

### Network printer installed through Windows

If the network printer does not support raw TCP printing, or it was installed
using a Windows/WSD driver:

1. Confirm it prints a Windows test page.
2. Share the working Windows printer queue as `ReceiptPrinter`.
3. Use:

```text
Connection Type: Windows
Path: smb://localhost/ReceiptPrinter
```

Do not enter a Windows WSD port name, `USB001`, or an IPP web address as the
path. The durable receipt queue works with both a Windows-shared printer and a
direct TCP network printer.

## 2. Install the Print Server

The current installer can be obtained without a flash disk:

- In UltimatePOS, open **Settings > Business Settings > System**, then select
  **Open System Downloads**.
- Use **Download Print Server** to download it from the hosted ERP.
- If the ERP copy is temporarily unavailable, use **Download from repository**
  on the same page.

The version, file size, and SHA-256 checksum are displayed so a technician can
confirm the downloaded file matches the published repository artifact.

1. Sign in to Windows using the account the cashier normally uses.
2. Run `UltimatePOS-PrintServer-Setup.exe`.
3. If Windows SmartScreen appears, use **More info > Run anyway** only after
   confirming that the installer came from the trusted system administrator.
4. Wait for the installer to report that the Print Server is ready.
5. Open **Windows Start > UltimatePOS > Test Print Server**.

A healthy result is:

```text
UltimatePOS Print Server is READY on ws://127.0.0.1:6441.
```

The installer bundles the official Microsoft Visual C++ x64 Redistributable.
If bundled PHP cannot start, setup installs the runtime first and Windows asks
for administrator approval (or administrator credentials). No internet is
needed on the cashier PC. The Print Server still installs for the signed-in
cashier account. If Windows requires a restart, restart and rerun setup.

The installer:

- installs under `%LOCALAPPDATA%\UltimatePOS\PrintServer`;
- creates the `UltimatePOS Print Server` Scheduled Task;
- starts the service immediately;
- starts it automatically whenever that Windows user signs in;
- restarts it after a failure;
- adds Test and Uninstall shortcuts to the Windows Start menu.

Install it separately while signed in as each Windows user who operates that
cashier computer. Cashiers do not run the installer or commands every day.

## 3. Create the printer in UltimatePOS

Sign in using an account allowed to change settings:

1. Open **Settings > Receipt Printers**.
2. Add a new printer.
3. Enter a clear name, such as `Front Counter Receipt Printer`.
4. Set **Connection Type** to `Windows`.
5. Set **Capability Profile** to the profile supported by the printer. Start
   with `Default` if the exact model is not listed.
6. Set **Characters per line**:
   - `32` for most 58 mm printers;
   - `42` or `48` for most 80 mm printers.
7. Enter the Windows share path:

```text
smb://localhost/ReceiptPrinter
```

8. Save the printer.

The path is case-insensitive on most Windows installations, but using the exact
share name avoids configuration mistakes.

## 4. Assign the printer to a business location

Creating a printer does not automatically assign it to invoices.

1. Open **Settings > Business Locations**.
2. Edit the required location or open its settings.
3. Open **Receipt Settings**.
4. Select **Use Configured Receipt Printer**.
5. Select the printer created in the previous section.
6. Enable automatic printing after invoice/sale completion if required.
7. Save the location.
8. Sign the cashier out and back in if the old assignment remains visible.

Repeat this assignment for every business location that should use the
printer. In a multitenant installation, each business must configure and
assign its own printer.

## 5. Perform the first test

1. Confirm the printer is powered on, contains paper, and has no warning light.
2. Open the POS screen.
3. Wait for the indicator to show **Printer ready**.
4. Complete one low-value test sale or reprint an existing test invoice.
5. Confirm the correct receipt prints once.
6. Compare the invoice number on paper with the transaction in UltimatePOS.
7. If a drawer is connected, test **Open Drawer** after receipt printing works.

Use `Ctrl+F5` if the browser still displays the old printer messages after a
system update.

## Printer status messages

| POS status | Meaning | Action |
| --- | --- | --- |
| `Connecting to printer` | The browser is opening the local connection. | Wait briefly. |
| `Printer ready` | The local Print Server is reachable. | Printing can be attempted. |
| `Sending receipt` | The browser is delivering the receipt to the local queue. | Do not submit the sale again. |
| `Receipt queued` | The receipt is safely stored on the cashier computer. | Wait for automatic printing. |
| `Printing` | The worker is sending the job to Windows. | Wait. |
| `Receipt printed` | The worker completed the print operation. | No action is required. |
| `Printer offline` | The local server or printer is unavailable. | Check power, cable, Windows queue, and the Print Server test. |
| `Print expired` | The receipt could not print within 24 hours. | Reprint it manually after fixing the printer. |

`Printer ready` confirms that the browser can reach the local Print Server. It
does not prove that the physical printer, driver, share, paper, or USB cable is
working. A printed Windows test page and a printed UltimatePOS test receipt
confirm the complete path.

## Offline receipt queue

Configured-printer receipts are stored under:

```text
%LOCALAPPDATA%\UltimatePOS\PrintServer\queue
```

The receipt is written to disk before the Print Server acknowledges it.
Failures are retried after 5, 15, 30, 60, 120, and then every 300 seconds.

Queue safeguards:

- The same print job ID is not intentionally queued twice.
- Pending receipts survive a Print Server update and Windows restart.
- Printed receipt payloads are deleted; small status records remain for 48
  hours.
- Unprinted receipts expire after 24 hours to prevent very old receipts
  printing unexpectedly.
- Open Drawer commands are never queued or replayed.
- A receipt cannot enter the disk queue while the local Print Server itself is
  stopped. The automatic Scheduled Task minimizes this condition.
- A power loss at the exact moment printing completes can very rarely produce
  a duplicate retry. Check unexpected duplicates using the invoice number.

Do not manually edit or delete queue files during normal operation. Before
uninstalling, confirm that no genuine receipt remains pending because
uninstalling removes the local queue.

## Cash drawer configuration

The drawer must normally be connected to the receipt printer's `DK`/drawer
port, not directly to the hosted ERP server.

1. Confirm normal receipt printing works.
2. Connect the correct RJ11/RJ12 drawer cable to the printer drawer port.
3. Ensure the drawer is physically unlocked.
4. Assign the cashier role the **Open cash drawer from POS** permission.
5. Sign the cashier out and back in.
6. Test the **Open Drawer** button.

Drawer commands are immediate and are not saved for later. This prevents a
drawer opening unexpectedly after a printer or computer recovers.

## Daily cashier procedure

The cashier normally only needs to:

1. Sign in to Windows.
2. Power on the printer.
3. Open UltimatePOS.
4. Confirm **Printer ready** before the first sale.

No command window or manual server startup is required.

If a receipt is queued, the cashier should not repeat the sale. The sale
already exists and the receipt will retry automatically. A supervisor can
reprint the invoice later if the job expires.

## Supervisor checks

Start with these non-technical checks:

1. Verify the printer has power and paper.
2. Clear paper jams and close the printer cover.
3. Confirm the USB/network cable is connected.
4. Print a Windows test page.
5. Run **Windows Start > UltimatePOS > Test Print Server**.
6. Check that the correct printer is assigned to the business location.
7. Reload the POS with `Ctrl+F5`.

Technicians can verify the scheduled task and port:

```powershell
Get-ScheduledTask -TaskName "UltimatePOS Print Server" |
  Select-Object TaskName, State

Get-NetTCPConnection -State Listen -LocalPort 6441
```

The task should be `Running`, and port `6441` should be listening on
`127.0.0.1`.

Logs are located at:

```text
%LOCALAPPDATA%\UltimatePOS\PrintServer\logs
```

The main launcher log is `print-server.log`. Queue attempts and printer errors
are recorded in `queue-worker.log`.

## Troubleshooting

### Printer ready, but no receipt prints

1. Print a Windows test page.
2. Confirm the shared queue is the same queue that printed the test page.
3. Confirm its share name is exactly `ReceiptPrinter`, or update UltimatePOS
   to match the actual name.
4. Confirm the UltimatePOS path is
   `smb://localhost/ReceiptPrinter`.
5. Confirm **Connection Type** is `Windows`.
6. Confirm the printer is assigned under the correct Business Location's
   Receipt Settings.
7. Check `queue-worker.log` for `Failed to copy file to printer`.

Do not use `USB003` as the path. `USB003` is a Windows port name, not a shared
printer path accepted by this configuration.

### Printer offline

1. Run **Test Print Server**.
2. If the test fails, sign out of Windows and sign back in.
3. If it still fails, rerun the current Setup EXE.
4. If the test succeeds, check printer power, paper, cable, Windows status, and
   the Windows test page.
5. Click the red POS printer indicator or reload with `Ctrl+F5`.

### Receipts remain queued

- Restore the same printer share instead of creating a different share name.
- Confirm Windows can print a test page.
- Review `queue-worker.log`.
- Leave the Print Server running; the queue retries automatically.
- Do not complete the sale again merely to obtain another receipt.
- After 24 hours, fix the printer and reprint the invoice manually.

### Wrong width or wrapped text

- Use `32` characters for most 58 mm paper.
- Try `42` or `48` for 80 mm paper.
- Select a different Capability Profile if special characters, cutting, or
  alignment is incorrect.

### Cash drawer does not open

- Confirm receipt printing works first.
- Check that the drawer cable is connected to the printer's drawer port.
- Confirm the drawer is unlocked.
- Confirm the cashier has the required permission.
- Verify that the selected Capability Profile supports the printer.

### Browser still shows old messages

1. Run `php artisan optimize:clear` on the ERP application server.
2. Reload the POS using `Ctrl+F5`.
3. If a PWA is installed, close and reopen it after refreshing in the browser.

## Updating the Print Server

Run the newer `UltimatePOS-PrintServer-Setup.exe` while signed in as the same
cashier Windows user. The updater stops the old server and queue worker,
replaces the application and PHP runtime, recreates the Scheduled Task,
preserves pending receipts, and starts the new version.

After updating:

1. Run **Test Print Server**.
2. Confirm **Printer ready** on the POS.
3. Print one test receipt.
4. Do not uninstall first; uninstalling removes pending receipts.

## Uninstalling

Before uninstalling, confirm that there are no pending genuine receipts.
Then open **Windows Start > UltimatePOS > Uninstall Print Server**.

Uninstalling stops the local processes, removes the Scheduled Task, and removes
the installed files, logs, statuses, and pending receipt queue.

## Security

- The agent listens only on `127.0.0.1:6441`.
- Do not expose port `6441` through the router or public internet.
- Distribute the installer only through a trusted administrator.
- Production-wide distribution should use a code-signing certificate.
- Do not store Windows administrator or SMB passwords in printer paths unless
  specifically required and securely managed.

## Building the deployment package

Technicians can build the installer on the development computer:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass `
  -File .\Build-DeploymentPackage.ps1 `
  -PrintServerSource "C:\path\to\pos_print_server" `
  -PhpRuntimeDirectory "C:\path\to\portable-php"
```

Outputs:

- `artifacts\UltimatePOS-PrintServer-Setup.exe` — preferred installer.
- `artifacts\UltimatePOS-PrintServer.zip` — manual fallback package.

The PHP directory must contain a portable Windows PHP runtime including
`php.exe`, `php8ts.dll`, and its required DLLs. Inno Setup 6 is required to
produce the EXE.

During packaging, the builder applies the PHP 8/PCRE2 compatibility corrections
required by the legacy official print-server source, including its SMB path
expression and legacy `implode()` calls.
