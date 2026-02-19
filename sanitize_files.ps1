# Sanitize temp_update_password.php
(Get-Content -Path "temp_update_password.php") -replace 'sales@efficentrix\.co\.ke', 'REDACTED_EMAIL@example.com' | 
    Set-Content -Path "temp_update_password.php"

# Sanitize temp_verify_password.php
(Get-Content -Path "temp_verify_password.php") -replace 'P@\$\$w0rd', 'REDACTED_PASSWORD' | 
    Set-Content -Path "temp_verify_password.php"

# Sanitize temp_dump_user.php
(Get-Content -Path "temp_dump_user.php") -replace 'sales@efficentrix\.co\.ke', 'REDACTED_EMAIL@example.com' | 
    Set-Content -Path "temp_dump_user.php"
(Get-Content -Path "temp_dump_user.php") -replace 'P@\$\$w0rd', 'REDACTED_PASSWORD' | 
    Set-Content -Path "temp_dump_user.php"

# Sanitize temp_list_user_devices.php
(Get-Content -Path "temp_list_user_devices.php") -replace 'sales@efficentrix\.co\.ke', 'REDACTED_EMAIL@example.com' | 
    Set-Content -Path "temp_list_user_devices.php"

Write-Host "Files have been sanitized. Please review the changes before committing."
