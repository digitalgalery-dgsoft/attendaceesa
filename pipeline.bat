@echo off
setlocal
set "MSG=%~1"
if "%MSG%"=="" set "MSG=Update system features and configuration"

powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0pipeline.ps1" -CommitMessage "%MSG%"
endlocal
