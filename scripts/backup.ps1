param([switch]$Local)
$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path $PSScriptRoot -Parent
Set-Location $projectRoot
$destination = Join-Path $projectRoot ('backups\' + (Get-Date -Format 'yyyyMMdd-HHmmss'))
New-Item -ItemType Directory -Path $destination -Force | Out-Null
if ($Local) {
    $settings = Get-Content runtime/db-local.json -Raw | ConvertFrom-Json
    $clientConfig = Join-Path $projectRoot 'runtime\backup-client.cnf'
    try {
        "[client]`nuser=techjob`npassword=$($settings.password)`nhost=127.0.0.1`nport=3308`n" | Set-Content -LiteralPath $clientConfig -Encoding utf8
        & './runtime/mariadb-11.4.13-winx64/bin/mariadb-dump.exe' --defaults-extra-file="$clientConfig" --single-transaction --hex-blob --result-file="$destination\database.sql" techjob
        if ($LASTEXITCODE -ne 0) { throw 'Database backup failed' }
    } finally { if (Test-Path -LiteralPath $clientConfig) { Remove-Item -LiteralPath $clientConfig } }
    Copy-Item -LiteralPath 'runtime/private' -Destination "$destination\private" -Recurse
    Copy-Item -LiteralPath 'runtime/wordpress/wp-config.php' -Destination "$destination\wp-config.php"
} else {
    docker compose exec -T db sh -c 'mariadb-dump --single-transaction --hex-blob -u"$MARIADB_USER" -p"$MARIADB_PASSWORD" "$MARIADB_DATABASE"' | Set-Content "$destination\database.sql" -Encoding utf8
    if ($LASTEXITCODE -ne 0) { throw 'Database backup failed' }
    docker compose exec -T wordpress tar -czf /tmp/techjob-private-backup.tgz -C /var techjob-private
    if ($LASTEXITCODE -ne 0) { throw 'Private CV backup failed' }
    docker compose cp wordpress:/tmp/techjob-private-backup.tgz "$destination\private.tgz"
    if ($LASTEXITCODE -ne 0) { throw 'Copying CV backup failed' }
}
Write-Host "Backup saved: $destination"
