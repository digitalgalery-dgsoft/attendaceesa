@echo off
title Antigravity AI Token & Quota Monitor
cd /d "%~dp0"
echo ============================================================
echo   MENJALANKAN ANTIGRAVITY AI TOKEN & QUOTA MONITOR...
echo ============================================================
echo Membuka browser ke http://127.0.0.1:8765 ...
start "" "http://127.0.0.1:8765"
echo Server berjalan di latar belakang (Port 8765)...
python server.py
pause
