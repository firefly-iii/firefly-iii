#!/usr/bin/env bash

# build image
docker build -t jc5x/ff-test-builds:develop -f Dockerfile .
echo "$DOCKER_PASSWORD" | docker login -u "$DOCKER_USERNAME" --password-stdin

if [ "$TRAVIS_BRANCH" == "develop" ]; then
    docker push jc5x/firefly-iii:develop
fi

if [ "$TRAVIS_BRANCH" == "master" ]; then
    docker login -u="$DOCKER_USER" -p="$DOCKER_PASS"
    docker push jc5x/firefly-iii:latest
    docker push jc5x/firefly-iii:release-$VERSION
fi