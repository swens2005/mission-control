<#
.SYNOPSIS
    Sets the five GitHub secrets for the proposal assistant and its email.

.DESCRIPTION
    Run this yourself, in your own terminal, from the repo root:

        powershell -ExecutionPolicy Bypass -File scripts\set-ai-secrets.ps1

    It asks for the Anthropic API key, the inbox for the "AI cap reached"
    email and the noreply@codelaunch.nl mailbox, and sends them straight to
    GitHub. The deploy adds them to the production settings (story 42).
    Unlike set-production-secrets.ps1 it never touches APP_KEY, so nobody
    is logged out. Safe to run again to change a value; press Enter on a
    question that has a [default] to keep it.
#>

$ErrorActionPreference = 'Stop'
$repo = 'swens2005/mission-control'

function Read-Plain([string] $prompt, [string] $default = '') {
    $label = if ($default) { "$prompt [$default]" } else { $prompt }
    $value = (Read-Host -Prompt $label).Trim()
    if (-not $value) { $value = $default }
    if (-not $value) { throw "$prompt cannot be empty." }
    return $value
}

function Read-Secret([string] $prompt) {
    $secure = Read-Host -Prompt $prompt -AsSecureString
    $bstr = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($secure)
    try { $value = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($bstr) }
    finally { [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($bstr) }
    if ([string]::IsNullOrEmpty($value)) { throw "$prompt cannot be empty." }
    return $value
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

Write-Host "`nAnthropic (console.anthropic.com > API keys)"
$values = [ordered] @{
    ANTHROPIC_API_KEY = Read-Secret 'API key (hidden, starts with sk-ant-)'
}
if (-not $values.ANTHROPIC_API_KEY.StartsWith('sk-ant-')) { throw 'That does not look like an Anthropic API key (it should start with sk-ant-). Nothing was sent.' }

Write-Host "`nWho gets the one email when the monthly AI cap is reached? Use an inbox you read."
$values.AI_CAP_EMAIL = Read-Plain 'Email address'
if ($values.AI_CAP_EMAIL -notmatch '^[^@\s]+@[^@\s]+\.[^@\s]+$') { throw 'That is not an email address. Nothing was sent.' }

Write-Host "`nThe mailbox that sends it (cPanel > Email Accounts)"
$values.MAIL_HOST = Read-Plain 'Mail server' 'codelaunch.nl'
$values.MAIL_USERNAME = Read-Plain 'Mailbox' 'noreply@codelaunch.nl'
$values.MAIL_PASSWORD = Read-Secret 'Mailbox password (hidden)'

# The deploy wraps each value in single quotes so $, # and spaces are read
# literally; a quote or a line break would break that.
foreach ($key in @($values.Keys)) {
    if ($values[$key] -match "['`r`n]") { throw "$key contains a single quote (') or a line break, which the settings file can't hold. Nothing was sent." }
}

Write-Host "`nSending secrets to $repo ..."
foreach ($key in $values.Keys) { Set-RepoSecret $key $values[$key] }

Write-Host "`nDone. Tell Claude the AI secrets are set; nothing secret was shown or saved." -ForegroundColor Cyan
