$ErrorActionPreference = 'Stop'
Set-Location (Split-Path $PSScriptRoot -Parent)
New-Item -ItemType Directory -Path artifacts -Force | Out-Null
Compress-Archive -Path 'wp-content/themes/techjob-ai' -DestinationPath 'artifacts/techjob-ai-theme.zip' -Force
Compress-Archive -Path 'wp-content/plugins/techjob-ai-core' -DestinationPath 'artifacts/techjob-ai-core.zip' -Force
Write-Host 'Installable theme and plugin ZIP files saved in artifacts/.'
