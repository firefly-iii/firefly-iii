#!/usr/bin/env bash

# build image
echo "$DOCKER_PASSWORD" | docker login -u "$DOCKER_USERNAME" --password-stdin

if [ "$TRAVIS_BRANCH" == "develop" ]; then
    echo "Build develop amd64"
    docker build -t jc5x/firefly-iii:develop-amd -f Dockerfile.amd64 .
    docker push jc5x/firefly-iii:develop-amd
fi

if [ "$TRAVIS_BRANCH" == "master" ]; then
    echo "Build master amd64"
    docker build -t jc5x/firefly-iii:latest-amd -f Dockerfile.amd64 .
    docker tag jc5x/firefly-iii:latest-amd jc5x/firefly-iii:release-$VERSION-amd
    docker push jc5x/firefly-iii:latest-amd
    docker push jc5x/firefly-iii:release-$VERSION-amd
fi