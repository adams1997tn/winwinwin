@echo off
:: Sport2 Cron Loop for Windows/Laragon
:: Run this in a terminal and leave it open, or set it as a Windows startup task.
:: Press Ctrl+C to stop.

set PHP=C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe
set SYNC=C:\laragon\www\bet\sport2\run_sync.php
set SETTLE=C:\laragon\www\bet\sport2\settle_bets.php

echo [CRON] Starting Sport2 cron loop...
echo [CRON] Live=60s, Upcoming=300s, Full=1800s, Heal=3600s, Settle=300s

set /a TICK=0

:loop
set /a TICK+=1

:: Every 60 seconds: Live sync
echo [%date% %time%] Running --live sync (tick %TICK%)...
start /b "" "%PHP%" "%SYNC%" --live

:: Every 5 minutes (300s / 60 = 5 ticks): Upcoming sync + Settlement
set /a MOD5=%TICK% %% 5
if %MOD5%==0 (
    echo [%date% %time%] Running --upcoming sync...
    start /b "" "%PHP%" "%SYNC%" --upcoming
    echo [%date% %time%] Running settlement...
    start /b "" "%PHP%" "%SETTLE%"
)

:: Every 30 minutes (1800s / 60 = 30 ticks): Full sync
set /a MOD30=%TICK% %% 30
if %MOD30%==0 (
    echo [%date% %time%] Running --full sync...
    start /b "" "%PHP%" "%SYNC%"
)

:: Every 60 minutes (3600s / 60 = 60 ticks): Self-healer
set /a MOD60=%TICK% %% 60
if %MOD60%==0 (
    echo [%date% %time%] Running --heal...
    start /b "" "%PHP%" "%SYNC%" --heal
)

:: Wait 60 seconds
timeout /t 60 /nobreak >nul
goto loop
