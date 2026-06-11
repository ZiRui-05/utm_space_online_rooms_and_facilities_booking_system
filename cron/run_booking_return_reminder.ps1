$ErrorActionPreference = 'Stop'

$environmentNames = @(
    'APP_TIMEZONE',
    'APP_URL',
    'APP_SECRET',
    'MAIL_HOST',
    'MAIL_PORT',
    'MAIL_USERNAME',
    'MAIL_PASSWORD',
    'MAIL_ENCRYPTION',
    'MAIL_FROM_EMAIL',
    'MAIL_FROM_NAME',
    'MAIL_REPLY_TO_EMAIL',
    'MAIL_REPLY_TO_NAME',
    'MAIL_TIMEOUT'
)

foreach ($name in $environmentNames) {
    $value = [Environment]::GetEnvironmentVariable($name, 'User')
    if ([string]::IsNullOrWhiteSpace($value)) {
        $value = [Environment]::GetEnvironmentVariable($name, 'Machine')
    }
    if (![string]::IsNullOrWhiteSpace($value)) {
        Set-Item -Path "Env:$name" -Value $value
    }
}

$phpPath = 'C:\xampp\php\php.exe'
$scriptPath = Join-Path $PSScriptRoot 'booking_return_reminder.php'

if (!(Test-Path -LiteralPath $phpPath)) {
    throw "PHP executable not found at $phpPath"
}

& $phpPath $scriptPath
exit $LASTEXITCODE
