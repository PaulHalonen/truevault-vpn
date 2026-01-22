#!/bin/bash
# TrueVault Network Scanner Launcher (Mac/Linux)
# Brute Force Camera Discovery
cd "$(dirname "$0")"

echo "================================================"
echo "  TrueVault Network Scanner v2.0"
echo "  Brute Force Camera Discovery"
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
pip3 install flask requests --quiet 2>/dev/null || pip install flask requests --quiet

echo
read -p "Your TrueVault Email: " EMAIL
read -p "Your Auth Token (from dashboard): " TOKEN

echo
echo "Starting scanner..."
echo
python3 "$(dirname "$0")/truthvault_scanner.py" "$EMAIL" "$TOKEN"
