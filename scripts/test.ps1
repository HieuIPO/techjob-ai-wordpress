param([switch]$Local, [string]$Php = 'C:\xampp\php\php.exe')
$ErrorActionPreference = 'Stop'
Set-Location (Split-Path $PSScriptRoot -Parent)
if ($Local) {
    Get-ChildItem wp-content,scripts,tests -Filter '*.php' -Recurse | ForEach-Object {
        & $Php -l $_.FullName | Out-Null
        if ($LASTEXITCODE -ne 0) { throw "Syntax error: $($_.FullName)" }
    }
    & $Php -d extension=zip tests/policy.php
    if ($LASTEXITCODE -ne 0) { throw 'Policy tests failed' }
    & $Php -d extension=zip runtime/wp-cli.phar --path=runtime/wordpress eval-file tests/integration.php
    if ($LASTEXITCODE -ne 0) { throw 'Integration tests failed' }
    & $Php -d extension=zip tests/http.php
    if ($LASTEXITCODE -ne 0) { throw 'HTTP tests failed' }
} else {
    docker compose exec wordpress php /var/www/html/techjob-tests/policy.php
    if ($LASTEXITCODE -ne 0) { throw 'Policy tests failed' }
    docker compose run --rm cli eval-file techjob-tests/integration.php
    if ($LASTEXITCODE -ne 0) { throw 'Integration tests failed' }
}
Write-Host 'All checks passed.'
