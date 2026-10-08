#define PackageSource SourcePath + "artifacts\UltimatePOS-PrintServer"

[Setup]
AppId=UltimatePOS.PrintServer.Bootstrapper
AppName=UltimatePOS Print Server
AppVersion=1.2.0
AppPublisher=Sysnettechs Solutions
DefaultDirName={localappdata}\UltimatePOS\PrintServer
DisableDirPage=yes
DisableProgramGroupPage=yes
PrivilegesRequired=lowest
Uninstallable=no
OutputDir={#SourcePath}\artifacts
OutputBaseFilename=UltimatePOS-PrintServer-Setup
Compression=lzma2/ultra64
SolidCompression=yes
WizardStyle=modern
SetupLogging=yes
ArchitecturesAllowed=x64compatible

[Files]
Source: "{#PackageSource}\*"; DestDir: "{tmp}\UltimatePOS-PrintServer"; Flags: ignoreversion recursesubdirs createallsubdirs

[Code]
procedure CurStepChanged(CurStep: TSetupStep);
var
  ResultCode: Integer;
  PowerShellPath: String;
  InstallerScript: String;
  PackageRoot: String;
  Parameters: String;
  ShowCommand: Integer;
begin
  if CurStep <> ssPostInstall then
    exit;

  PowerShellPath := ExpandConstant('{sys}\WindowsPowerShell\v1.0\powershell.exe');
  PackageRoot := ExpandConstant('{tmp}\UltimatePOS-PrintServer');
  InstallerScript := PackageRoot + '\Install-PrintServer.ps1';
  Parameters :=
    '-NoProfile -ExecutionPolicy Bypass -File "' + InstallerScript +
    '" -PackageRoot "' + PackageRoot + '"';

  if WizardSilent then
    ShowCommand := SW_HIDE
  else
    ShowCommand := SW_SHOW;

  if not Exec(
    PowerShellPath,
    Parameters,
    PackageRoot,
    ShowCommand,
    ewWaitUntilTerminated,
    ResultCode
  ) then
    RaiseException('Unable to start the UltimatePOS Print Server installer.');

  if ResultCode <> 0 then
    RaiseException(
      'UltimatePOS Print Server installation failed with exit code ' +
      IntToStr(ResultCode) + '.'
    );
end;
