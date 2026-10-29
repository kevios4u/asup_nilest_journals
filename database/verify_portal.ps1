param(
    [Parameter(Mandatory = $true)][string]$Role,
    [Parameter(Mandatory = $true)][string[]]$Pages,
    [int]$Width = 1440
)

$edge = "C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe"
$tmp = "C:\Users\kevio\AppData\Local\Temp\kilo"
$profile = Join-Path $tmp "portal_$Role"

# Establish the session
$login = Join-Path $tmp "login_$Role.html"
Start-Process -FilePath $edge -ArgumentList '--headless=new', '--disable-gpu', '--no-sandbox', '--virtual-time-budget=6000', "--user-data-dir=$profile", '--dump-dom', "http://localhost/asup_nilest_journals/_devlogin.php?as=$Role" -RedirectStandardOutput $login -RedirectStandardError (Join-Path $tmp "login_err.txt") -Wait -NoNewWindow

foreach ($page in $Pages) {
    $outFile = Join-Path $tmp "portal_out.html"
    $url = "http://localhost/asup_nilest_journals/_icheck.html?w=$Width&page=$page"
    Start-Process -FilePath $edge -ArgumentList '--headless=new', '--disable-gpu', '--no-sandbox', '--virtual-time-budget=12000', "--user-data-dir=$profile", '--dump-dom', $url -RedirectStandardOutput $outFile -RedirectStandardError (Join-Path $tmp "portal_err.txt") -Wait -NoNewWindow

    $dom = Get-Content -Raw $outFile
    $match = [regex]::Match($dom, 'id="out">([^<]*)<')
    Write-Output ("{0,-26} {1}" -f $page, $(if ($match.Success) { $match.Groups[1].Value } else { 'NO RESULT' }))
}
