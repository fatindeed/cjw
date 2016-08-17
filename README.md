# cjw财务软件

调用新天地数据接口，将门店营业数据上传至服务器

## Installation
1. 安装**Docker Toolbox**
2. 将代码克隆到用户主目录下
3. 修改**Docker Toolbox**目录中的`start.sh`，加入以下代码：
```sh
runapp () {
  pushd "$1" > /dev/null
  proj=$(basename "$PWD" | sed 's/-//g')
  result=0
  while [ $result -eq 0 ]
  do
    docker-compose up -d
    result=$(docker ps | grep "$proj" | wc -l)
    sleep 3
  done
  echo "$proj" started
  popd > /dev/null
}
export -f runapp
```
4. 复制`Docker Quickstart Terminal`的快捷方式到启动目录，并在目标最后加上`runcjw`，如下：
```sh
"C:\Program Files\Git\bin\bash.exe" --login -i "C:\Program Files\Docker Toolbox\start.sh" runapp cjw
```
