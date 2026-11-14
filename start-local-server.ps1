# Local server limits: maximum 20 MB per photo plus multipart overhead.
$php = 'D:\xampp\php\php.exe'
if (-not (Test-Path -LiteralPath $php)) { $php = 'php' }
Set-Location -LiteralPath (Join-Path $PSScriptRoot 'public')
& $php -d upload_max_filesize=21M -d post_max_size=22M -S 127.0.0.1:8000 (Join-Path $PSScriptRoot 'vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php')
