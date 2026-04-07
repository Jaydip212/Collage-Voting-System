# ============================================================
# College Voting System – Auto File Watcher & Sync Script
# Run this ONCE in PowerShell. It watches F: drive and
# automatically syncs every change to C:\cxamppnew\htdocs\
# ============================================================

$src  = "f:\project 2026\collage voting system"
$dest = "C:\cxamppnew\htdocs\collage voting system"

Write-Host ""
Write-Host "  ╔══════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "  ║   College Voting System – Auto Sync 🔄       ║" -ForegroundColor Cyan
Write-Host "  ╚══════════════════════════════════════════════╝" -ForegroundColor Cyan
Write-Host ""
Write-Host "  Source : $src" -ForegroundColor Yellow
Write-Host "  Dest   : $dest" -ForegroundColor Green
Write-Host ""
Write-Host "  Watching for file changes... (Press Ctrl+C to stop)" -ForegroundColor Gray
Write-Host ""

# Initial full sync
robocopy "$src" "$dest" /E /XO /XD ".git" "node_modules" /NP /NJH /NJS | Out-Null
Write-Host "  [$(Get-Date -f 'HH:mm:ss')] ✅ Initial full sync done" -ForegroundColor Green

# Set up file system watcher
$watcher                    = New-Object System.IO.FileSystemWatcher
$watcher.Path               = $src
$watcher.Filter             = "*.*"
$watcher.IncludeSubdirectories = $true
$watcher.NotifyFilter       = [System.IO.NotifyFilters]::LastWrite + [System.IO.NotifyFilters]::FileName + [System.IO.NotifyFilters]::DirectoryName

# Debounce timer (avoid multiple syncs on rapid saves)
$global:lastSync = [DateTime]::MinValue

$action = {
    $path    = $Event.SourceEventArgs.FullPath
    $change  = $Event.SourceEventArgs.ChangeType
    $now     = Get-Date

    # Skip files we don't care about
    $skipExts = @('.log','.tmp','.git','.suo','.user')
    $ext = [System.IO.Path]::GetExtension($path).ToLower()
    if ($ext -in $skipExts) { return }
    if ($path -like "*\.git\*") { return }

    # Debounce: wait 800ms between syncs
    if (($now - $global:lastSync).TotalMilliseconds -lt 800) { return }
    $global:lastSync = $now

    # Compute relative path and destination
    $rel     = $path.Substring($src.Length).TrimStart('\')
    $destFile = Join-Path $dest $rel
    $destDir  = Split-Path $destFile -Parent

    try {
        if ($change -eq 'Deleted') {
            if (Test-Path $destFile) {
                Remove-Item $destFile -Force -ErrorAction SilentlyContinue
                Write-Host "  [$(Get-Date -f 'HH:mm:ss')] 🗑️  Deleted  : $rel" -ForegroundColor Red
            }
        } else {
            if (!(Test-Path $destDir)) { New-Item -ItemType Directory -Path $destDir -Force | Out-Null }
            if (Test-Path $path -PathType Leaf) {
                Copy-Item $path $destFile -Force
                Write-Host "  [$(Get-Date -f 'HH:mm:ss')] ✅ Synced   : $rel" -ForegroundColor Green
            }
        }
    } catch {
        Write-Host "  [$(Get-Date -f 'HH:mm:ss')] ⚠️  Error    : $rel – $($_.Exception.Message)" -ForegroundColor Yellow
    }
}

# Register all events
Register-ObjectEvent $watcher 'Changed' -Action $action | Out-Null
Register-ObjectEvent $watcher 'Created' -Action $action | Out-Null
Register-ObjectEvent $watcher 'Deleted' -Action $action | Out-Null
Register-ObjectEvent $watcher 'Renamed' -Action $action | Out-Null

$watcher.EnableRaisingEvents = $true

Write-Host "  🟢 Watcher is ACTIVE — edit files in VS Code, changes go live instantly!" -ForegroundColor Green
Write-Host ""

# Keep script running
try {
    while ($true) { Start-Sleep -Seconds 1 }
} finally {
    $watcher.EnableRaisingEvents = $false
    $watcher.Dispose()
    Write-Host "`n  🔴 Watcher stopped." -ForegroundColor Red
}
