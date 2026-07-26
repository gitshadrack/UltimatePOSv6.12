@echo off
setlocal
title UltimatePOS Print Server Uninstall
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0Uninstall-PrintServer.ps1"
echo.
pause

