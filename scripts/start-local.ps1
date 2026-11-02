param([string]$Php = 'C:\xampp\php\php.exe')
$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path $PSScriptRoot -Parent
Set-Location $projectRoot
if (!(Test-Path -LiteralPath "$projectRoot\runtime\wordpress\wp-config.php")) { throw 'Local fallback is not installed. Use scripts/setup.ps1 for Docker.' }
if (!(Test-Path -LiteralPath $Php)) { throw 'PHP not found. Pass -Php with the installed PHP path.' }
if (Test-Path '.env') {
    foreach ($line in Get-Content '.env') {
        if ($line -match '^(GEMINI_API_KEY|GEMINI_MODEL)=(.*)$') { [Environment]::SetEnvironmentVariable($Matches[1], $Matches[2].Trim(), 'Process') }
    }
}
$database = Get-NetTCPConnection -State Listen -LocalPort 3308 -ErrorAction SilentlyContinue
if (!$database) {
    $mariadb = Join-Path $projectRoot 'runtime\mariadb-11.4.13-winx64\bin\mariadbd.exe'
    if (!(Test-Path -LiteralPath $mariadb)) { throw 'Portable MariaDB is missing from runtime.' }
    Start-Process -FilePath $mariadb -ArgumentList @('--defaults-file="' + $projectRoot + '\runtime\mysql\my.ini"', '--bind-address=127.0.0.1', '--port=3308', '--console') -WorkingDirectory $projectRoot -WindowStyle Hidden -RedirectStandardOutput "$projectRoot\runtime\database-out.log" -RedirectStandardError "$projectRoot\runtime\database-error.log" | Out-Null
}
for ($attempt = 0; $attempt -lt 30; $attempt++) {
    if (Get-NetTCPConnection -State Listen -LocalPort 3308 -ErrorAction SilentlyContinue) { break }
    Start-Sleep -Milliseconds 300
}
if (!(Get-NetTCPConnection -State Listen -LocalPort 3308 -ErrorAction SilentlyContinue)) { throw 'MariaDB did not start. See runtime/database-error.log.' }
$server = Get-NetTCPConnection -State Listen -LocalPort 8080 -ErrorAction SilentlyContinue
if (!$server) {
    $phpArgs = @('-d', 'extension=zip', '-d', 'upload_max_filesize=6M', '-d', 'post_max_size=8M', '-d', 'display_errors=0', '-S', '127.0.0.1:8080', '-t', ('"' + $projectRoot + '\runtime\wordpress"'), ('"' + $projectRoot + '\scripts\router.php"'))
    $process = Start-Process -FilePath $Php -ArgumentList $phpArgs -WorkingDirectory $projectRoot -WindowStyle Hidden -RedirectStandardOutput "$projectRoot\runtime\web-out.log" -RedirectStandardError "$projectRoot\runtime\web-error.log" -PassThru
    @{php = $process.Id} | ConvertTo-Json | Set-Content 'runtime/local-processes.json' -Encoding utf8
}
Write-Host 'TechJob AI: http://localhost:8080'
Write-Host 'Accounts: runtime/private/demo-accounts.txt'
