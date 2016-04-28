@echo off
cd /d %~dp0
chcp 936 >nul 2>&1
at >nul 2>&1 || goto runas_admin
if %PROCESSOR_ARCHITECTURE%==x86 (set platform=x86) else (set platform=x64)
%platform%\php.exe app\install.php || goto not_support
goto end

:runas_admin
echo 安装失败，请“以管理员身份运行”此程序
goto end

:not_support
echo 安装程序要求Win7以上操作系统并已安装 Visual C++ Redistributable for Visual Studio 2012 才可运行。
echo 如果您是Win7以下操作系统（如WinXP），请根据文档手动安装PHP环境并进行配置。
echo 如果你还未安装VC11环境，请打开 http://www.microsoft.com/en-us/download/details.aspx?id=30679 进行下载。
goto end

:end
pause