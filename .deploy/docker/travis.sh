#!/usr/bin/env bash

echo $TRAVIS_DIST
echo '{"experimental":true}' | sudo tee /etc/docker/daemon.json
mkdir $HOME/.docker
touch $HOME/.docker/config.json
echo '{"experimental":"enabled"}' | sudo tee $HOME/.docker/config.json
sudo service docker restart
docker version -f '{{.Server.Experimental}}'
docker version

# build everything
.deploy/docker/build-$ARCH.sh
.deploy/docker/manifest.sh