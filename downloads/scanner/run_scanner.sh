#!/bin/bash
# TruthVault Network Scanner Launcher
cd "$(dirname "$0")"

echo "================================================"
echo "  TruthVault Network Scanner"
echo "================================================"
echo

if ! command -v python3 &> /dev/null; then
    echo "ERROR: Python 3 is not installed"
    echo "Please install Python 3.8+ using:"
    echo "  Mac: brew install python3"
    echo "  Linux: sudo apt install python3 python3-pip"
    exit 1
fi

echo
read -p "Your TruthVault Email: " EMAIL
read -p "Your Auth Token (from dashboard): " TOKEN

echo
echo "Starting scanner..."
echo
python3 "$(dirname "$0")/truthvault_scanner.py" "$EMAIL" "$TOKEN"
