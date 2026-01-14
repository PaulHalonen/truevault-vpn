@echo off
REM TruthVault Network Scanner Launcher
cd /d "%~dp0"

echo ================================================
echo   TruthVault Network Scanner
echo ================================================
echo.

python --version >nul 2>&1
if errorlevel 1 (
    echo ERROR: Python is not installed or not in PATH
    echo Please install Python 3.8+ from https://python.org
    echo Make sure to check "Add Python to PATH" during installation
    pause
    exit /b 1
)

echo.
set /p EMAIL="Your TruthVault Email: "
set /p TOKEN="Your Auth Token (from dashboard): "

echo.
echo Starting scanner...
echo.
python "%~dp0truthvault_scanner.py" "%EMAIL%" "%TOKEN%"

echo.
pause
