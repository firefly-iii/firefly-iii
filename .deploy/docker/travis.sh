#!/usr/bin/env bash

echo "I am building channel ${CHANNEL} for version ${VERSION} on architecture ${ARCH}."

echo '{"experimental":true}' | sudo tee /etc/docker/daemon.json
mkdir $HOME/.docker
touch $HOME/.docker/config.json
echo '{"experimental":"enabled"}' | sudo tee $HOME/.docker/config.json
sudo service docker restart

# build everything
.deploy/docker/build-$ARCH.sh
