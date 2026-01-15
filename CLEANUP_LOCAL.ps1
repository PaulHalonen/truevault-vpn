# TRUEVAULT VPN - CLEANUP SCRIPT
# This script safely cleans up the local folder, keeping only the Master Checklist and reference files
# Created: January 15, 2026

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  TRUEVAULT VPN - CLEANUP SCRIPT" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Define the base directory
$baseDir = "E:\Documents\GitHub\truevault-vpn"

# Folders/files to KEEP
$keepItems = @(
    ".git",
    ".gitignore",
    "Master_Checklist",
    "reference",
    "README.md"
)

Write-Host "This script will:" -ForegroundColor Yellow
Write-Host "  ✓ KEEP: Master_Checklist folder (all your documentation)" -ForegroundColor Green
Write-Host "  ✓ KEEP: reference folder (chat logs)" -ForegroundColor Green
Write-Host "  ✓ KEEP: .git folder (git history)" -ForegroundColor Green
Write-Host "  ✓ KEEP: .gitignore" -ForegroundColor Green
Write-Host "  ✓ KEEP: README.md" -ForegroundColor Green
Write-Host "  ✗ DELETE: Everything else" -ForegroundColor Red
Write-Host ""

# Get all items in the directory
$allItems = Get-ChildItem -Path $baseDir -Force

Write-Host "Items to be DELETED:" -ForegroundColor Red
Write-Host "--------------------" -ForegroundColor Red

$itemsToDelete = @()

foreach ($item in $allItems) {
    if ($keepItems -notcontains $item.Name) {
        $itemsToDelete += $item
        if ($item.PSIsContainer) {
            Write-Host "  [FOLDER] $($item.Name)" -ForegroundColor Red
        } else {
            Write-Host "  [FILE]   $($item.Name)" -ForegroundColor Red
        }
    }
}

Write-Host ""
Write-Host "Total items to delete: $($itemsToDelete.Count)" -ForegroundColor Yellow
Write-Host ""

# Confirmation
$confirmation = Read-Host "Are you sure you want to delete these items? (yes/no)"

if ($confirmation -eq "yes") {
    Write-Host ""
    Write-Host "Starting cleanup..." -ForegroundColor Yellow
    Write-Host ""
    
    $deletedCount = 0
    $errorCount = 0
    
    foreach ($item in $itemsToDelete) {
        try {
            if ($item.PSIsContainer) {
                Remove-Item -Path $item.FullName -Recurse -Force
                Write-Host "  ✓ Deleted folder: $($item.Name)" -ForegroundColor Green
            } else {
                Remove-Item -Path $item.FullName -Force
                Write-Host "  ✓ Deleted file:   $($item.Name)" -ForegroundColor Green
            }
            $deletedCount++
        } catch {
            Write-Host "  ✗ ERROR deleting: $($item.Name) - $($_.Exception.Message)" -ForegroundColor Red
            $errorCount++
        }
    }
    
    Write-Host ""
    Write-Host "========================================" -ForegroundColor Cyan
    Write-Host "  CLEANUP COMPLETE!" -ForegroundColor Cyan
    Write-Host "========================================" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "Successfully deleted: $deletedCount items" -ForegroundColor Green
    if ($errorCount -gt 0) {
        Write-Host "Errors: $errorCount items" -ForegroundColor Red
    }
    Write-Host ""
    Write-Host "Remaining items:" -ForegroundColor Green
    Get-ChildItem -Path $baseDir -Force | ForEach-Object {
        if ($_.PSIsContainer) {
            Write-Host "  [FOLDER] $($_.Name)" -ForegroundColor Green
        } else {
            Write-Host "  [FILE]   $($_.Name)" -ForegroundColor Green
        }
    }
    Write-Host ""
    Write-Host "Your Master_Checklist and reference folders are safe!" -ForegroundColor Green
    Write-Host ""
    
} else {
    Write-Host ""
    Write-Host "Cleanup cancelled. No files were deleted." -ForegroundColor Yellow
    Write-Host ""
}

Write-Host "Press any key to exit..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
