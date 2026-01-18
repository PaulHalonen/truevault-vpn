#!/bin/bash
# TruthVault Enhanced Network Scanner - Mac/Linux Launcher
cd "$(dirname "$0")"

echo "========================================================"
echo "  TruthVault Enhanced Network Scanner v3.0"
echo "  NEW: Aggressive Camera Detection!"
echo "========================================================"
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
read -p "Your TruthVault Email: " EMAIL
read -p "Your Auth Token (from dashboard): " TOKEN

echo
echo "Starting enhanced scanner..."
echo
python3 "$(dirname "$0")/truthvault_scanner_enhanced.py" "$EMAIL" "$TOKEN"
