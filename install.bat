@echo off
cd /d %~dp0
chcp 936 >nul 2>&1
at >nul 2>&1 || goto runas_admin
if %PROCESSOR_ARCHITECTURE%==x86 (set platform=x86) else (set platform=x64)
%platform%\php.exe app\install.php || goto not_support
goto end

:runas_admin
echo ��װʧ�ܣ��롰�Թ���Ա������С��˳���
goto end

:not_support
echo ��װ����Ҫ��Win7���ϲ���ϵͳ���Ѱ�װ Visual C++ Redistributable for Visual Studio 2012 �ſ����С�
echo �������Win7���²���ϵͳ����WinXP����������ĵ��ֶ���װPHP�������������á�
echo ����㻹δ��װVC11��������� http://www.microsoft.com/en-us/download/details.aspx?id=30679 �������ء�
goto end

:end
pause