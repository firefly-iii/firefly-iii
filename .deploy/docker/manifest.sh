#!/usr/bin/env bash

if [ "$TRAVIS_BRANCH" == "develop" ]; then
    TARGET=jc5x/firefly-iii:develop
    ARM=jc5x/firefly-iii:develop-arm
    AMD=jc5x/firefly-iii:develop-amd64

    docker manifest create $TARGET $AMD $ARM
    docker manifest annotate $TARGET $ARM --arch arm   --os linux
    docker manifest annotate $TARGET $AMD --arch amd64 --os linux
    docker manifest push $TARGET
fi

echo "The version is $VERSION"

if [ "$TRAVIS_BRANCH" == "master" ]; then
    TARGET=jc5x/firefly-iii:latest
    ARM=jc5x/firefly-iii:latest-arm
    AMD=jc5x/firefly-iii:latest-amd64

    docker manifest create $TARGET $AMD $ARM
    docker manifest annotate $TARGET $ARM --arch arm   --os linux
    docker manifest annotate $TARGET $AMD --arch amd64 --os linux
    docker manifest push $TARGET

    # and another one for version specific:
    TARGET=jc5x/firefly-iii:release-$VERSION
    ARM=jc5x/firefly-iii:release-$VERSION-arm
    AMD=jc5x/firefly-iii:release-$VERSION-amd64

    docker manifest create $TARGET $AMD $ARM
    docker manifest annotate $TARGET $ARM --arch arm   --os linux
    docker manifest annotate $TARGET $AMD --arch amd64 --os linux
    docker manifest push $TARGET
fi
