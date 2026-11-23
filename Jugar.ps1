param([switch]$NoBrowser)

$ErrorActionPreference = 'Stop'
try {
    $webRoot = Join-Path $PSScriptRoot 'source\public'
    $phpCommand = Get-Command php -ErrorAction SilentlyContinue
    $phpCandidates = @(
        (Join-Path $env:USERPROFILE '.config\herd\bin\php85\php.exe'),
        'C:\xampp\php\php.exe',
        'D:\xampp\php\php.exe'
    )
    if ($phpCommand -and $phpCommand.Source.EndsWith('.exe')) {
        $phpCandidates = @($phpCommand.Source) + $phpCandidates
    }
    $phpBinary = $phpCandidates | Where-Object { Test-Path -LiteralPath $_ } | Select-Object -First 1
    if (!$phpBinary -and $phpCommand) { $phpBinary = $phpCommand.Source }
    if (!$phpBinary) { throw 'No se encontro PHP. Instala PHP 8.5 o configura php en PATH.' }

    $logDirectory = Join-Path $env:TEMP 'Buscaminas-local'
    New-Item -ItemType Directory -Path $logDirectory -Force | Out-Null
    $gameUrl = $null
    foreach ($port in 8000..8010) {
        $candidateUrl = "http://127.0.0.1:$port/"
        try {
            $response = Invoke-WebRequest -Uri $candidateUrl -UseBasicParsing -TimeoutSec 1
            if ($response.Headers['X-Minas-Game'] -eq '1') {
                $gameUrl = $candidateUrl
                break
            }
            continue
        } catch {
            if ($_.Exception.Response) { continue }
        }

        $server = Start-Process -FilePath $phpBinary -ArgumentList @('-S', "127.0.0.1:$port", '-t', ('"' + $webRoot + '"')) -WindowStyle Hidden -PassThru -WorkingDirectory $env:TEMP -RedirectStandardOutput (Join-Path $logDirectory "$port-out.log") -RedirectStandardError (Join-Path $logDirectory "$port-error.log")
        for ($attempt = 0; $attempt -lt 20; $attempt++) {
            if ($server.HasExited) { break }
            try {
                $response = Invoke-WebRequest -Uri $candidateUrl -UseBasicParsing -TimeoutSec 1
                if ($response.Headers['X-Minas-Game'] -eq '1') { $gameUrl = $candidateUrl; break }
            } catch { }
            Start-Sleep -Milliseconds 250
        }
        if ($gameUrl) { break }
        if (!$server.HasExited) { Stop-Process -Id $server.Id }
    }
    if (!$gameUrl) { throw "No fue posible iniciar el juego. Revisa los archivos en $logDirectory." }
    Write-Output "Buscaminas listo: $gameUrl"
    if (!$NoBrowser) { Start-Process $gameUrl }
} catch {
    Write-Host $_.Exception.Message -ForegroundColor Red
    exit 1
}
