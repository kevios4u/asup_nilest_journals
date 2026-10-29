$base = "http://localhost/asup_nilest_journals"
Add-Type -AssemblyName System.Net.Http

$session = New-Object Microsoft.PowerShell.Commands.WebRequestSession
$session.UserAgent = "NIJOSTAM-Verification/1.0"
$handler = New-Object System.Net.Http.HttpClientHandler
$handler.CookieContainer = $session.Cookies
$handler.AllowAutoRedirect = $true
$client = New-Object System.Net.Http.HttpClient($handler)

function Get-CsrfToken($html) {
    if ($html -match 'name="csrf_token"\s+value="([^"]+)"') { return $Matches[1] }
    if ($html -match 'value="([^"]+)"\s+name="csrf_token"') { return $Matches[1] }
    return $null
}

function Get-Html($url) {
    return $client.GetAsync($url).Result.Content.ReadAsStringAsync().Result
}

function Encode-Fields($fields) {
    $pairs = @()
    foreach ($key in $fields.Keys) {
        $pairs += [System.Uri]::EscapeDataString([string]$key) + "=" + [System.Uri]::EscapeDataString([string]$fields[$key])
    }
    return ($pairs -join "&")
}

function Post-Form($url, $fields) {
    $body = New-Object System.Net.Http.StringContent((Encode-Fields $fields), [System.Text.Encoding]::UTF8)
    $body.Headers.ContentType = New-Object System.Net.Http.Headers.MediaTypeHeaderValue("application/x-www-form-urlencoded")
    $response = $client.PostAsync($url, $body).Result
    return @{ Status = [int]$response.StatusCode; Html = $response.Content.ReadAsStringAsync().Result; Url = $response.RequestMessage.RequestUri.AbsoluteUri }
}

function Post-Multipart($url, $fields, $fileField, $filePath, $fileName) {
    $boundary = "NIJOSTAMboundary" + [guid]::NewGuid().ToString("N")
    $builder = New-Object System.Text.StringBuilder
    foreach ($key in $fields.Keys) {
        [void]$builder.AppendLine("--$boundary")
        [void]$builder.AppendLine("Content-Disposition: form-data; name=`"$key`"")
        [void]$builder.AppendLine("")
        [void]$builder.AppendLine([string]$fields[$key])
    }
    [void]$builder.AppendLine("--$boundary")
    [void]$builder.AppendLine("Content-Disposition: form-data; name=`"$fileField`"; filename=`"$fileName`"")
    [void]$builder.AppendLine("Content-Type: application/pdf")
    [void]$builder.AppendLine("")
    [void]$builder.AppendLine((Get-Content -Raw -LiteralPath $filePath))
    [void]$builder.AppendLine("--$boundary--")

    $bytes = [System.Text.Encoding]::UTF8.GetBytes($builder.ToString())
    $body = New-Object System.Net.Http.ByteArrayContent -ArgumentList (, $bytes)
    [void]$body.Headers.TryAddWithoutValidation("Content-Type", "multipart/form-data; boundary=$boundary")
    $response = $client.PostAsync($url, $body).Result
    return @{ Status = [int]$response.StatusCode; Html = $response.Content.ReadAsStringAsync().Result; Url = $response.RequestMessage.RequestUri.AbsoluteUri }
}

$email = "webtest.$([guid]::NewGuid().ToString('N').Substring(0,8))@example.test"
$password = "Testing12345pass"
$output = @()

$token = Get-CsrfToken (Get-Html "$base/auth/register.php")
$result = Post-Form "$base/auth/register.php" @{
    csrf_token = $token; name = "Web Form Tester"; email = $email; role = "author"
    affiliation = "Nigerian Institute of Leather and Science Technology"; country = "Nigeria"
    password = $password; password_confirmation = $password
}
$output += "1. register          -> $($result.Url)"

$html = Get-Html "$base/author/dashboard.php"
$output += "2. dashboard         -> signed in: $($html -match 'Web Form Tester')"

$tempFile = Join-Path $env:TEMP "nijostam-sample.pdf"
Set-Content -LiteralPath $tempFile -Value "%PDF-1.4`n% verification sample`n" -Encoding ASCII
$token = Get-CsrfToken (Get-Html "$base/author/submit.php")
$result = Post-Multipart "$base/author/submit.php" @{
    csrf_token = $token
    title = "A web form submission test manuscript for NIJOSTAM"
    abstract = "This manuscript was submitted through the public web form to verify that registration, submission, and file upload work end to end without server errors."
    keywords = "testing, workflow, submission"
    subject = "Science"
    article_type = "Original research"
    language = "English"
    co_author_name = ""
    co_author_email = ""
    co_author_affiliation = ""
    cover_letter = "Submitted by the automated verification run."
    agreement = "1"
} "manuscript_file" $tempFile "verification-sample.pdf"
$output += "3. submit            -> $($result.Url)"
$alerts = [regex]::Matches($result.Html, 'alert alert--danger">.*?</div>')
foreach ($alert in $alerts) { $output += "   error: $(($alert.Value -replace '<[^>]+>','').Trim())" }

$html = Get-Html "$base/author/manuscripts.php"
$reference = if ($html -match 'NIJOSTAM-\d{4}-\d{4}') { $Matches[0] } else { 'NONE' }
$output += "4. manuscripts list  -> reference: $reference (valid: $([bool]($reference -match '^NIJOSTAM-\d{4}-\d{4}$')))"

if ($reference -ne 'NONE') {
    $manuscriptId = [regex]::Match($html, 'manuscript\.php\?id=(\d+)').Groups[1].Value
    $detail = Get-Html "$base/author/manuscript.php?id=$manuscriptId"
    $output += "5. manuscript page   -> file listed: $($detail -match 'verification-sample\.pdf')"
    $fileId = [regex]::Match($detail, 'download\.php\?id=(\d+)').Groups[1].Value
    $download = $client.GetAsync("$base/includes/download.php?id=$fileId").Result
    $payload = $download.Content.ReadAsByteArrayAsync().Result
    $header = [System.Text.Encoding]::ASCII.GetString($payload[0..3])
    $output += "   file download     -> status $([int]$download.StatusCode), $($payload.Length) bytes, header: $header"
}

Get-Html "$base/auth/logout.php" | Out-Null
$token = Get-CsrfToken (Get-Html "$base/auth/login.php")
$result = Post-Form "$base/auth/login.php" @{ csrf_token = $token; email = $email; password = "WrongPassword123" }
$output += "6. wrong password    -> rejected: $($result.Html -match 'incorrect')"

$token = Get-CsrfToken (Get-Html "$base/auth/login.php")
$result = Post-Form "$base/auth/login.php" @{ csrf_token = $token; email = $email; password = $password }
$output += "7. correct password  -> $($result.Url)"

$client.Dispose()
Remove-Item -LiteralPath $tempFile -Force -ErrorAction SilentlyContinue
$output
