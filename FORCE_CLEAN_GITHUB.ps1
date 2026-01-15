# Force Clean GitHub Repository
# This removes all old build files and keeps only documentation

Write-Host "=============================================" -ForegroundColor Cyan
Write-Host "  FORCE CLEAN GITHUB REPOSITORY" -ForegroundColor Cyan
Write-Host "=============================================" -ForegroundColor Cyan
Write-Host ""

cd E:\Documents\GitHub\truevault-vpn

# Remove all old folders
Write-Host "Step 1: Removing old folders..." -ForegroundColor Yellow
$foldersToDelete = @(
    "admin",
    "api",
    "dashboard",
    "database",
    "downloads",
    "public",
    "scripts",
    "server-scripts",
    "server-setup"
)

foreach ($folder in $foldersToDelete) {
    if (git ls-files $folder | Select-Object -First 1) {
        Write-Host "  Removing $folder from git..." -ForegroundColor Gray
        git rm -r $folder
    }
}

# Remove specific files
Write-Host ""
Write-Host "Step 2: Removing old files..." -ForegroundColor Yellow

$filesToDelete = @(
    ".htaccess",
    "DEPLOYMENT.md",
    "*.py",
    "*.ps1",
    "*.html",
    "*.php",
    "*.txt",
    "*.json",
    "*.sql"
)

foreach ($pattern in $filesToDelete) {
    $files = git ls-files $pattern
    if ($files) {
        Write-Host "  Removing $pattern files..." -ForegroundColor Gray
        git rm $files 2>$null
    }
}

# Keep only documentation files
Write-Host ""
Write-Host "Step 3: Adding back documentation..." -ForegroundColor Yellow
git reset -- MASTER_BLUEPRINT/
git reset -- Master_Checklist/
git reset -- reference/
git reset -- README.md
git reset -- .gitignore

git add MASTER_BLUEPRINT/
git add Master_Checklist/
git add reference/
git add README.md
git add .gitignore

Write-Host ""
Write-Host "Step 4: Checking what will be deleted..." -ForegroundColor Yellow
$deletions = git status --short | Where-Object { $_ -like "D *" }
Write-Host "  Files to delete: $($deletions.Count)" -ForegroundColor Red

Write-Host ""
Write-Host "Ready to commit!" -ForegroundColor Green
Write-Host "  Run: git commit -m 'Remove all old build files - keep only documentation'"
Write-Host "  Then: git push origin main --force"
