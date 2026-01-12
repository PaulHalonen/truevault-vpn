@echo off
REM TrueVault Network Scanner Launcher for Windows
REM Change to the directory where this script is located
cd /d "%~dp0"

echo ================================================
echo   TrueVault Network Scanner
echo ================================================
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
pip install requests --quiet --disable-pip-version-check

echo.
set /p EMAIL="Your TrueVault Email: "
set /p TOKEN="Your Auth Token (from dashboard): "

echo.
echo Starting scanner...
echo.
python "%~dp0truevault_scanner.py" "%EMAIL%" "%TOKEN%"

echo.
pause
