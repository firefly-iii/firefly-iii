#!/usr/bin/env bash
docker run --rm --privileged multiarch/qemu-user-static:register --reset

# get qemu-arm-static binary
mkdir tmp
pushd tmp && \
curl -L -o qemu-arm-static.tar.gz https://github.com/multiarch/qemu-user-static/releases/download/v2.6.0/qemu-arm-static.tar.gz && \
tar xzf qemu-arm-static.tar.gz && \
popd

# build image
docker build -t jc5x/ff-test-builds:develop -f Dockerfile-ARM .
echo "$DOCKER_PASSWORD" | docker login -u "$DOCKER_USERNAME" --password-stdin

if [ "$TRAVIS_BRANCH" == "develop" ]; then
    docker push jc5x/firefly-iii:develop-arm
fi

if [ "$TRAVIS_BRANCH" == "master" ]; then
    docker login -u="$DOCKER_USER" -p="$DOCKER_PASS"
    docker push jc5x/firefly-iii:latest-arm
    docker push jc5x/firefly-iii:release-$VERSION-arm
fi