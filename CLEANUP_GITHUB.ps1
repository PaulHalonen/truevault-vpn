# GitHub Repository Cleanup Script
# Purpose: Remove all build files, keep only documentation
# Created: January 15, 2026

Write-Host "=============================================" -ForegroundColor Cyan
Write-Host "  GITHUB REPOSITORY CLEANUP" -ForegroundColor Cyan
Write-Host "=============================================" -ForegroundColor Cyan
Write-Host ""

$repoPath = "E:\Documents\GitHub\truevault-vpn"
Set-Location $repoPath

# Verify we're in the right place
if (-not (Test-Path ".git")) {
    Write-Host "ERROR: Not in a Git repository!" -ForegroundColor Red
    exit
}

Write-Host "Current repository: truevault-vpn" -ForegroundColor Green
Write-Host ""

# Step 1: Check what files exist
Write-Host "Step 1: Analyzing current files..." -ForegroundColor Yellow
Write-Host ""

$allFiles = Get-ChildItem -Recurse -File -Force | Where-Object { $_.FullName -notlike "*\.git\*" }
$totalFiles = $allFiles.Count
Write-Host "  Total files in repo: $totalFiles" -ForegroundColor Gray

# Step 2: Identify files to keep
Write-Host ""
Write-Host "Step 2: Identifying files to KEEP..." -ForegroundColor Yellow
Write-Host ""

$keepPatterns = @(
    "MASTER_BLUEPRINT",
    "Master_Checklist",
    "reference",
    "README.md",
    ".gitignore",
    "CLEANUP",
    "chat_log.txt"
)

$filesToKeep = $allFiles | Where-Object {
    $keep = $false
    foreach ($pattern in $keepPatterns) {
        if ($_.FullName -like "*$pattern*") {
            $keep = $true
            break
        }
    }
    $keep
}

Write-Host "  Files to KEEP: $($filesToKeep.Count)" -ForegroundColor Green
Write-Host ""
Write-Host "  Keeping:" -ForegroundColor Gray
$filesToKeep | ForEach-Object {
    $relativePath = $_.FullName.Replace("$repoPath\", "")
    Write-Host "    ✓ $relativePath" -ForegroundColor Green
} | Select-Object -First 20

if ($filesToKeep.Count -gt 20) {
    Write-Host "    ... and $($filesToKeep.Count - 20) more" -ForegroundColor Gray
}

# Step 3: Identify files to remove
Write-Host ""
Write-Host "Step 3: Identifying files to REMOVE..." -ForegroundColor Yellow
Write-Host ""

$filesToRemove = $allFiles | Where-Object {
    $remove = $true
    foreach ($pattern in $keepPatterns) {
        if ($_.FullName -like "*$pattern*") {
            $remove = $false
            break
        }
    }
    $remove
}

Write-Host "  Files to REMOVE: $($filesToRemove.Count)" -ForegroundColor Red
Write-Host ""
Write-Host "  Removing:" -ForegroundColor Gray
$filesToRemove | ForEach-Object {
    $relativePath = $_.FullName.Replace("$repoPath\", "")
    Write-Host "    ✗ $relativePath" -ForegroundColor Red
} | Select-Object -First 20

if ($filesToRemove.Count -gt 20) {
    Write-Host "    ... and $($filesToRemove.Count - 20) more" -ForegroundColor Gray
}

# Step 4: Confirmation
Write-Host ""
Write-Host "=============================================" -ForegroundColor Yellow
Write-Host "  CLEANUP SUMMARY" -ForegroundColor Yellow
Write-Host "=============================================" -ForegroundColor Yellow
Write-Host ""
Write-Host "  Total files: $totalFiles" -ForegroundColor Gray
Write-Host "  Will KEEP: $($filesToKeep.Count) files" -ForegroundColor Green
Write-Host "  Will REMOVE: $($filesToRemove.Count) files" -ForegroundColor Red
Write-Host ""
Write-Host "  This will:" -ForegroundColor Cyan
Write-Host "    ✓ Keep all MASTER_BLUEPRINT files" -ForegroundColor Green
Write-Host "    ✓ Keep all Master_Checklist files" -ForegroundColor Green
Write-Host "    ✓ Keep README.md and .gitignore" -ForegroundColor Green
Write-Host "    ✗ Remove all admin/ files" -ForegroundColor Red
Write-Host "    ✗ Remove all api/ files" -ForegroundColor Red
Write-Host "    ✗ Remove all dashboard/ files" -ForegroundColor Red
Write-Host "    ✗ Remove all database/ files" -ForegroundColor Red
Write-Host "    ✗ Remove all build scripts" -ForegroundColor Red
Write-Host ""

$confirmation = Read-Host "Do you want to proceed? (yes/no)"

if ($confirmation -ne "yes") {
    Write-Host ""
    Write-Host "Cleanup cancelled. No changes made." -ForegroundColor Yellow
    exit
}

# Step 5: Remove files using git
Write-Host ""
Write-Host "Step 5: Removing files from Git..." -ForegroundColor Yellow
Write-Host ""

# Get unique directories to remove
$dirsToRemove = $filesToRemove | 
    ForEach-Object { $_.DirectoryName.Replace("$repoPath\", "").Split('\')[0] } |
    Select-Object -Unique |
    Where-Object { $_ -ne "" }

foreach ($dir in $dirsToRemove) {
    if (Test-Path $dir) {
        Write-Host "  Removing directory: $dir" -ForegroundColor Red
        git rm -rf $dir 2>$null
        if (Test-Path $dir) {
            Remove-Item -Recurse -Force $dir
        }
    }
}

# Remove individual files in root that aren't in directories
$rootFilesToRemove = $filesToRemove | Where-Object { 
    $_.DirectoryName -eq $repoPath 
}

foreach ($file in $rootFilesToRemove) {
    $relativePath = $file.FullName.Replace("$repoPath\", "")
    Write-Host "  Removing file: $relativePath" -ForegroundColor Red
    git rm -f $relativePath 2>$null
    if (Test-Path $file.FullName) {
        Remove-Item -Force $file.FullName
    }
}

Write-Host ""
Write-Host "✓ Files removed from Git" -ForegroundColor Green

# Step 6: Verify what's left
Write-Host ""
Write-Host "Step 6: Verifying remaining files..." -ForegroundColor Yellow
Write-Host ""

$remainingFiles = Get-ChildItem -Recurse -File -Force | 
    Where-Object { $_.FullName -notlike "*\.git\*" }

Write-Host "  Remaining files: $($remainingFiles.Count)" -ForegroundColor Green
Write-Host ""
Write-Host "  Directory structure:" -ForegroundColor Gray
Get-ChildItem -Directory | ForEach-Object {
    Write-Host "    📁 $($_.Name)/" -ForegroundColor Cyan
}

# Step 7: Check Git status
Write-Host ""
Write-Host "Step 7: Git status..." -ForegroundColor Yellow
Write-Host ""

$gitStatus = git status --short
if ($gitStatus) {
    Write-Host "  Changes ready to commit:" -ForegroundColor Green
    Write-Host $gitStatus
} else {
    Write-Host "  No changes detected (files may already be removed)" -ForegroundColor Gray
}

# Step 8: Instructions for next steps
Write-Host ""
Write-Host "=============================================" -ForegroundColor Cyan
Write-Host "  CLEANUP COMPLETE!" -ForegroundColor Cyan
Write-Host "=============================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Next steps:" -ForegroundColor Yellow
Write-Host ""
Write-Host "1. Review the changes in GitHub Desktop" -ForegroundColor White
Write-Host "2. Commit with message: 'Clean repository - keep only documentation'" -ForegroundColor White
Write-Host "3. Push to GitHub" -ForegroundColor White
Write-Host ""
Write-Host "Your GitHub repository will then contain:" -ForegroundColor Green
Write-Host "  ✓ MASTER_BLUEPRINT/ (complete technical specs)" -ForegroundColor Green
Write-Host "  ✓ Master_Checklist/ (complete build instructions)" -ForegroundColor Green
Write-Host "  ✓ README.md" -ForegroundColor Green
Write-Host "  ✓ .gitignore" -ForegroundColor Green
Write-Host ""
Write-Host "All build files are safely backed up in:" -ForegroundColor Cyan
Write-Host "  E:\Backup\Full Website Backup Jan-14-2026\" -ForegroundColor Cyan
Write-Host ""
Write-Host "Ready to commit!" -ForegroundColor Green
Write-Host ""
