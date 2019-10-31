#!/usr/bin/env bash

echo "$DOCKER_PASSWORD" | docker login -u "$DOCKER_USERNAME" --password-stdin

if [ "$TRAVIS_BRANCH" == "develop" ]; then
    echo "Build develop arm64"
    docker build -t jc5x/firefly-iii:develop-arm64 -f Dockerfile.arm64 .
    docker tag jc5x/firefly-iii:develop-arm64 jc5x/firefly-iii:develop-$VERSION-arm64
    docker push jc5x/firefly-iii:develop-arm64
    docker push jc5x/firefly-iii:develop-$VERSION-arm64
fi

if [ "$TRAVIS_BRANCH" == "master" ]; then
    echo "Build master arm64"
    docker build -t jc5x/firefly-iii:latest-arm64 -f Dockerfile.arm64 .
    docker tag jc5x/firefly-iii:latest-arm64 jc5x/firefly-iii:release-$VERSION-arm64
    docker push jc5x/firefly-iii:latest-arm64
    docker push jc5x/firefly-iii:release-$VERSION-arm64
fi