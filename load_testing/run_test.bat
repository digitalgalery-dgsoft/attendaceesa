@echo off
set K6_BIN=C:\Program Files\k6\k6.exe

if not exist "%K6_BIN%" (
    echo [ERROR] k6.exe tidak ditemukan di %K6_BIN%
    pause
    exit /b 1
)

echo ===================================================
echo   MENJALANKAN K6 STRESS TEST (PRESENSI ESA)
echo ===================================================
echo.

if "%~1"=="" (
    "%K6_BIN%" run stress_test.js
) else (
    "%K6_BIN%" run %* stress_test.js
)
