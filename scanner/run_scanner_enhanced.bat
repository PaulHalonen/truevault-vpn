@echo off
REM TruthVault Enhanced Network Scanner - Windows Launcher
cd /d "%~dp0"

echo ========================================================
echo   TruthVault Enhanced Network Scanner v3.0
echo   NEW: Aggressive Camera Detection!
echo ========================================================
echo.

REM Check for Python
python --version >nul 2>&1
if errorlevel 1 (
    echo ERROR: Python is not installed or not in PATH
    echo Please install Python 3.8+ from https://python.org
    echo Make sure to check "Add Python to PATH" during installation
    pause
    exit /b 1
)

echo Installing dependencies...
pip install flask requests --quiet --disable-pip-version-check

echo.
set /p EMAIL="Your TruthVault Email: "
set /p TOKEN="Your Auth Token (from dashboard): "

echo.
echo Starting enhanced scanner...
echo.
python "%~dp0truthvault_scanner_enhanced.py" "%EMAIL%" "%TOKEN%"

echo.
pause
