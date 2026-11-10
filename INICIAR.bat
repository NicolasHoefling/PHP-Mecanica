@echo off
setlocal DisableDelayedExpansion
title Calculadora de Mecanica - Tema II

set "PHP_EXE="
if exist "%~dp0runtime\php\php.exe" set "PHP_EXE=%~dp0runtime\php\php.exe"
if defined PHP_EXE goto executar

for /f "delims=" %%P in ('where php.exe 2^>nul') do if not defined PHP_EXE set "PHP_EXE=%%P"
if defined PHP_EXE goto executar

if exist "%SystemDrive%\xampp\php\php.exe" set "PHP_EXE=%SystemDrive%\xampp\php\php.exe"
if defined PHP_EXE goto executar

echo Nao foi encontrado PHP 8 ou superior.
echo Use o ZIP de entrega completo, que inclui o PHP portatil.
echo Consulte o arquivo LEIA-ME.txt.
pause
exit /b 1

:executar
"%PHP_EXE%" -n "%~dp0iniciar.php" %*
set "RESULTADO=%ERRORLEVEL%"
if not "%RESULTADO%"=="0" (
    echo.
    echo Nao foi possivel iniciar a calculadora.
    echo Se o Windows informar falta de VCRUNTIME140.dll, consulte LEIA-ME.txt.
    pause
)
endlocal & exit /b %RESULTADO%
