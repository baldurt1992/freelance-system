# Ejecutar en PowerShell COMO ADMINISTRADOR (Windows)
# Agrega personal.localhost para el navegador en Windows + WSL

$hostsPath = "$env:SystemRoot\System32\drivers\etc\hosts"
$entry = "127.0.0.1 personal.localhost"
$marker = "# freelance-system"

$content = Get-Content $hostsPath -Raw
if ($content -match 'personal\.localhost') {
    Write-Host "OK: personal.localhost ya existe en hosts de Windows"
    exit 0
}

Add-Content -Path $hostsPath -Value "`n$marker`n$entry"
Write-Host "Agregado a $hostsPath"
Write-Host ""
Write-Host "Abre: http://personal.localhost:3000/login"
