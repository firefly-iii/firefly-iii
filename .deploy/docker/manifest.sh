#!/usr/bin/env bash

if [ "$TRAVIS_BRANCH" == "develop" ]; then
    TARGET=jc5x/firefly-iii:develop
    IMAGENAME=jc5x/firefly-iii:develop-$ARCH

    docker manifest create $TARGET $IMAGENAME
    docker manifest annotate $TARGET $IMAGENAME --arch $ARCH --os linux
    docker manifest push $TARGET
fi

echo "The version is $VERSION"

if [ "$TRAVIS_BRANCH" == "master" ]; then
    TARGET=jc5x/firefly-iii:latest
    IMAGENAME=jc5x/firefly-iii:latest-$ARCH


    docker manifest create $TARGET $IMAGENAME
    docker manifest annotate $TARGET $IMAGENAME --arch $ARCH --os linux
    docker manifest push $TARGET

    # and another one for version specific:
    TARGET=jc5x/firefly-iii:release-$VERSION
    IMAGENAME=jc5x/firefly-iii:release-$VERSION-$ARCH

    docker manifest create $TARGET $IMAGENAME
    docker manifest annotate $TARGET $IMAGENAME --arch $ARCH --os linux
    docker manifest push $TARGET
fi
