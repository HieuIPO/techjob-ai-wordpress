param([string]$Php = 'C:\xampp\php\php.exe')
$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path $PSScriptRoot -Parent
Set-Location $projectRoot
if (Test-Path 'runtime/local-processes.json') {
    $webId = (Get-Content runtime/local-processes.json -Raw | ConvertFrom-Json).php
    $webProcess = Get-CimInstance Win32_Process -Filter "ProcessId=$webId" -ErrorAction SilentlyContinue
    if ($webProcess -and $webProcess.Name -eq 'php.exe' -and $webProcess.CommandLine.Contains($projectRoot + '\scripts\router.php')) { Stop-Process -Id $webId }
}
if (Get-NetTCPConnection -State Listen -LocalPort 3308 -ErrorAction SilentlyContinue) { & $Php scripts/stop-local.php }
