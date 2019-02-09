#!/usr/bin/env bash

# build image
echo "$DOCKER_PASSWORD" | docker login -u "$DOCKER_USERNAME" --password-stdin

# get qemu-arm-static binary
if [ "$TRAVIS_BRANCH" == "arm" ]; then
    mkdir tmp
    pushd tmp && \
    curl -L -o qemu-arm-static.tar.gz https://github.com/multiarch/qemu-user-static/releases/download/v2.6.0/qemu-arm-static.tar.gz && \
    tar xzf qemu-arm-static.tar.gz && \
    popd
fi

# build develop
if [ "$TRAVIS_BRANCH" == "develop" ]; then
    echo "Build develop $ARCH"
    docker build -t jc5x/firefly-iii:develop-$ARCH -f Dockerfile.$ARCH .
    docker push jc5x/firefly-iii:develop-$ARCH
fi

if [ "$TRAVIS_BRANCH" == "master" ]; then
    echo "Build master $ARCH"
    docker build -t jc5x/firefly-iii:latest-$ARCH -f Dockerfile.$ARCH .
    docker tag jc5x/firefly-iii:latest-$ARCH jc5x/firefly-iii:release-$VERSION-$ARCH
    docker push jc5x/firefly-iii:latest-$ARCH
    docker push jc5x/firefly-iii:release-$VERSION-$ARCH
fi