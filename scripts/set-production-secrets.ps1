<#
.SYNOPSIS
    Sets the five GitHub Actions secrets the production deploy needs.

.DESCRIPTION
    Run this yourself, in your own terminal, from the repo root:

        powershell -ExecutionPolicy Bypass -File scripts\set-production-secrets.ps1

    It asks for the database and FTP details, generates APP_KEY and
    DEPLOY_TOKEN, fills in .env.production.example and sends everything
    straight to GitHub. No value is ever printed or written to disk.
    See docs/production-setup.md.
#>

$ErrorActionPreference = 'Stop'
$repo = 'swens2005/mission-control'
$template = Join-Path $PSScriptRoot '..\.env.production.example'

function Read-Plain([string] $prompt) {
    $value = Read-Host -Prompt $prompt
    if ([string]::IsNullOrWhiteSpace($value)) { throw "$prompt cannot be empty." }
    return $value.Trim()
}

function Read-Secret([string] $prompt) {
    $secure = Read-Host -Prompt $prompt -AsSecureString
    $bstr = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($secure)
    try { $value = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($bstr) }
    finally { [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($bstr) }
    if ([string]::IsNullOrEmpty($value)) { throw "$prompt cannot be empty." }
    return $value
}

function New-RandomBytes([int] $count) {
    $bytes = New-Object byte[] $count
    $rng = [Security.Cryptography.RandomNumberGenerator]::Create()
    try { $rng.GetBytes($bytes) } finally { $rng.Dispose() }
    return , $bytes
}

# Sends the value on stdin with no trailing newline, so it never appears in
# the process list and PowerShell 5.1 can't mangle quotes or encoding.
function Set-RepoSecret([string] $name, [string] $value) {
    $psi = New-Object Diagnostics.ProcessStartInfo 'gh', "secret set $name --repo $repo"
    $psi.UseShellExecute = $false
    $psi.RedirectStandardInput = $true
    $psi.RedirectStandardOutput = $true
    $psi.RedirectStandardError = $true
    $process = [Diagnostics.Process]::Start($psi)
    $stdin = New-Object IO.StreamWriter($process.StandardInput.BaseStream, (New-Object Text.UTF8Encoding $false))
    $stdin.Write($value)
    $stdin.Close()
    $errors = $process.StandardError.ReadToEnd()
    $process.WaitForExit()
    if ($process.ExitCode -ne 0) { throw "Setting $name failed: $errors" }
    Write-Host "  set $name" -ForegroundColor Green
}

gh auth status *> $null
if ($LASTEXITCODE -ne 0) { throw 'Log in to GitHub first: gh auth login' }

$existing = gh secret list --repo $repo --json name --jq '.[].name'
if ($existing -contains 'ENV_MISSION_CONTROL_PROD') {
    Write-Warning 'The production secrets already exist. Running this again creates a NEW APP_KEY, which logs everyone out and makes stored two-factor secrets unreadable.'
    if ((Read-Host 'Type REPLACE to continue') -ne 'REPLACE') { Write-Host 'Nothing changed.'; exit 0 }
}

Write-Host "`nDatabase (from cPanel > MySQL Databases, including the prefix, e.g. codefhdb_mission_control)"
$values = [ordered] @{
    DB_DATABASE = Read-Plain 'Database name'
    DB_USERNAME = Read-Plain 'Database user'
    DB_PASSWORD = Read-Secret 'Database password (hidden)'
}

Write-Host "`nFTP (the same values as the meagan-swenson repo's secrets)"
$ftp = [ordered] @{
    FTP_SERVER   = Read-Plain 'FTP server'
    FTP_USERNAME = Read-Plain 'FTP username'
    FTP_PASSWORD = Read-Secret 'FTP password (hidden)'
}

$values.APP_KEY = 'base64:' + [Convert]::ToBase64String((New-RandomBytes 32))
$values.DEPLOY_TOKEN = -join ((New-RandomBytes 32) | ForEach-Object { $_.ToString('x2') })

# Single quotes make dotenv read the value literally ($, # and spaces included).
foreach ($key in @($values.Keys)) {
    if ($values[$key].Contains("'")) { throw "$key contains a single quote ('), which the env file can't hold. Pick a value without one." }
}

$lines = foreach ($line in Get-Content -LiteralPath $template) {
    if ($line -match '^([A-Z_]+)=\s*$' -and $values.Contains($Matches[1])) {
        "$($Matches[1])='$($values[$Matches[1]])'"
    }
    else { $line }
}

Write-Host "`nSending secrets to $repo ..."
Set-RepoSecret 'ENV_MISSION_CONTROL_PROD' ($lines -join "`n")
Set-RepoSecret 'DEPLOY_TOKEN' $values.DEPLOY_TOKEN
foreach ($key in $ftp.Keys) { Set-RepoSecret $key $ftp[$key] }

Write-Host "`nDone. Tell Claude the secrets are set; nothing secret was shown or saved." -ForegroundColor Cyan
