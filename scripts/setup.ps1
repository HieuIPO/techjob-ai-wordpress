param([int]$Port = 8080)
$ErrorActionPreference = 'Stop'
Set-Location (Split-Path $PSScriptRoot -Parent)
function Invoke-Compose { & docker compose @args; if ($LASTEXITCODE -ne 0) { throw "Docker Compose failed: $args" } }
if (!(Test-Path '.env')) {
    $dbPassword = [Convert]::ToHexString([Security.Cryptography.RandomNumberGenerator]::GetBytes(24))
    $rootPassword = [Convert]::ToHexString([Security.Cryptography.RandomNumberGenerator]::GetBytes(24))
    (Get-Content '.env.example' -Raw).Replace('TECHJOB_PORT=8080', "TECHJOB_PORT=$Port").Replace('DB_PASSWORD=', "DB_PASSWORD=$dbPassword").Replace('DB_ROOT_PASSWORD=', "DB_ROOT_PASSWORD=$rootPassword") | Set-Content '.env' -Encoding utf8
}
Invoke-Compose up -d wordpress
for ($attempt = 0; $attempt -lt 40; $attempt++) {
    try { Invoke-WebRequest "http://localhost:$Port/wp-admin/install.php" -TimeoutSec 5 -UseBasicParsing | Out-Null; break } catch { Start-Sleep -Seconds 2 }
}
Invoke-Compose exec -u root wordpress sh -c 'mkdir -p /var/techjob-private; chown www-data:www-data /var/techjob-private; chmod 700 /var/techjob-private'
Invoke-Compose run --rm cli eval-file techjob-scripts/install.php --skip-wordpress
Invoke-Compose run --rm cli plugin activate techjob-ai-core
Invoke-Compose run --rm cli theme activate techjob-ai
Invoke-Compose run --rm cli option update timezone_string Asia/Ho_Chi_Minh
Invoke-Compose run --rm cli option update permalink_structure '/%postname%/'
Invoke-Compose run --rm cli rewrite flush --hard
Invoke-Compose run --rm cli eval-file techjob-scripts/seed.php
New-Item -ItemType Directory -Path runtime -Force | Out-Null
Invoke-Compose exec -T wordpress cat /var/techjob-private/demo-accounts.txt | Set-Content runtime/demo-accounts.txt -Encoding utf8
Write-Host "TechJob AI: http://localhost:$Port | Tai khoan: runtime/demo-accounts.txt"
