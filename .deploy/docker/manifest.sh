#!/usr/bin/env bash

if [ "$TRAVIS_BRANCH" == "develop" ]; then
    TARGET=jc5x/firefly-iii:develop

    ARM32=jc5x/firefly-iii:develop-arm
    ARM64=jc5x/firefly-iii:develop-arm64
    AMD64=jc5x/firefly-iii:develop-amd64

    docker manifest create $TARGET $ARM32 $ARM64 $AMD64
    docker manifest annotate $TARGET $ARM32 --arch arm   --os linux
    docker manifest annotate $TARGET $ARM64 --arch arm64 --os linux
    docker manifest annotate $TARGET $AMD64 --arch amd64 --os linux
    docker manifest push $TARGET
fi

if [ "$TRAVIS_BRANCH" == "master" ]; then
    TARGET=jc5x/firefly-iii:latest

    ARM32=jc5x/firefly-iii:latest-arm
    ARM64=jc5x/firefly-iii:latest-arm64
    AMD64=jc5x/firefly-iii:latest-amd64


    docker manifest create $TARGET $ARM32 $ARM64 $AMD64
    docker manifest annotate $TARGET $ARM32 --arch arm   --os linux
    docker manifest annotate $TARGET $ARM64 --arch arm64 --os linux
    docker manifest annotate $TARGET $AMD64 --arch amd64 --os linux
    docker manifest push $TARGET

    # and another one for version specific:
    VERSION_TARGET=jc5x/firefly-iii:release-$VERSION

    VERSION_ARM32=jc5x/firefly-iii:release-$VERSION-arm
    VERSION_ARM64=jc5x/firefly-iii:release-$VERSION-arm64
    VERSION_AMD64=jc5x/firefly-iii:release-$VERSION-amd64

    docker manifest create $VERSION_TARGET $VERSION_ARM32 $VERSION_ARM64 $VERSION_AMD64
    docker manifest annotate $VERSION_TARGET $VERSION_ARM32 --arch arm   --os linux
    docker manifest annotate $VERSION_TARGET $VERSION_ARM64 --arch arm64 --os linux
    docker manifest annotate $VERSION_TARGET $VERSION_AMD64 --arch amd64 --os linux
    docker manifest push $VERSION_TARGET
fi
