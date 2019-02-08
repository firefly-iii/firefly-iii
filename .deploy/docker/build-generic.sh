#!/usr/bin/env bash

# build image
echo "$DOCKER_PASSWORD" | docker login -u "$DOCKER_USERNAME" --password-stdin

# build develop
if [ "$TRAVIS_BRANCH" == "develop" ]; then
    echo "Build develop $ARCH"
    #docker build -t whoami --build-arg "arch=$ARCH" .
    #docker build -t jc5x/firefly-iii:develop -f Dockerfile --build-arg "arch=$env:ARCH" .
    #docker push jc5x/firefly-iii:develop
fi

#if [ "$TRAVIS_BRANCH" == "master" ]; then
#    echo "Build master amd64"
#    docker build -t jc5x/firefly-iii:latest-amd -f Dockerfile .
#    docker tag jc5x/firefly-iii:latest-amd jc5x/firefly-iii:release-$VERSION-amd
#    docker push jc5x/firefly-iii:latest-amd
#    docker push jc5x/firefly-iii:release-$VERSION-amd
#fi