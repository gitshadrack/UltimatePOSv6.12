@echo off
setlocal
title UltimatePOS Print Server Setup
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0Install-PrintServer.ps1"
echo.
pause

