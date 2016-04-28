<?php

echo '开始安装程序...'.PHP_EOL;

do {
	echo '请输入您要安装的目录：';
	$installDir = trim(fgets(STDIN));
	if(file_exists($installDir)) {
		echo '安装目录已存在'.PHP_EOL;
		continue;
	}
	if(preg_match('/[\x7f-\xff]/', $installDir)) { 
		echo '安装目录不能包含中文'.PHP_EOL;
		continue;
	}
	mkdir($installDir, 0777, true) || die('安装目录创建失败，请确保您有足够的权限。'.PHP_EOL);
	$installDir = realpath($installDir).DIRECTORY_SEPARATOR;
	mkdir($installDir.'log', 0777, true) || die('安装目录创建失败，请确保您有足够的权限。'.PHP_EOL);
	echo '开始复制文件，请稍候...'.PHP_EOL;
	break;
}
while(true);

$phpDir = $installDir.'php'.DIRECTORY_SEPARATOR;
system('xcopy /E "'.dirname(PHP_BINARY).'" "'.$phpDir.'"', $retval);
if($retval !== 0) die('PHP目录复制失败'.PHP_EOL);
$iniData = file_get_contents($phpDir.'php.ini');
$iniData = str_replace('extension_dir = "ext"', 'extension_dir = "'.$phpDir.'ext"', $iniData);
file_put_contents($phpDir.'php.ini', $iniData);
echo 'PHP安装成功...'.PHP_EOL;

$appDir = $installDir.'app'.DIRECTORY_SEPARATOR;
system('xcopy /E app "'.$appDir.'"', $retval);
if($retval !== 0) die('应用程序目录复制失败'.PHP_EOL);
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
if($retval !== 0) die('计划任务创建失败'.PHP_EOL);

echo '应用程序安装成功'.PHP_EOL;

?>