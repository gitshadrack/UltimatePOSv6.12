@echo off
setlocal
title UltimatePOS Print Server Test
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%LOCALAPPDATA%\UltimatePOS\PrintServer\Test-PrintServer.ps1"
echo.
pause

