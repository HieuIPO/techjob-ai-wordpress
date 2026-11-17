param([switch]$Local, [string]$Php = 'C:\xampp\php\php.exe', [switch]$RecruitmentOnly)
$ErrorActionPreference = 'Stop'
Set-Location (Split-Path $PSScriptRoot -Parent)
if ($RecruitmentOnly) {
    if ($Local) {
        Get-ChildItem wp-content,scripts,tests -Filter '*.php' -Recurse | ForEach-Object {
            & $Php -l $_.FullName | Out-Null
            if ($LASTEXITCODE -ne 0) { throw "Syntax error: $($_.FullName)" }
        }
        & $Php -d extension=zip tests/policy.php
        if ($LASTEXITCODE -ne 0) { throw 'Policy tests failed' }
        foreach ($suite in @('recruitment-e2e', 'recruitment-features')) {
            & $Php -d extension=zip "tests/$suite.php"
            if ($LASTEXITCODE -ne 0) { throw "$suite tests failed" }
        }
    } else {
        $port = '8080'
        if (Test-Path '.env') {
            $match = [regex]::Match((Get-Content '.env' -Raw), '(?m)^TECHJOB_PORT=(\d+)\s*$')
            if ($match.Success) { $port = $match.Groups[1].Value }
        }
        docker compose exec -T wordpress php /var/www/html/techjob-tests/policy.php
        if ($LASTEXITCODE -ne 0) { throw 'Policy tests failed' }
        foreach ($suite in @('recruitment-e2e', 'recruitment-features')) {
            docker compose exec -T -e TJ_TEST_WP_LOAD=/var/www/html/wp-load.php -e "TJ_TEST_CONNECT_TO=localhost:${port}:127.0.0.1:80" wordpress php "/var/www/html/techjob-tests/$suite.php"
            if ($LASTEXITCODE -ne 0) { throw "$suite tests failed" }
        }
    }
    Write-Host 'All recruitment checks passed.'
    return
}
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
