<?php

echo '��ʼ��װ����...'.PHP_EOL;

do {
	echo '��������Ҫ��װ��Ŀ¼��';
	$installDir = trim(fgets(STDIN));
	if(file_exists($installDir)) {
		echo '��װĿ¼�Ѵ���'.PHP_EOL;
		continue;
	}
	if(preg_match('/[\x7f-\xff]/', $installDir)) { 
		echo '��װĿ¼���ܰ�������'.PHP_EOL;
		continue;
	}
	mkdir($installDir, 0777, true) || die('��װĿ¼����ʧ�ܣ���ȷ�������㹻��Ȩ�ޡ�'.PHP_EOL);
	$installDir = realpath($installDir).DIRECTORY_SEPARATOR;
	mkdir($installDir.'log', 0777, true) || die('��װĿ¼����ʧ�ܣ���ȷ�������㹻��Ȩ�ޡ�'.PHP_EOL);
	echo '��ʼ�����ļ������Ժ�...'.PHP_EOL;
	break;
}
while(true);

$phpDir = $installDir.'php'.DIRECTORY_SEPARATOR;
system('xcopy /E "'.dirname(PHP_BINARY).'" "'.$phpDir.'"', $retval);
if($retval !== 0) die('PHPĿ¼����ʧ��'.PHP_EOL);
$iniData = file_get_contents($phpDir.'php.ini');
$iniData = str_replace('extension_dir = "ext"', 'extension_dir = "'.$phpDir.'ext"', $iniData);
file_put_contents($phpDir.'php.ini', $iniData);
echo 'PHP��װ�ɹ�...'.PHP_EOL;

$appDir = $installDir.'app'.DIRECTORY_SEPARATOR;
system('xcopy /E app "'.$appDir.'"', $retval);
if($retval !== 0) die('Ӧ�ó���Ŀ¼����ʧ��'.PHP_EOL);
unlink($appDir.'install.php');

$heredoc = <<<EOD
@echo off
cd /d %~dp0
"{$phpDir}php.exe" app\index.php
pause
EOD;
file_put_contents($installDir.'run.bat', $heredoc);

$heredoc = <<<EOD
@echo off
cd /d %~dp0
"{$phpDir}php.exe" app\index.php >>log\%date:~0,4%%date:~5,2%%date:~8,2%.txt 2>>&1
EOD;
file_put_contents($installDir.'task.bat', $heredoc);

$heredoc = <<<EOD
@echo off
cd /d %~dp0
"{$phpDir}php.exe" app\index.php stat
pause
EOD;
file_put_contents($installDir.'stat.bat', $heredoc);

system('xcopy /E sqliteadmin "'.$installDir.'sqliteadmin'.DIRECTORY_SEPARATOR.'"');

system('schtasks /create /sc minute /mo 30 /tn "ILSUPLOADTRANS" /tr "'.$installDir.'task.bat" /ru system', $retval);
if($retval !== 0) die('�ƻ����񴴽�ʧ��'.PHP_EOL);

echo 'Ӧ�ó���װ�ɹ�'.PHP_EOL;

?>