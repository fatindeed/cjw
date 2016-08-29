# cjw财务软件

调用新天地数据接口，将门店营业数据上传至服务器

## Installation
1. 下载项目源代码，并将其放到用户主目录下
```sh
cd ~
git clone https://github.com/fatindeed/cjw.git
```
2. 创建配置文件`var/application.ini`，配置参数参考`app/conf/application.ini.example`
3. [下载](https://www.docker.com/products/docker-toolbox)并安装**Docker Toolbox**
4. 修改**Docker Toolbox**目录中的`start.sh`，加入以下代码：
```sh
STEP="Setting env"
ENV_CNT=0
while [ $ENV_CNT -eq 0 ]
do
  ENV_CNT=$("${DOCKER_MACHINE}" env "${VM}" | wc -l)
  sleep 5
done
eval "$(${DOCKER_MACHINE} env --shell=bash ${VM})"

runapp () {
  pushd "$1" > /dev/null
  docker-compose up -d
  popd > /dev/null
}
```
5. 复制`Docker Quickstart Terminal`的快捷方式到**启动**目录，并在*目标*最后加上`runapp cjw`，如下：
```sh
"C:\Program Files\Git\bin\bash.exe" --login -i "C:\Program Files\Docker Toolbox\start.sh" runapp cjw
```
6. 重启后每次开机后即会自动启动本应用