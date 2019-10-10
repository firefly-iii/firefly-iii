#!/usr/bin/env bash

docker run --rm --privileged multiarch/qemu-user-static:register --reset

# get qemu-arm-static binary
mkdir tmp
pushd tmp && \
curl -L -o qemu-arm-static.tar.gz https://github.com/multiarch/qemu-user-static/releases/download/v2.6.0/qemu-arm-static.tar.gz && \
tar xzf qemu-arm-static.tar.gz && \
popd

# build image
echo "$DOCKER_PASSWORD" | docker login -u "$DOCKER_USERNAME" --password-stdin

if [ "$TRAVIS_BRANCH" == "develop" ]; then
    echo "Build develop arm"
    docker build --tag jc5x/firefly-iii:develop-arm --file Dockerfile.arm .
    docker tag jc5x/firefly-iii:develop-arm jc5x/firefly-iii:develop-$VERSION-arm
    docker push jc5x/firefly-iii:develop-arm
    docker push jc5x/firefly-iii:develop-$VERSION-arm
fi

if [ "$TRAVIS_BRANCH" == "master" ]; then
    echo "Build master arm"
    docker build --tag jc5x/firefly-iii:latest-arm --file Dockerfile.arm .
    docker tag jc5x/firefly-iii:latest-arm jc5x/firefly-iii:release-$VERSION-arm
    docker push jc5x/firefly-iii:latest-arm
    docker push jc5x/firefly-iii:release-$VERSION-arm
fi