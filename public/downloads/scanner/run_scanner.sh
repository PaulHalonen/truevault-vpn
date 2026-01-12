#!/bin/bash
# TrueVault Network Scanner Launcher for Mac/Linux
# Change to the directory where this script is located
cd "$(dirname "$0")"

echo "================================================"
echo "  TrueVault Network Scanner"
echo "================================================"
echo

# Check for Python
if ! command -v python3 &> /dev/null; then
    echo "ERROR: Python 3 is not installed"
    echo "Please install Python 3.8+ using:"
    echo "  Mac: brew install python3"
    echo "  Linux: sudo apt install python3 python3-pip"
    exit 1
fi

echo "Installing dependencies..."
pip3 install requests --quiet 2>/dev/null || pip install requests --quiet

echo
read -p "Your TrueVault Email: " EMAIL
read -p "Your Auth Token (from dashboard): " TOKEN

echo
echo "Starting scanner..."
echo
python3 "$(dirname "$0")/truevault_scanner.py" "$EMAIL" "$TOKEN"
