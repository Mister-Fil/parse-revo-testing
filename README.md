#Hello

###Install Docker and start APP
```shell
apt update
apt install -y apt-transport-https ca-certificates curl gnupg-agent software-properties-common zip unzip

curl -fsSL https://download.docker.com/linux/debian/gpg | apt-key add -
add-apt-repository "deb [arch=amd64] https://download.docker.com/linux/debian $(lsb_release -cs) stable"
apt update

#/etc/init.d/docker stop
#apt purge -y docker-ce docker-ce-cli containerd.io
apt install -y docker-ce docker-ce-cli containerd.io

rm -rf /usr/bin/docker-compose
rm -rf /usr/local/bin/docker-compose
curl -L "https://github.com/docker/compose/releases/download/1.29.2/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
chmod +x /usr/local/bin/docker-compose
ln -s /usr/local/bin/docker-compose /usr/bin/docker-compose

docker-compose up --build --force-recreate
```