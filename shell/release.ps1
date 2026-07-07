#Requires -Version 5.1
<#
.SYNOPSIS
    Interactive release script: bumps semver tag, pushes to GitHub, and deploys via Deployer.

.DESCRIPTION
    1. Reads the latest git tag (semver X.Y.Z)
    2. Asks whether this is a Major, Minor, or Fix (patch) release
    3. Creates the new tag locally and pushes it to origin
    4. Runs  dep deploy Production  targeting that tag
#>

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

# ---------------------------------------------------------------------------
# Helpers
# ---------------------------------------------------------------------------

function Write-Step([string]$msg) {
    Write-Host "`n==> $msg" -ForegroundColor Cyan
}

function Write-Success([string]$msg) {
    Write-Host "[OK] $msg" -ForegroundColor Green
}

function Write-Fail([string]$msg) {
    Write-Host "[FAIL] $msg" -ForegroundColor Red
}

function Invoke-Git {
    $output = & git @args 2>&1
    if ($LASTEXITCODE -ne 0) {
        Write-Fail "git $args failed:`n$output"
        exit 1
    }
    return $output
}

# ---------------------------------------------------------------------------
# 1. Ensure working directory is clean (warn only, don't block)
# ---------------------------------------------------------------------------

Write-Step "Checking repository state"

$status = & git status --porcelain 2>&1
if ($status) {
    Write-Host "[WARN] You have uncommitted changes:" -ForegroundColor Yellow
    $status | ForEach-Object { Write-Host "   $_" -ForegroundColor Yellow }
    $cont = Read-Host "`nContinue anyway? [y/N]"
    if ($cont -notmatch '^[yY]$') {
        Write-Host "Release cancelled." -ForegroundColor Gray
        exit 0
    }
}

# ---------------------------------------------------------------------------
# 2. Fetch latest tags from remote
# ---------------------------------------------------------------------------

Write-Step "Fetching tags from origin"
Invoke-Git fetch --tags --quiet
Write-Success "Tags fetched"

# ---------------------------------------------------------------------------
# 3. Determine current latest tag
# ---------------------------------------------------------------------------

$latestTag = & git tag --sort=-v:refname 2>$null | Select-Object -First 1

if (-not $latestTag) {
    Write-Host "No existing tags found. Starting from 0.0.0" -ForegroundColor Yellow
    $latestTag = "0.0.0"
}

Write-Host "`nCurrent version : " -NoNewline
Write-Host $latestTag -ForegroundColor Yellow

# ---------------------------------------------------------------------------
# 4. Parse semver (strips optional leading 'v')
# ---------------------------------------------------------------------------

$semver = $latestTag -replace '^v', ''

if ($semver -notmatch '^(\d+)\.(\d+)\.(\d+)$') {
    Write-Fail "Tag '$latestTag' is not in semver format (X.Y.Z). Aborting."
    exit 1
}

[int]$major = $Matches[1]
[int]$minor = $Matches[2]
[int]$patch = $Matches[3]

# ---------------------------------------------------------------------------
# 5. Ask for release type
# ---------------------------------------------------------------------------

Write-Host "`nSelect release type:"
Write-Host "  [1]  Major  ->  $($major + 1).0.0   (breaking changes)"
Write-Host "  [2]  Minor  ->  $major.$($minor + 1).0   (new features)"
Write-Host "  [3]  Fix    ->  $major.$minor.$($patch + 1)   (bug fixes)"
Write-Host ""

do {
    $choice = Read-Host "Enter choice [1/2/3]"
} while ($choice -notmatch '^[123]$')

$newTag = switch ($choice) {
    "1" { "$($major + 1).0.0" }
    "2" { "$major.$($minor + 1).0" }
    "3" { "$major.$minor.$($patch + 1)" }
}

$releaseType = switch ($choice) {
    "1" { "Major" }
    "2" { "Minor" }
    "3" { "Fix" }
}

Write-Host ""
Write-Host "Release type : " -NoNewline; Write-Host $releaseType -ForegroundColor Magenta
Write-Host "New version  : " -NoNewline; Write-Host $newTag -ForegroundColor Green

# ---------------------------------------------------------------------------
# 6. Confirm
# ---------------------------------------------------------------------------

Write-Host ""
$confirm = Read-Host "Create tag $newTag and deploy to Production? [y/N]"
if ($confirm -notmatch '^[yY]$') {
    Write-Host "Release cancelled." -ForegroundColor Gray
    exit 0
}

# ---------------------------------------------------------------------------
# 7. Create and push tag
# ---------------------------------------------------------------------------

Write-Step "Creating tag $newTag"
Invoke-Git tag $newTag
Write-Success "Tag $newTag created locally"

Write-Step "Pushing tag $newTag to origin"
Invoke-Git push origin $newTag
Write-Success "Tag $newTag pushed to origin"

# ---------------------------------------------------------------------------
# 8. Deploy via Deployer (target the new tag)
# ---------------------------------------------------------------------------

Write-Step "Starting deployment of $newTag to Production"

$env:DEPLOY_TAG = "refs/tags/$newTag"

& dep deploy Production
$depExit = $LASTEXITCODE

Remove-Item Env:\DEPLOY_TAG -ErrorAction SilentlyContinue

if ($depExit -ne 0) {
    Write-Fail "Deployment failed (exit code $depExit)."
    Write-Host "Tag $newTag was already pushed to GitHub. Fix the issue and re-deploy with:" -ForegroundColor Yellow
    Write-Host "  `$env:DEPLOY_TAG = 'refs/tags/$newTag'; dep deploy Production" -ForegroundColor Gray
    exit $depExit
}

Write-Host ""
Write-Success "Version $newTag deployed successfully!"
