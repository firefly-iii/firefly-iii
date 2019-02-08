#!/usr/bin/env bash

# build image
echo "$DOCKER_PASSWORD" | docker login -u "$DOCKER_USERNAME" --password-stdin

# get qemu-arm-static binary
if [ "$TRAVIS_BRANCH" != "amd64" ]; then
    mkdir tmp
    pushd tmp && \
    curl -L -o qemu-arm-static.tar.gz https://github.com/multiarch/qemu-user-static/releases/download/v2.6.0/qemu-arm-static.tar.gz && \
    tar xzf qemu-arm-static.tar.gz && \
    popd
fi

# build develop
if [ "$TRAVIS_BRANCH" == "develop" ]; then
    echo "Build develop $ARCH"
    # > original command. docker build -t whoami --build-arg "arch=$ARCH" .
    docker build -t jc5x/firefly-iii:develop-$ARCH -f Dockerfile.$ARCH --build-arg "arch=$ARCH" .
    #docker push jc5x/firefly-iii:develop
fi

#if [ "$TRAVIS_BRANCH" == "master" ]; then
#    echo "Build master amd64"
#    docker build -t jc5x/firefly-iii:latest-amd -f Dockerfile .
#    docker tag jc5x/firefly-iii:latest-amd jc5x/firefly-iii:release-$VERSION-amd
#    docker push jc5x/firefly-iii:latest-amd
#    docker push jc5x/firefly-iii:release-$VERSION-amd
#fi